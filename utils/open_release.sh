#!/bin/bash

# I have a virtual host set up in my Apache vhosts,
# /usr/local/etc/httpd/extra/httpd-vhosts.conf,
# which points to the directory where the lute_release is unzipped.
# The lute_release/.env.local works for my Apache setup ...
# if others wish to use this script, they'll need to set up their vhosts.
#
# This could be generalized later if needed.
open http://lute_release.local:8080/
