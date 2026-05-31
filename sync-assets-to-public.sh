#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"
mkdir -p public/assets

if command -v rsync >/dev/null 2>&1; then
    rsync -a --delete --exclude uploads/ assets/ public/assets/
else
    find assets -mindepth 1 -maxdepth 1 ! -name uploads -exec cp -R {} public/assets/ \;
fi

echo "Assets synchronisés vers public/assets."
