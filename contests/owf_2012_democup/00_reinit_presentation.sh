#/bin/sh

# Remove dolibarr debian installation
echo
echo STEP 1 - Removed Dolibarr
sudo apt-get purge dolibarr

echo STEP 2 - Install version 3.2
sudo dpkg -i '/home/ldestail/Bureau/02_dolibarr_3.2.3+nmu1_all.deb'



