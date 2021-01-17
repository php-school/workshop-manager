#!/bin/bash

#reset changes from composer updates
git reset --hard

cd .github/secrets
gpg --quiet --batch --yes --decrypt --passphrase="$PHAR_BUILD_PHRASE" \
--output .github/secrets/secrets.tar secrets.tar.gpg
cd ../../
tar xvf .github/secrets/secrets.tar -C .github/secrets

# Setup SSH agent:
eval "$(ssh-agent -s)" #start the ssh agent
chmod 600 .github/secrets/build-key.pem
ssh-add .github/secrets/build-key.pem

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
