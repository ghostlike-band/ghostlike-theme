#!/bin/sh
# Baut lokal build/ghostlike.zip (z. B. für die allererste Installation per Upload).
set -e
cd "$(dirname "$0")/.."
rm -rf build && mkdir -p build/ghostlike
rsync -a --exclude-from=.distignore ./ build/ghostlike/
(cd build && zip -rq ghostlike.zip ghostlike)
echo "build/ghostlike.zip"
