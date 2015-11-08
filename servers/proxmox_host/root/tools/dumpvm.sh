#!/bin/sh
# Launch immediate dump of VM
vzdump 100 --maxfiles 2 --remove 1 --mode snapshot --compress lzo --storage backups --node ks220364 --bwlimit 120000
vzdump 102 --maxfiles 2 --remove 1 --mode snapshot --compress lzo --storage backups --node ks220364 --bwlimit 120000
vzdump 103 --maxfiles 2 --remove 1 --mode snapshot --compress lzo --storage backups --node ks220364 --bwlimit 120000

