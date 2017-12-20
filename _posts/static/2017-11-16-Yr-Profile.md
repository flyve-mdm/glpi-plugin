---
layout: post
code: true
howtos: false
published: true
title: Plugin Flyve MDM Profile
permalink: development/devdocs/PluginFlyvemdmProfile
---

* Class name: PluginFlyvemdmProfile
* Namespace: 
* Parent class: Profile



Constants
----------


### RIGHT_FLYVEMDM_USE

    const RIGHT_FLYVEMDM_USE = 128





Properties
----------


### $rightname

    public string $rightname = 'flyvemdm:flyvemdm'





* Visibility: **public**
* This property is **static**.


Methods
-------


### purgeProfiles

    mixed PluginFlyvemdmProfile::purgeProfiles(\Profile $prof)

Deletes the profiles related to the ones being purged



* Visibility: **public**
* This method is **static**.


#### Arguments
* $prof **Profile**



### showForm

    mixed PluginFlyvemdmProfile::showForm($ID, $options)





* Visibility: **public**


#### Arguments
* $ID **mixed**
* $options **mixed**



### getTabNameForItem

    mixed PluginFlyvemdmProfile::getTabNameForItem(\CommonGLPI $item, $withtemplate)





* Visibility: **public**


#### Arguments
* $item **CommonGLPI**
* $withtemplate **mixed**



### displayTabContentForItem

    boolean PluginFlyvemdmProfile::displayTabContentForItem(\CommonGLPI $item, \number $tabnum, \number $withtemplate)





* Visibility: **public**
* This method is **static**.


#### Arguments
* $item **CommonGLPI**
* $tabnum **number**
* $withtemplate **number**



### getGeneralRights

    \array:array:string PluginFlyvemdmProfile::getGeneralRights()

Get rights matrix for plugin



* Visibility: **public**




### getAssetsRights

    \array:array:string PluginFlyvemdmProfile::getAssetsRights()

Get rights matrix for plugin's assets



* Visibility: **public**




### changeProfile

    mixed PluginFlyvemdmProfile::changeProfile()

Callback when a user logins or switch profile



* Visibility: **public**
* This method is **static**.



