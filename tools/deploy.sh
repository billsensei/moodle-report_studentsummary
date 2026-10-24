#!/bin/bash
# Copy the plugin into the local Moodle trees (symlinks break __DIR__-based config.php lookups under a web server).
set -e
SRC="$(cd "$(dirname "$0")/.." && pwd)"
for tree in /home/bill/test2/moodle45 /home/bill/test2/moodle /home/bill/test2/moodle51/public; do
    dest="$tree/report/studentsummary"
    [ -L "$dest" ] && rm "$dest"
    mkdir -p "$dest"
    rsync -a --delete --exclude tools --exclude docs --exclude .git "$SRC/" "$dest/"
done
