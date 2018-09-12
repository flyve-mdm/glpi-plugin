#!/bin/bash
### BEGIN INIT INFO
# Provides:          glpi_flyvemdm_mqtt_subscriber
# Required-Start:    networking mysql
# Required-Stop:     networking mysql
# Default-Start:     2 3 4 5
# Default-Stop:      0 1 6
# Short-Description: MQTT implementation for Flyve mdm plugin for GLPI
# Description:       Flyve MDM uses MQTT to dialog with managed devices
#                    
#                    
### END INIT INFO


. /lib/lsb/init-functions

NAME="Flyve Mobile Device Management for GLPI"
# Daemon name, where is the actual executable
SELF=`readlink -f $0`
SELFDIR=`dirname $SELF`
USER=www-data
GROUP=www-data
PHPCLI=/usr/bin/php
PHP_SCRIPT=$SELFDIR/mqtt.php
PIDFILE=/var/run/flyvemdm.pid

## Do some sanity checks before even trying to start.
function sanity_checks {
 if [ ! -r $PHP_SCRIPT ]; then
    log_warning_msg "$0: WARNING: $PHP_SCRIPT cannot be read. "
    echo                "WARNING: $PHP_SCRIPT cannot be read. " | $ERR_LOGGER
    exit 1
  fi
}

case "$1" in
  'start')
    sanity_checks
    log_daemon_msg "Starting $NAME"
    start-stop-daemon --start --quiet $SSD_START_ARGS -c $USER:$GROUP -m -p $PIDFILE -b --exec $SELFDIR/loop-run.sh \
        || log_progress_msg "$NAME is already running"
      log_end_msg 0
    ;;

  'stop')
    if [ -e $PIDFILE ]; then
      status_of_proc -p $PIDFILE $SELFDIR/loop-run.sh "Stoppping the $NAME process" && status="0" || status="$?"
      if [ "$status" = 0 ]; then
        start-stop-daemon --stop --quiet --oknodo --pidfile $PIDFILE
        /bin/rm -rf $PIDFILE
       fi
    else
      log_daemon_msg "$NAME process is not running"
      log_end_msg 0
    fi
    ;;

  'restart')
    $0 stop && sleep 2 && $0 start
    ;;

  'status')
    if [ -e $PIDFILE ]; then
      status_of_proc -p $PIDFILE $SELFDIR/loop-run.sh "$NAME process" && exit 0 || exit $?
    else
      log_daemon_msg "$NAME Process is not running"
      log_end_msg 0
    fi
    ;;

esac
