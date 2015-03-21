#!/usr/bin/python
__author__ = 'dropdead'

import os
import shutil
from itertools import islice

__DIR__ = os.path.dirname(os.path.realpath(__file__))
dir_organized = __DIR__ + "/data/organized/"
dir_raw = __DIR__ + "/data/raw/"
dir_training = __DIR__ + "data/raw/training/"

if not os.path.isdir(dir_training):
    os.makedirs(dir_training)
if not os.path.isdir(dir_training + "trash/"):
    os.makedirs(dir_training + "trash/")
if not os.path.isdir(dir_training + "plain/"):
    os.makedirs(dir_training + "plain/")
if not os.path.isdir(dir_training + "hash/"):
    os.makedirs(dir_training + "hash/")


for (dirpath, dirnames, filenames) in os.walk(dir_raw):
    for f in dirnames:
        if f == "training" or f == "hash" or f == "plain" or f == "trash":
            continue
        for (rawdirpath, rawdirnames, rawfilenames) in os.walk(dirpath + f):
            for rf in rawfilenames:
                if os.path.exists(dir_training + "trash/" + rf):
                    continue
                if os.path.exists(dir_training + "plain/" + rf):
                    continue
                if os.path.exists(dir_training + "hash/" + rf):
                    continue

                print rawdirpath + "/" + rf
                with open(rawdirpath + "/" + rf) as rfh:
                    #head = [next(rfh) for x in xrange(20)]
                    head = list(islice(rfh, 20))

                print head
                print rawdirpath + "/" + rf
                answer = raw_input("[t]rash [p]lain [h]ash => ")

                if answer == "t":
                   shutil.copyfile(rawdirpath + "/" + rf, dir_training + "trash/" + rf)
                if answer == "p":
                   shutil.copyfile(rawdirpath + "/" + rf, dir_training + "plain/" + rf)
                if answer == "h":
                   shutil.copyfile(rawdirpath + "/" + rf, dir_training + "hash/" + rf)


