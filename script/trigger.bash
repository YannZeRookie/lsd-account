#!/bin/bash
#
# Trigger the updates the website or the bot by receiving signals
#
# This should be run as the `deploy` user

PIDFILE=../cache/lsd-update.pid

echo 'Started trigger monitoring'

cd "$(dirname "$0")"

while true; do
    sleep 2
    if [ -f $PIDFILE ]; then
       mode=`cat $PIDFILE`
       ./update $mode
       rm -f $PIDFILE
    fi
done
