#!/bin/sh
# To launch backup when server is launched in rescue mode
hostname='ftp.nltechno.com'
username='backup_proxmox'

rsync -a -vv --delete-before -W --exclude .ssh --bwlimit=100000 /mnt/bbb/dump/*.lzo $username@$hostname:/home/backup_proxmox

echo End of rsync
