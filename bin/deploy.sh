#!/bin/bash
set -e

#reset changes from composer updates
git reset --hard

# Setup git defaults:
git config --global user.email "aydin@hotmail.co.uk"
git config --global user.name "Aydin Hassan"

# Get box and build PHAR
composer phar

# Without the following step, we cannot checkout the gh-pages branch due to
# file conflicts:
mv workshop-manager.phar workshop-manager.phar.tmp

# Checkout gh-pages and add PHAR file and version:
git fetch
git checkout -b gh-pages
mv workshop-manager.phar.tmp workshop-manager.phar
sha1sum workshop-manager.phar > workshop-manager.phar.version
git add workshop-manager.phar workshop-manager.phar.version

# Commit and push:
git commit -m 'Rebuilt phar'
git push gh-pages:gh-pages
