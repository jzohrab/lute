#!/bin/bash

set -e

echo
echo "Generating manifest: "
php utils/write_app_manifest.php
cat manifest.json

echo
echo "Making the release zip file:"
touch ../lute_release.zip
rm ../lute_release.zip
zip ../lute_release.zip . --recurse-paths -qdgds 1m -x "*.git*" -x "tests/*" -x "utils" -x "var/*"

echo
echo "Done:"
ls -larth ../lute_*.zip
