#!/bin/bash

#
# Before script for Travis CI
#

# Make a ramdisk for mysql (speed improvement)
sudo mkdir /mnt/ramdisk
sudo mount -t tmpfs -o size=1024m tmpfs /mnt/ramdisk
sudo stop mysql
sudo mv /var/lib/mysql /mnt/ramdisk
sudo ln -s /mnt/ramdisk/mysql /var/lib/mysql
sudo start mysql
	
# setup GLPI and its plugins
mysql -u root -e 'create database $DBNAME;'
rm -f composer.lock
tests/config-composer.sh
git clone --depth=1 $GLPI_SOURCE -b $GLPI_BRANCH ../glpi && cd ../glpi
composer install --no-dev --no-interaction
if [ -e scripts/cliinstall.php ] ; then php scripts/cliinstall.php --db=glpitest --user=root --tests ; fi
if [ -e tools/cliinstall.php ] ; then php tools/cliinstall.php --db=glpitest --user=root --tests ; fi
mkdir plugins/fusioninventory && git clone --depth=1 $FI_SOURCE -b $FI_BRANCH plugins/fusioninventory
IFS=/ read -a repo <<< $TRAVIS_REPO_SLUG
mv ../${repo[1]} plugins/flyvemdm

# patch GLPI when needed
if [[ $GLPI_BRANCH == "9.2.1" ]] ; then patch -p1 --batch < plugins/flyvemdm/tests/patches/10f8dabfc5e20bb5a4e7d4ba4b93706871156a8a.diff; fi

# prepare plugin to test
cd plugins/flyvemdm 
composer install --no-interaction

