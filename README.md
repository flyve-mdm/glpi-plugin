[![Build Status](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi.svg?branch=master)](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi)

INSTALLATION
============

Dependencies
------------

This plugin is depends on GLPi, FusionIvnentory for GLPi and a some packages

* Download GLPi (please refer to its documentation)
* Download FusionInventory for GLPi and put it in glpi/plugins/
* Donwload Storkmdm for GLPi and put it in glpi/plugins/

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
Run composer instal --no-dev


Security
--------
The directory glpi/plugins/storkmdm/scripts must be inaccessible from the webserver.

* If running Apache, the .htaccess file in this directory will do the job.
* If running an other server like Nginx, please configure the host properly. 

TEST
============

Go to the folder containing GLPi
Run php tools/cliinstall.php --tests --user=database-user --pass=<database-pass --db=glpi-test
TODO : Installation in a test database for FusionInventory
Go to glpi/plugins/storkmdm
Run php tools/cliinstall.php --tests
Run phpunit


CONFIGURATION
=============

Login as the user glpi and open the menu 'Configuration' > 'Notifications'. Enable the email notifications.