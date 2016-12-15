#!/bin/sh
SELF=`readlink -f $0`
SELFDIR=`dirname $SELF`

oldpath=`pwd`
cd $SELFDIR/..
mysql -u glpi -pglpi -e "DROP DATABASE IF EXISTS \`glpi-storkmdm-test\`"
php ../../tools/cliinstall.php --db=glpi-storkmdm-test --user=glpi --pass=glpi --tests --force
php -S localhost:8088 -t ../.. ../../tests/router.php &>/dev/null &
PID=$!
echo $PID
phpunit --coverage-text
cd $oldpath
kill $PID
