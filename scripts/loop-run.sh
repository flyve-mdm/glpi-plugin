#!/bin/bash

SELF=`readlink -f $0`
SELFDIR=`dirname $SELF`
PHPCLI=/usr/bin/php
PHP_SCRIPT=$SELFDIR/mqtt.php
PIDFILE=/var/run/flyvemdm.pid

on_term()
{
  if [ -n ${PROC_ID+x} ];
  then kill -SIGTERM $PROC_ID;
  fi
  echo "\n"
  exit 0
}

trap 'on_term' SIGINT SIGTERM

until false; do
  $PHPCLI -f "$PHP_SCRIPT" &
  PROC_ID=$!
  while kill -0 "$PROC_ID" >/dev/null 2>&1; do
    sleep 5
  done
done

exit 0