#!/bin/bash

set -e

echo
RELTESTDIR="../lute_release"
echo "Deploying to $RELTESTDIR ..."

rm -rf "$RELTESTDIR"
mkdir -p "$RELTESTDIR"
cp ../lute_release.zip "$RELTESTDIR"

pushd "$RELTESTDIR"
  unzip -q lute_release.zip
  rm lute_release.zip
  echo
  echo "Done."
  # ls -larth
popd
