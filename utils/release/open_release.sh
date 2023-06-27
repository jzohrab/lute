#!/bin/bash

RELTESTDIR="../lute_release"
TESTPORT=9876

echo "Starting server for $RELTESTDIR on port $TESTPORT ... hit Ctl-C to stop it"

# Open to non-existent site!
# On Chrome, the tab refreshes itself periodically, so
# when php starts, the site is shown.
open http://localhost:${TESTPORT}/

pushd "$RELTESTDIR"
  mkdir zz_bkp
  sed -i '' 's/~\/Dropbox\/LuteBackup\//.\/zz_bkp/g' .env
  pushd public
  # Run using the built-in PHP web server.
  php -S localhost:${TESTPORT}
  popd
popd
