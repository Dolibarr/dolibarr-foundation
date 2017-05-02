#!/bin/sh
hostname='ftp.nltechno.com'
username='backup_proxmox'

rsync -a -vv --delete -W --exclude .ssh --bwlimit=100000 /var/lib/vz/dump/. $username@$hostname:/home/backup_proxmox

echo End of rsync
