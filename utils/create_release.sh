#!/bin/bash

set -e

# Files are changed here, so you should have your own copies safely stored.
BACKUPDIR=../lute_my_files
if [[ ! -f "${BACKUPDIR}/.env" ]]
then
    echo "Missing backup .env, quitting."
    exit 1
fi
if [[ ! -f "${BACKUPDIR}/.env.test" ]]
then
    echo "Missing backup .env.test, quitting."
    exit 1
fi

# Run this script from the project root directory.
clear
echo "Creating release. (debug not created, not used?)"
echo

git clean -xdf

echo
echo Generating manifest.
php utils/write_app_manifest.php
cat manifest.json
echo

echo
echo ".env files"
cp .env.example .env

# echo
# echo "Ensuring full set of dev dependencies"
# APP_ENV=dev composer install --dev
# 
# echo
# echo "Making the debug zip file, including dev dependencies:"
# touch ../lute_debug.zip
# rm ../lute_debug.zip
# zip ../lute_debug.zip . --recurse-paths -qdgds 1m -x "*.git*" -x "tests/*" -x "utils" -x "var/*" -x "media/*" -x "public/media/*" -x "public/userimages/*" -x "zz_backup" -x "db/*.db"

echo
echo "Install w/o dev dependencies to reduce release zip size."
APP_ENV=prod composer install --no-dev

echo
echo "Making the release zip file:"
touch ../lute_release.zip
rm ../lute_release.zip
zip ../lute_release.zip . --recurse-paths -qdgds 1m -x "*.git*" -x "tests/*" -x "utils" -x "var/*"

echo
echo "Restoring my .env files"
cp "${BACKUPDIR}/.env" .
cp "${BACKUPDIR}/.env.test" .

echo
echo "Restoring dev dependencies."
APP_ENV=dev composer install --dev

echo
echo "Done:"
ls -larth ../lute_*.zip

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
echo "Make ../lute_release_docker folder for local testing."
DOCKERRELTESTDIR="../lute_release_docker"
rm -rf "$DOCKERRELTESTDIR"
mkdir -p "$DOCKERRELTESTDIR"
cp ../lute_release.zip "$DOCKERRELTESTDIR"
echo "Unzipping to $DOCKERRELTESTDIR ..."

pushd "$DOCKERRELTESTDIR"
  unzip -q lute_release.zip
  rm lute_release.zip
  rm .env
  cp .env.example.docker .env
  echo "Done."
  # ls -larth
popd

echo
echo "Done."
echo "Change the .env in $RELTESTDIR for testing environment as needed."
