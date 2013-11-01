#!/bin/bash

# Script taken from https://github.com/tklein23/validate-oai-xmetadissplus
# written by Thoralf Klein, 2012-2013

CATALOG="$1"

mkdir -pv cache/
grep '<uri' "$CATALOG" |perl -pe 's/^.*name="([^"]+)"\s.*uri="([^"]+)".*/$1 $2/ig' |while read url file; do
   wget -O "$file" "$url"
done
