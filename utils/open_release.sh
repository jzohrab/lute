#!/bin/bash

# I have a virtual host set up in my Apache vhosts,
# /usr/local/etc/httpd/extra/httpd-vhosts.conf,
# which points to the directory where the lute_release is unzipped.
# The lute_release/.env.local works for my Apache setup ...
# if others wish to use this script, they'll need to set up their vhosts.
#
# This could be generalized later if needed.
open http://lute_release.local:8080/

# Don't bother opening the debug release.  It is the same as the other
# release except for additional dev composer dependencies.  More
# importantly, opening it up at the same time as the release can cause
# problems if both of them try to run migrations at the same time on
# the same database.
#
# open http://lute_debug.local:8080/
