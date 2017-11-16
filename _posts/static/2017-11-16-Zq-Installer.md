---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Installer
permalink: development/devdocs/pluginflyvemdminstaller
---

* Class name: PluginFlyvemdmInstaller
* Namespace: 

## Constants



### DEFAULT_CIPHERS_LIST

    const DEFAULT_CIPHERS_LIST = 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:AES128:AES256:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK'





### BACKEND_MQTT_USER

    const BACKEND_MQTT_USER = 'flyvemdm-backend'

## Properties



### $itemtypesToInstall

    protected mixed $itemtypesToInstall = array('mqttuser', 'mqttacl', 'config', 'entityconfig', 'mqttlog', 'agent', 'package', 'file', 'fleet', 'profile', 'notificationtargetinvitation', 'geolocation', 'policy', 'policycategory', 'fleet_policy', 'wellknownpath', 'invitation', 'invitationlog')





* Visibility: **protected**
* This property is **static**.


### $currentVersion

    protected mixed $currentVersion = null





* Visibility: **protected**
* This property is **static**.


### $migration

    protected mixed $migration





* Visibility: **protected**


## Methods



### autoload

    mixed PluginFlyvemdmInstaller::autoload($classname)

Autoloader for installation



* Visibility: **public**


#### Arguments
* $classname **mixed**



### install

    boolean PluginFlyvemdmInstaller::install()

Install the plugin



* Visibility: **public**




### getOrCreateProfile

    integer PluginFlyvemdmInstaller::getOrCreateProfile(string $name, string $comment)

Find a profile having the given comment, or create it



* Visibility: **protected**
* This method is **static**.


#### Arguments
* $name **string** - &lt;p&gt;Name of the profile&lt;/p&gt;
* $comment **string** - &lt;p&gt;Comment of the profile&lt;/p&gt;



### createDirectories

    mixed PluginFlyvemdmInstaller::createDirectories()





* Visibility: **public**




### getCurrentVersion

    mixed PluginFlyvemdmInstaller::getCurrentVersion()





* Visibility: **public**
* This method is **static**.




### createRootEntityConfig

    mixed PluginFlyvemdmInstaller::createRootEntityConfig()





* Visibility: **protected**




### createFirstAccess

    mixed PluginFlyvemdmInstaller::createFirstAccess()

Give all rights on the plugin to the profile of the current user



* Visibility: **protected**




### createDefaultFleet

    mixed PluginFlyvemdmInstaller::createDefaultFleet()





* Visibility: **protected**




### createGuestProfileAccess

    mixed PluginFlyvemdmInstaller::createGuestProfileAccess()

Create a profile for guest users



* Visibility: **protected**




### createAgentProfileAccess

    mixed PluginFlyvemdmInstaller::createAgentProfileAccess()

Create a profile for agent user accounts



* Visibility: **protected**




### createPolicies

    mixed PluginFlyvemdmInstaller::createPolicies()

Create policies in DB



* Visibility: **protected**




### getNotificationTargetInvitationEvents

    mixed PluginFlyvemdmInstaller::getNotificationTargetInvitationEvents()





* Visibility: **protected**




### createNotificationTargetInvitation

    mixed PluginFlyvemdmInstaller::createNotificationTargetInvitation()





* Visibility: **public**




### upgrade

    mixed PluginFlyvemdmInstaller::upgrade(string $fromVersion)

Upgrade the plugin to the current code version



* Visibility: **protected**


#### Arguments
* $fromVersion **string**



### upgradeOneStep

    mixed PluginFlyvemdmInstaller::upgradeOneStep(string $toVersion)

Proceed to upgrade of the plugin to the given version



* Visibility: **protected**


#### Arguments
* $toVersion **string**



### createJobs

    mixed PluginFlyvemdmInstaller::createJobs()





* Visibility: **protected**




### startsWith

    mixed PluginFlyvemdmInstaller::startsWith(string $haystack, string $needle)

http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php



* Visibility: **protected**


#### Arguments
* $haystack **string**
* $needle **string**



### endsWith

    mixed PluginFlyvemdmInstaller::endsWith(string $haystack, string $needle)

http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php



* Visibility: **protected**


#### Arguments
* $haystack **string**
* $needle **string**



### uninstall

    boolean PluginFlyvemdmInstaller::uninstall()

Uninstall the plugin



* Visibility: **public**




### rrmdir

    mixed PluginFlyvemdmInstaller::rrmdir(string $dir)

Cannot use the method from PluginFlyvemdmToolbox if the plugin is being uninstalled



* Visibility: **protected**


#### Arguments
* $dir **string**



### createInitialConfig

    mixed PluginFlyvemdmInstaller::createInitialConfig()

Generate default configuration for the plugin



* Visibility: **protected**




### createBackendMqttUser

    mixed PluginFlyvemdmInstaller::createBackendMqttUser(string $MdmMqttUser, string $MdmMqttPassword)

Create MQTT user for the backend and save credentials



* Visibility: **protected**


#### Arguments
* $MdmMqttUser **string**
* $MdmMqttPassword **string**



### convertTextToHtml

    mixed PluginFlyvemdmInstaller::convertTextToHtml(string $text)

Generate HTML version of a text
Replaces \n by <br>
Encloses the text un <p>.

..</p>
Add anchor to URLs

* Visibility: **protected**
* This method is **static**.


#### Arguments
* $text **string**



### getPolicyCategories

    mixed PluginFlyvemdmInstaller::getPolicyCategories()





* Visibility: **public**
* This method is **static**.




### getPolicies

    array PluginFlyvemdmInstaller::getPolicies()





* Visibility: **public**
* This method is **static**.




### deleteNotificationTargetInvitation

    mixed PluginFlyvemdmInstaller::deleteNotificationTargetInvitation()





* Visibility: **protected**




### deleteTables

    mixed PluginFlyvemdmInstaller::deleteTables()





* Visibility: **protected**




### deleteProfiles

    mixed PluginFlyvemdmInstaller::deleteProfiles()





* Visibility: **protected**




### deleteProfileRights

    mixed PluginFlyvemdmInstaller::deleteProfileRights()





* Visibility: **protected**




### deleteRelations

    mixed PluginFlyvemdmInstaller::deleteRelations()





* Visibility: **protected**




### deleteDisplayPreferences

    mixed PluginFlyvemdmInstaller::deleteDisplayPreferences()





* Visibility: **protected**



