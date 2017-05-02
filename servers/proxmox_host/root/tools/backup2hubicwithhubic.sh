#!/bin/bash

echo Backup HUBIC
export BACKUPNAME="BackupDolibarr"
export DIRTOBACKUP=/mnt/host_dump

if [ "x$DBUS_SESSION_BUS_ADDRESS" = "x" ]
then
	echo "Set DBUS_SESSION_BUS_ADDRESS"
	export DBUS_SESSION_BUS_ADDRESS=`dbus-daemon --session --fork --print-address`
	echo "DBUS_SESSION_BUS_ADDRESS=$DBUS_SESSION_BUS_ADDRESS"
fi
#hubic login --password_path=/root/hubicpass contact@dolibarr.org
#hubic backup create --name=BackupDolibarr --frequency=never /aa
#hubic backup update /home/ldestailleur/hubic/Images
echo "hubic backup attach $BACKUPNAME $DIRTOBACKUP"
hubic backup attach $BACKUPNAME $DIRTOBACKUP 
echo "hubic backup update $BACKUPNAME"
hubic backup update $BACKUPNAME

