---
Title:       Content of the folder  
Subtitle:    Describe the purpose of each file  
Project:     Flyve MDM plugin for GLPI
Author:      Thierry Bugier <tbugier@teclib.com> 
Contributor: Domingo Oropeza <doropeza@teclib.com>
---

* README.md                              : This file
* PHPStorm-GLPI-coding-convention.xml    : GLPI coding conventions for PHPStorm IDE
* eclipse-PDT-GLPI-coding-convention.xml : GLPI coding conventions for Eclipse PDT IDE
* cli_install.php                        : PHP script to install Flyve MDM plugin for GLPI
* HEADER                                 : Template of source code header used by Robo.li script

## Importing the coding convention for PHPStorm

* Open PHPStorm IDE.
* Open the `File` menu and select the `Settings` option, on the new window follow this path `Editor > Code Style > PHP`.
* Click on the gear icon next to `Scheme` option and select `Import Scheme > Intellij IDEA code style XML`.
* Find and choose the `PHPStorm-GLPI-coding-convention.xml` file then click OK.
* Confirm the import action on the confirmation window without changing anything.
* Use it either in the project or on the whole workspace (depending on your other projects).

## Importing the coding convention for Eclipse PDT

* Open Eclipse PDT.
* Open the menu and follow this path `Window > Preference > PHP > Code Style > Formatter`.
* Click the import button and choose `eclipse-PDT-GLPI-coding-convention.xml` in this project.
* Use it either in the project or on the whole workspace (depending on your other projects).