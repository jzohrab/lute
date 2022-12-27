#!/bin/bash

# Run this script from the project root directory.
clear
echo "Creating release."
echo

echo
echo Generating manifest.
php utils/write_app_manifest.php
cat manifest.json
echo

BACKUPDIR=../lute_release_copies
mkdir -p $BACKUPDIR
echo
echo "Backing up my files to ${BACKUPDIR}."
echo "Backup of files during lute release gen" > $BACKUPDIR/README.txt
cp .env.local "${BACKUPDIR}/"
cp .env.test.local "${BACKUPDIR}/"
cp public/css/styles-overrides.css "${BACKUPDIR}/"

echo "Verify:"
ls -a -1 $BACKUPDIR

echo
echo "Make .env.local:"

# single quote, so things don't get interpolated
echo '# Your personal settings

APP_ENV=prod
DB_DATABASE=lute_demo

DB_HOSTNAME=localhost
DB_USER=root
DB_PASSWORD=root

# Leave the next line as-is :-)
DATABASE_URL=mysql://${DB_USER}:${DB_PASSWORD}@${DB_HOSTNAME}/${DB_DATABASE}?serverVersion=8&charset=utf8' > .env.local

echo ----------------------------
cat .env.local
echo ----------------------------

echo
echo "Remove .env.test.local:"
rm .env.test.local

echo
echo "Making the zip file:"
touch ../lute_release.zip
rm ../lute_release.zip
zip -r ../lute_release.zip . -x "*.git*" -x "*tests*" -x "utils" -x "*var*" > /dev/null

echo
echo "Restoring my .env files"
cp "${BACKUPDIR}/.env.local" .

echo
echo "Done, backup created:"
ls -larth ../lute_release.zip
