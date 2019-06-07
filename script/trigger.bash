#!/bin/bash
#
# Trigger the updates the website or the bot by receiving signals
#
# This should be run as the `deploy` user

PIDFILE=/tmp/lsd-update.pid
echo $$ > $PIDFILE
echo 'Started trigger monitoring, pid='$$

cd "$(dirname "$0")"

trap './update web' SIGUSR1
trap './update bot' SIGUSR2

while true; do
    sleep 2
done
