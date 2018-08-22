---
layout: static-docs
code: false
howtos: true
published: false
title: Upgrade Flyve MDM Plugin
permalink: howtos/upgrade-glpi-plugin
description: Stay up-to-date
category: user
---
# Before Updating

Remember to:

* Check the compatibility matrix of your GLPI version with Flyve MDM Plugin before any update
* Make a backup of your database
* Make a backup of the files of GLPI

## Compatibility Matrix

<br>

<table>
    <tr>
        <td style="width:180px">GLPI</td>
        <td style="width:100px">9.1.x</td>
        <td>9.2.x</td>
    </tr>
    <tr>
        <td><b>Flyve MDM</b></td>
        <td>1.x.x</td>
        <td>2.0.0-dev</td>
    </tr>
    <tr>
        <td><b>FusionInventory</b></td>
        <td>9.1+1.0</td>
        <td>9.2+1.0</td>
    </tr>
    <tr>
        <td><b>Flyve MDM Demo</b></td>
        <td>-</td>
        <td>1.0.0-dev</td>
    </tr>
    <tr>
        <td><b>Web MDM Dashboard</b></td>
        <td>-</td>
        <td>1.0.0-dev</td>
    </tr>
</table>

## From one release to another

* Download the new version of the plugin
* Go to glpi plugins directory
* Move the folder of Flyve MDM plugin out of the GLPI subtree
* Install the new version of the plugin (this one should be named flyvemdm)
* Change the current directory to the content of flyve MDM plugin ```cd glpi/plugins/flyvemdm```
* Run ```php tools/cli_install.php``` in order to update your database
* Enable the plugin again with the user interface of GLPI
* Check in the file glpi/plugins/flyvemdm/scripts/service.sh that the user of the daemon is the same as your HTTP server (www-data for debian based systems).
* If you need to change the user, please run ```update-rc.d flyvemdm defaults```
* Restart the daemon ```service flyvemdm restart```

After you successfully confirm everything is as it should, delete the old version you moved out of GLPI

## Using Git

If you installed the Flyve MDM plugin through git:

* Go to the Flyve MDM plugin's directory
* Use ```git pull``` to update your local branch
* Run ```php tools/cli_install.php``` to update the database
* Enable the plugin again with the user interface of GLPI
* Check in the file glpi/plugins/flyvemdm/scripts/service.sh that the user of the daemon is the same as your HTTP server (www-data for debian based systems).
* If you need to change the user, please run ```update-rc.d flyvemdm defaults```
* Restart the daemon ```service flyvemdm restart```

You can use this method to upgrade the plugin from a revision to another revision on the develop branch.
