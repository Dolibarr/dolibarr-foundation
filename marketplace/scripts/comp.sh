#!/bin/bash
# Script to compare 2 zip modules. Return a percentage of similarity.
#

echo "----- Compare 2 Dolibarr modules -----"

PATHDOC="/home/dolibarr/asso.dolibarr.org/dolibarr_documents/produit"

DIR1="$PATHDOC/$1"
DIR2="$PATHDOC/$2"

if [ ! -d "$DIR1" ] || [ ! -d "$DIR2" ]; then
    echo "Usage: $0 <dir1> <dir2>"
    echo
	echo "Example:"
	echo "comp.sh  mpxxxxxxxxx  mpzzzzzzzz"
	echo
	exit
fi

ZIP1=$(ls -t "$DIR1"/*.zip 2>/dev/null | head -n 1)
ZIP2=$(ls -t "$DIR2"/*.zip 2>/dev/null | head -n 1)

if [ -z "$ZIP1" ] || [ -z "$ZIP2" ]; then
    echo "Erreur : fichier zip introuvable dans un des répertoires"
    exit 1
fi

TMP1="/tmp/comp/dir1"
TMP2="/tmp/comp/dir2"

mkdir /tmp/comp
chown -R dolibarr:www-data "/tmp/comp"
chmod -R a+rwx "/tmp/comp"
rm -rf "/tmp/comp/*"

# Décompression
unzip -qq "$ZIP1" -d "$TMP1"
unzip -qq "$ZIP2" -d "$TMP2"

chown -R dolibarr:www-data "/tmp/comp"
chmod -R a+rwx "/tmp/comp"

XXXX1=$(basename "$ZIP1" | sed -E 's/^module_([^ -]+)-.*\.zip$/\1/i')
XXXX2=$(basename "$ZIP2" | sed -E 's/^module_([^ -]+)-.*\.zip$/\1/i')

echo "Process module 1: $ZIP1 = $XXXX1"
echo "And     module 2: $ZIP2 = $XXXX2"

# Extraction des lignes PHP
find "$TMP1" -name "*.php" -type f -exec cat {} + | sed '/^\s*$/d' | sed -E "s/$XXXX1/MODULENAME/Ig" > /tmp/comp/php1.php
find "$TMP2" -name "*.php" -type f -exec cat {} + | sed '/^\s*$/d' | sed -E "s/$XXXX2/MODULENAME/Ig" > /tmp/comp/php2.php

cloc --strip-comments=txt --original-dir /tmp/comp/php1.php
cloc --strip-comments=txt --original-dir /tmp/comp/php2.php
rm /tmp/comp/php1.txt /tmp/comp/php2.txt 2>/dev/null
mv /tmp/comp/php1.php.txt /tmp/comp/php1.txt
mv /tmp/comp/php2.php.txt /tmp/comp/php2.txt

dos2unix /tmp/comp/php1.txt
dos2unix /tmp/comp/php2.txt

sort /tmp/comp/php1.txt > /tmp/comp/php1s.txt
sort /tmp/comp/php2.txt > /tmp/comp/php2s.txt


# Lignes communes
LINEF1=$(cat /tmp/comp/php1s.txt | wc -l)
LINEF2=$(cat /tmp/comp/php2s.txt | wc -l)
TOTAL=$(cat /tmp/comp/php1s.txt /tmp/comp/php2s.txt | wc -l)

COMMON=$(comm -12 /tmp/comp/php1s.txt /tmp/comp/php2s.txt | wc -l)

UNIQ1=$(comm -23 /tmp/comp/php1s.txt /tmp/comp/php2s.txt | wc -l)
UNIQ2=$(comm -12 /tmp/comp/php1s.txt /tmp/comp/php2s.txt | wc -l)
UNIQ=$(comm -3 /tmp/comp/php1s.txt /tmp/comp/php2s.txt | wc -l)


PERCENT=$(awk "BEGIN { printf \"%.2f\", ($COMMON * 2 / $TOTAL) * 100 }")

echo "Lines of code PHP total : $LINEF1 + $LINEF2 = $TOTAL"
echo "Lines of code PHP unique : $UNIQ1 + $UNIQ2 = $UNIQ"
echo "Lines of code PHP common : $COMMON x2"
echo "Percent similarity: $PERCENT %"

chmod -R 666 /tmp/comp 2>/dev/null
chown -R dolibarr:www-data /tmp/comp 2>/dev/null

# Nettoyage
#rm -rf "$TMP1" "$TMP2" /tmp/php1.txt /tmp/php2.txt

echo
