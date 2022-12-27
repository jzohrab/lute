#!/bin/bash

set -e

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

# echo "Verify:"
# ls -a -1 $BACKUPDIR

echo
echo "Make .env[.test].local"
cp .env.local.example .env.local
cp .env.test.local.example .env.test.local

echo
echo "Making the zip file:"
touch ../lute_release.zip
rm ../lute_release.zip
zip ../lute_release.zip . --recurse-paths -qdgds 1m -x "*.git*" -x "tests/*" -x "utils" -x "var/*" -x "media/*"

echo
echo "Restoring my .env files"
cp "${BACKUPDIR}/.env.local" .
cp "${BACKUPDIR}/.env.test.local" .

echo
echo "Done, release created:"
ls -larth ../lute_release.zip

echo
echo "Make ../lute_release folder for local testing."
RELTESTDIR="../lute_release"
rm -rf "$RELTESTDIR"
mkdir -p "$RELTESTDIR"
cp ../lute_release.zip "$RELTESTDIR"
echo "Unzipping to $RELTESTDIR ..."

pushd "$RELTESTDIR"
  unzip -q lute_release.zip
  rm lute_release.zip
  echo "Done."
  # ls -larth
popd

echo
echo "Done."
echo "Change the .env.local in $RELTESTDIR for testing environment as needed."
