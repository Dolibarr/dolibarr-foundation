#!/bin/bash

export TARGET=hubic
export BACKUPNAME="HubiC-DeskBackup_backup_proxmox_dumps"
export DIRTOBACKUP=/var/lib/vz/dump

echo Backup to $TARGET using rclone
echo rclone --transfers=2 sync $DIRTOBACKUP $TARGET:$BACKUPNAME
rclone --transfers=2 sync $DIRTOBACKUP $TARGET:$BACKUPNAME

