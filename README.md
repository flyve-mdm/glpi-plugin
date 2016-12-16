[![Build Status](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi.svg?branch=master)](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi)

INSTALLATION
============

Dependencies
------------

This plugin is depends on GLPi, FusionIvnentory for GLPi and a some packages

* Download GLPi 9.1.1 or later (please refer to its documentation)
* Download FusionInventory 9.1+1.0 for GLPi and put it in glpi/plugins/
* Donwload Flyve MDM for GLPi and put it in glpi/plugins/

You should have a directory structure like this :

```
glpi
|
+ plugins
  |
  + fusioninventory
  + storkmdm
```

Go in the directory  glpi/plugins/storkmdm 
Run composer install --no-dev


Security
--------
FlyveMDM needs only the REST API feature of GLPi to work with devices and its web interface.
Expose to the world only the API of GLPi, and keep inacessible GLPi's user interface.

The directory glpi/plugins/storkmdm/scripts must be inaccessible from the webserver.

* If running Apache, the .htaccess file in this directory will do the job.
* If running an other server like Nginx, please configure the host properly. 

TEST
====

Go to the folder containing GLPi
Run composer install
Run php tools/cliinstall.php --tests --user=database-user --pass=database-pass --db=glpi-test
Go to plugins/storkmdm
Run php tools/cliinstall.php --tests
Run phpunit


CONFIGURATION
=============

Login as the user glpi and open the menu 'Configuration' > 'Notifications'. Enable the email notifications.

If you are using development version of Fusioninventory, you need to disable the rule *Computer constraint (name)* in import rules.