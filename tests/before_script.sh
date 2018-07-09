#!/bin/bash

#
# Before script for Travis CI
#

# config composer
if [ "$TRAVIS_SECURE_ENV_VARS" = "true" ]; then
  mkdir ~/.composer -p
  touch ~/.composer/composer.json
  composer config -g github-oauth.github.com $GH_OAUTH
fi

# Make a ramdisk for mysql (speed improvement)
sudo mkdir /mnt/ramdisk
sudo mount -t tmpfs -o size=1024m tmpfs /mnt/ramdisk
sudo stop mysql
sudo mv /var/lib/mysql /mnt/ramdisk
sudo ln -s /mnt/ramdisk/mysql /var/lib/mysql
sudo start mysql

# setup GLPI and its plugins
mysql -u root -e 'create database $DBNAME;'
git clone --depth=35 $GLPI_SOURCE -b $GLPI_BRANCH ../glpi && cd ../glpi
composer install --no-dev --no-interaction
if [ -e scripts/cliinstall.php ] ; then php scripts/cliinstall.php --db=glpitest --user=root --tests ; fi
if [ -e tools/cliinstall.php ] ; then php tools/cliinstall.php --db=glpitest --user=root --tests ; fi
mkdir plugins/fusioninventory && git clone --depth=35 $FI_SOURCE -b $FI_BRANCH plugins/fusioninventory
IFS=/ read -a repo <<< $TRAVIS_REPO_SLUG
mv ../${repo[1]} plugins/flyvemdm
cd plugins/fusioninventory
if [[ $FI_BRANCH == "glpi9.2+1.0" ]] ; then patch -p1 < ../flyvemdm/tests/patches/fi-fix-obsolete-query.patch; fi
if [[ $FI_BRANCH == "master" ]] ; then patch -p1 < ../flyvemdm/tests/patches/fi-raise-max-version.patch; fi
cd ../..

# patch GLPI when needed
if [[ $GLPI_BRANCH == "9.2.1" ]] ; then patch -p1 --batch < plugins/flyvemdm/tests/patches/10f8dabfc5e20bb5a4e7d4ba4b93706871156a8a.diff; fi

# prepare plugin to test
cd plugins/flyvemdm
if [[ $GLPI_BRANCH == "master" ]] ; then patch -p1 --batch < tests/patches/glpi-allow-test-on-master-branch.patch; fi
composer install --no-interaction

