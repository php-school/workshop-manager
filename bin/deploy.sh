#!/bin/bash

#reset changes from composer updates
git reset --hard

# Unpack secrets; -C ensures they unpack *in* the .travis directory
tar xvf .travis/secrets.tar -C .travis

# Setup SSH agent:
eval "$(ssh-agent -s)" #start the ssh agent
chmod 600 .travis/build-key.pem
ssh-add .travis/build-key.pem

# Setup git defaults:
git config --global user.email "aydin@hotmail.co.uk"
git config --global user.name "Aydin Hassan"

# Add SSH-based remote to GitHub repo:
git remote add deploy git@github.com:php-school/workshop-manager.git
git fetch deploy

# Get box and build PHAR
composer phar

# Without the following step, we cannot checkout the gh-pages branch due to
# file conflicts:
mv workshop-manager.phar workshop-manager.phar.tmp

# Checkout gh-pages and add PHAR file and version:
git checkout -b gh-pages deploy/gh-pages
mv workshop-manager.phar.tmp workshop-manager.phar
sha1sum workshop-manager.phar > workshop-manager.phar.version
git add workshop-manager.phar workshop-manager.phar.version

# Commit and push:
git commit -m 'Rebuilt phar'
git push deploy gh-pages:gh-pages