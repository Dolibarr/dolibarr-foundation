#!/bin/sh
hostname='2byte.gotdns.com'
username='dolibarr'

rsync -a -vv --rsh 'ssh -p 65011' --delete -W --exclude .ssh --bwlimit=10000 /var/lib/vz/dump/. $username@$hostname:/media/COPIAS/dolibarr/

echo End of rsync

