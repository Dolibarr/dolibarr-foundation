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

ZIP1=$(ls -t "$DIR1"/*.zip 2>/dev/null | grep -v disabled | head -n 1)
ZIP2=$(ls -t "$DIR2"/*.zip 2>/dev/null | grep -v disabled | head -n 1)

if [ -z "$ZIP1" ] || [ -z "$ZIP2" ]; then
    echo "Erreur : fichier zip introuvable dans un des répertoires"
    exit 1
fi

TMPDIR="/tmp/comp."`id -un`

TMP1="$TMPDIR/dir1"
TMP2="$TMPDIR/dir2"

rm -fr "$TMPDIR"
mkdir "$TMPDIR"
chown -R dolibarr:www-data "$TMPDIR" 2>&1
chmod -R a+rwx "$TMPDIR"
rm -rf "$TMPDIR/*"

# Décompression
unzip -qq "$ZIP1" -d "$TMP1"
unzip -qq "$ZIP2" -d "$TMP2"

chown -R dolibarr:www-data "$TMPDIR" 2>&1
chmod -R a+rwx "$TMPDIR"

XXXX1=$(basename "$ZIP1" | sed -E 's/^module_([^ -]+)-.*\.zip$/\1/i')
XXXX2=$(basename "$ZIP2" | sed -E 's/^module_([^ -]+)-.*\.zip$/\1/i')

echo "Process module 1: $ZIP1 = $XXXX1"
echo "And     module 2: $ZIP2 = $XXXX2"

# Extraction des lignes PHP
find "$TMP1" -name "*.php" -type f -exec cat {} + | sed '/^\s*$/d' | sed -E "s/$XXXX1/MODULENAME/Ig" > $TMPDIR/php1.php
find "$TMP2" -name "*.php" -type f -exec cat {} + | sed '/^\s*$/d' | sed -E "s/$XXXX2/MODULENAME/Ig" > $TMPDIR/php2.php

cloc --strip-comments=txt --original-dir $TMPDIR/php1.php
cloc --strip-comments=txt --original-dir $TMPDIR/php2.php
rm $TMPDIR/php1.txt $TMPDIR/php2.txt 2>/dev/null
mv $TMPDIR/php1.php.txt $TMPDIR/php1.txt
mv $TMPDIR/php2.php.txt $TMPDIR/php2.txt

dos2unix $TMPDIR/php1.txt
dos2unix $TMPDIR/php2.txt

sort $TMPDIR/php1.txt > $TMPDIR/php1s.txt
sort $TMPDIR/php2.txt > $TMPDIR/php2s.txt


# Lignes communes
LINEF1=$(cat $TMPDIR/php1s.txt | wc -l)
LINEF2=$(cat $TMPDIR/php2s.txt | wc -l)
TOTAL=$(cat $TMPDIR/php1s.txt $TMPDIR/php2s.txt | wc -l)

COMMON=$(comm -12 $TMPDIR/php1s.txt $TMPDIR/php2s.txt | wc -l)

UNIQ1=$(comm -23 $TMPDIR/php1s.txt $TMPDIR/php2s.txt | wc -l)
UNIQ2=$(comm -12 $TMPDIR/php1s.txt $TMPDIR/php2s.txt | wc -l)
UNIQ=$(comm -3 $TMPDIR/php1s.txt $TMPDIR/php2s.txt | wc -l)


PERCENT=$(awk "BEGIN { printf \"%.2f\", ($COMMON * 2 / $TOTAL) * 100 }")

echo "Lines of code PHP total : $LINEF1 + $LINEF2 = $TOTAL"
echo "Lines of code PHP unique : $UNIQ1 + $UNIQ2 = $UNIQ"
echo "Lines of code PHP common : $COMMON x2"
echo "Percent similarity: $PERCENT %"

echo
