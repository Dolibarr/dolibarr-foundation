#!/bin/bash


echo "----- Compare 2 Dolibarr modules -----"

ZIP1="$1"
ZIP2="$2"

if [ "x$ZIP2" == "x" ];
then
	echo "Example:"
	echo "htdocs/custom/marketplace/scripts/comp.sh ../dolibarr_documents/product/c21271d20230204164353/aaa.ip  ../dolibarr_documents/product/c21271d20230204164353/bbb.zip"
	echo
	exit
fi


TMP1=$(mktemp -d)
TMP2=$(mktemp -d)

# Décompression
unzip -qq "$ZIP1" -d "$TMP1"
unzip -qq "$ZIP2" -d "$TMP2"

# Extraction des lignes PHP
find "$TMP1" -name "*.php" -type f -exec cat {} + | sed '/^\s*$/d' > /tmp/php1.txt
find "$TMP2" -name "*.php" -type f -exec cat {} + | sed '/^\s*$/d' > /tmp/php2.txt

# Lignes communes
COMMON=$(comm -12 <(sort /tmp/php1.txt) <(sort /tmp/php2.txt) | wc -l)

TOTAL=$(cat /tmp/php1.txt /tmp/php2.txt | sort | uniq | wc -l)

PERCENT=$(awk "BEGIN { printf \"%.2f\", ($COMMON / $TOTAL) * 100 }")

echo "Code PHP commun : $PERCENT %"

# Nettoyage
rm -rf "$TMP1" "$TMP2" /tmp/php1.txt /tmp/php2.txt

echo
