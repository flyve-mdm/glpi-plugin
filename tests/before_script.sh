#!/bin/bash

#
# Before script for Travis CI
#

# defined in travis.yml
# DBNAME      : database name for tests
# OLDDBNAME   : database name for upgrade test of the plugin
# GLPI_SOURCE : URL to GLPI GIT repository
# GLPI_BRANCH : branch of GLPI to test with the project
# FI_SOURCE   : URL to Fusion Inventory GIT repository
# FI_BRANCH   : branch of Fusion Inventory to test with the project

# defined by Travis CI
# TRAVIS_REPO_SLUG : see Travis CI: https://docs.travis-ci.com/user/environment-variables

# defined in travis settings / environment variables
# GH_OAUTH

# config composer
if [ "$TRAVIS_SECURE_ENV_VARS" = "true" ]; then
  mkdir ~/.composer -p
  touch ~/.composer/composer.json
  composer config -g github-oauth.github.com $GH_OAUTH
fi

# setup GLPI and its plugins
mysql -u root -e 'create database $DBNAME;'
mysql -u root -e 'create database $OLDDBNAME;'
git clone --depth=35 $GLPI_SOURCE -b $GLPI_BRANCH ../glpi && cd ../glpi
composer install --no-dev --no-interaction
mkdir plugins/fusioninventory && git clone --depth=35 $FI_SOURCE -b $FI_BRANCH plugins/fusioninventory
IFS=/ read -a repo <<< $TRAVIS_REPO_SLUG
mv ../${repo[1]} plugins/flyvemdm

# patch Fusion Inventory when needed
cd plugins/fusioninventory
if [[ $FI_BRANCH == "master" ]] ; then patch -p1 --batch < ../flyvemdm/tests/patches/fusioninventory/fi-raise-max-version.patch; fi
if [[ $FI_BRANCH == "master" ]] ; then patch -p1 --batch < ../flyvemdm/tests/patches/fusioninventory/compat-glpi-9-3-2.diff; fi
if [[ $FI_BRANCH == "glpi9.3" ]] ; then patch -p1 --batch < ../flyvemdm/tests/patches/fusioninventory/compat-glpi-9-3-2.diff; fi
cd ../..

# patch GLPI when needed
if [[ $GLPI_BRANCH == "9.2.1" ]] ; then patch -p1 --batch < plugins/flyvemdm/tests/patches/glpi/10f8dabfc5e20bb5a4e7d4ba4b93706871156a8a.diff; fi

# prepare plugin to test
cd plugins/flyvemdm
if [[ $GLPI_BRANCH == "master" ]] ; then patch -p1 --batch < tests/patches/allow-test-on-master-branch.patch; fi
composer install --no-interaction

