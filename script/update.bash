#!/bin/bash
#
# Updates the website or the bot
#
# This should be run as the `deploy` user.

echo "Updating $1..."

cd "$(dirname "$0")"

if [ "$1" == "web" ]; then
    cd ..
    git pull
fi

if [ "$1" == "bot" ]; then
    cd ../../bot
    git pull
fi
