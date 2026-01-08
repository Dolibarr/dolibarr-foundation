#!/bin/bash


echo "----- Compare 2 Dolibarr modules -----"

DIR1="$1"
DIR2="$2"

if [ ! -d "$DIR1" ] || [ ! -d "$DIR2" ]; then
    echo "Usage: $0 <dir1> <dir2>"
    echo
	echo "Example:"
	echo "htdocs/custom/marketplace/scripts/comp.sh ../dolibarr_documents/produit/c21271d20230204164353  ../dolibarr_documents/produit/c21271d20230204164353"
	echo
	exit
fi

ZIP1=$(ls -t "$DIR1"/*.zip 2>/dev/null | head -n 1)
ZIP2=$(ls -t "$DIR2"/*.zip 2>/dev/null | head -n 1)

if [ -z "$ZIP1" ] || [ -z "$ZIP2" ]; then
    echo "Erreur : fichier zip introuvable dans un des répertoires"
    exit 1
fi

TMP1="/tmp/dir1"
TMP2="/tmp/dir2"

rm -rf "$TMP1" "$TMP2" /tmp/php1.txt /tmp/php2.txt

# Décompression
unzip -qq "$ZIP1" -d "$TMP1"
unzip -qq "$ZIP2" -d "$TMP2"

# Extraction des lignes PHP
find "$TMP1" -name "*.php" -type f -exec cat {} + | sed '/^\s*$/d' > /tmp/php1.txt
find "$TMP2" -name "*.php" -type f -exec cat {} + | sed '/^\s*$/d' > /tmp/php2.txt

dos2unix /tmp/php1.txt
dos2unix /tmp/php2.txt

sort /tmp/php1.txt > /tmp/php1s.txt
sort /tmp/php2.txt > /tmp/php2s.txt


# Lignes communes
COMMON=$(comm -12 /tmp/php1s.txt /tmp/php2s.txt | wc -l)
UNIQ=$(comm -3 /tmp/php1s.txt /tmp/php2s.txt | wc -l)

TOTAL=$(cat /tmp/php1s.txt /tmp/php2s.txt | wc -l)

PERCENT=$(awk "BEGIN { printf \"%.2f\", ($COMMON * 2 / $TOTAL) * 100 }")

echo "Code PHP total : $TOTAL"
echo "Code PHP commun : $COMMON x2"
echo "Code PHP unique : $UNIQ"
echo "Percent : $PERCENT %"

# Nettoyage
#rm -rf "$TMP1" "$TMP2" /tmp/php1.txt /tmp/php2.txt

echo
