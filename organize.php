<?php
/**
 * @package     Dumpmon Scraper
 * @copyright   2015 Davide Tampellini - FabbricaBinaria
 * @license     GNU GPL version 3 or later
 */

namespace Dumpmon;

error_reporting(E_ALL);
ini_set('display_errors', 1);

use Dumpmon\Detector\Hash;
use Dumpmon\Detector\Plain;
use Dumpmon\Detector\Trash;
use Dumpmon\Utils\Utils;

require_once __DIR__.'/autoloader.php';

\Autoloader::getInstance()->addMap('Dumpmon\\', __DIR__ . '/Dumpmon');

$banner  = <<<BANNER
Dump Scraper - Organize dump files
Copyright (C) 2015 FabbricaBinaria - Davide Tampellini
===============================================================================
Dump Scraper is Free Software, distributed under the terms of the GNU General
Public License version 3 or, at your option, any later version.
This program comes with ABSOLUTELY NO WARRANTY as per sections 15 & 16 of the
license. See http://www.gnu.org/licenses/gpl-3.0.html for details.
===============================================================================
BANNER;

echo "\n".$banner."\n\n";

$options = getopt('s:u:ht', array('since:', 'until:', 'help', 'train'));

if(isset($options['h']) || isset($options['help']))
{
    $help = <<<HELP
  [-s]        Since date    Start date for processing file dump, format YYYY-MM-DD
  [--since]                 If an "until" date is not provided only this day is processed

  [-u]        Until date    Stop date for processing file dump, format YYYY-MM-DD
  [--until]

  [-t]        Train mode    Process train data
  [--train]

  [-h]        Help          Show this help
  [--help]

HELP;
    echo $help;
    die();
}

if(
    (!isset($options['s']) && !isset($options['since'])) &&
    (!isset($options['u']) && !isset($options['until'])) &&
    (!isset($options['t']) && !isset($options['train']))
)
{
    echo "Please provide a start/until date or enable training mode\n";
    die();
}

$folders = array();

if(isset($options['t']) || isset($options['train']))
{
    $folders[] = 'training';
    $csvDir    = 'training';
}
else
{
    $csvDir    = 'data/raw';

    if(isset($options['s']) || isset($options['since']))
    {
        $folders[] = trim((isset($options['s']) ? $options['s'] : $options['since']));
    }

    if(isset($options['u']) || isset($options['until']))
    {
        $date = strtotime(trim(isset($options['s']) ? $options['s'] : $options['since']));
        $end  = strtotime(trim(isset($options['u']) ? $options['u'] : $options['until']));

        $date = strtotime('+1 day', $date);

        while($end >= $date)
        {
            $folders[] = date('Y-m-d', $date);
            $date = strtotime('+1 day', $date);
        }
    }
}

$csv    = __DIR__.'/'.$csvDir.'/features.csv';
$features = fopen($csv, 'wb');
fputcsv($features, array('Trash score', 'Plain score', 'Hash score', 'Label', 'Filename'));

$organizers = array(
    'trash' => new Trash(),
    'plain' => new Plain(),
    'hash'  => new Hash(),
);

foreach($folders as $folder)
{
    $source = __DIR__.'/data/raw/'.$folder;

    if(!is_dir($source))
    {
        echo "Directory ".$source." does not exist!\n";
        continue;
    }

    echo "Directory    : ".$folder."\n";
    echo "Memory usage : ". Utils::memory_convert(memory_get_usage())."\n\n";

    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS));

    $i = 0;

    /** @var \SplFileInfo $file */
    foreach($iterator as $file)
    {
        if($file->getFilename() == '.DS_Store' || $file->getFilename() == 'features.csv')
        {
            continue;
        }

        echo '.';
        $i++;

        if($i >= 50)
        {
            echo "    50\n";
            $i = 0;
        }

        if($file->getFilename() == '565184679196631041.txt')
        {
            $x = 1;
        }

        $data  = file_get_contents($file->getPathname());

        // Remove /r since they could mess up regex
        $data = str_replace("\r", '', $data);

        $info = array(
            'data'  => $data,
            'lines' => max(substr_count($data, "\n"), 1)
        );

        $line    = array();
        $results = array(
            'trash' => 0,
            'plain' => 0,
            'hash'  => 0
        );

        /** @var \Dumpmon\Detector\Detector $organizer */
        foreach($organizers as $key => $organizer)
        {
            $organizer->reset();
            $organizer->setInfo($info);
            $organizer->analyze($results);

            $score = min($organizer->getScore(), 3);

            $line[$key] = round($score, 4);
            $results[$key] = round($score, 4);
        }

        switch(basename($file->getPath()))
        {
            case 'hash':
                $label = 1;
                break;
            case 'plain':
                $label = 2;
                break;
            case 'trash':
                $label = 0;
                break;
            default:
                $label = '';
                break;
        }

        $line['label'] = $label;
        $line['id']    = basename($file->getPath()).'/'.$file->getFilename();

        fputcsv($features, $line);
    }

    echo "\n\n";
}

fclose($features);