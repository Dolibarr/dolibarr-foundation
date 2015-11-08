#!/bin/sh
hostname='xxx'
username='yyy'

rsync -a --delete -W --bwlimit=10000 /var/lib/vz/dump/* $username@$hostname:/home/backup_proxmox

