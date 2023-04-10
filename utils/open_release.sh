#!/bin/bash

# Lute v2 can run using the built-in PHP web server.
RELTESTDIR="../lute_release"

# Open to non-existent site!
# A bit wonky, but I don't want to run it as a background.
open http://localhost:9999/

pushd "$RELTESTDIR/public"
  php -S localhost:9999
popd

# Don't bother opening the debug release.  It is the same as the other
# release except for additional dev composer dependencies.  More
# importantly, opening it up at the same time as the release can cause
# problems if both of them try to run migrations at the same time on
# the same database.
#
# open http://lute_debug.local:8080/
