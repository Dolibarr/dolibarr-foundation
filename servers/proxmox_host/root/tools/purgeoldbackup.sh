#!/bin/sh
# Purge old dump files

export day=40
echo Purge files older than $day

cd /var/lib/vz/dump
#find /var/lib/vz/dump -name *.tar.lzo -mtime +$day -exec rm -f {} \;
find /var/lib/vz/dump -mtime +$day -exec rm -f {} \;

echo Done

