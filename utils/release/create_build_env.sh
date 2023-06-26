#!/bin/bash

# Run this script from the build environment root directory.
# Note that this script kills local changes!

set -e

clear
echo "Creating build environment."
echo

echo "Git reset and env file ..."
git clean -xdf
cp .env.example .env

echo
echo "Install only prod dependencies ..."
APP_ENV=prod composer install --no-dev
