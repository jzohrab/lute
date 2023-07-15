#!/bin/bash

# Run this script from the build environment root directory.
# Note that this script kills local changes!
#
# set MODE=offline before calling to copy vendor deps
# from my prod environment

set -e

clear
echo "Creating build environment."

echo
echo "Git reset and env file ..."
git clean -xdf
cp .env.example .env

echo
echo "Install only prod dependencies ..."
if [[ "offline" == "$MODE" ]]; then
    echo "Copying vendor deps from my lute production environment ...."
    cp -r ../lute/vendor .
else
    APP_ENV=prod composer install --no-dev
fi


