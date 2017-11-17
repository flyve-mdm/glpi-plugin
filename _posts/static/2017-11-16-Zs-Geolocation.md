---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Geolocation
permalink: development/devdocs/PluginFlyvemdmGeolocation
---

* Class name: PluginFlyvemdmGeolocation
* Namespace: 
* Parent class: CommonDBTM





## Properties



### $rightname

    public mixed $rightname = 'flyvemdm:geolocation'





* Visibility: **public**
* This property is **static**.


### $dohistory

    public boolean $dohistory = false





* Visibility: **public**


### $usenotepad

    protected boolean $usenotepad = true





* Visibility: **protected**


### $usenotepadRights

    protected boolean $usenotepadRights = true





* Visibility: **protected**


### $types

    public mixed $types = array('Computer')





* Visibility: **public**
* This property is **static**.

## Methods



### getTypeName

    mixed PluginFlyvemdmGeolocation::getTypeName($nb)

Localized name of the type



* Visibility: **public**
* This method is **static**.


#### Arguments
* $nb **mixed** - integer number of item in the type (default 0)



### getTabNameForItem

    mixed PluginFlyvemdmGeolocation::getTabNameForItem(\CommonGLPI $item, $withtemplate)





* Visibility: **public**


#### Arguments
* $item **CommonGLPI**
* $withtemplate **mixed**



### displayTabContentForItem

    mixed PluginFlyvemdmGeolocation::displayTabContentForItem($item, $tabnum, $withtemplate)





* Visibility: **public**
* This method is **static**.


#### Arguments
* $item **mixed** - CommonGLPI object
* $tabnum **mixed** - (default 1)
* $withtemplate **mixed** - (default 0)



### getRights

    mixed PluginFlyvemdmGeolocation::getRights($interface)





* Visibility: **public**


#### Arguments
* $interface **mixed**



### prepareInputForAdd

    mixed PluginFlyvemdmGeolocation::prepareInputForAdd($input)





* Visibility: **public**


#### Arguments
* $input **mixed**



### prepareInputForUpdate

    array|false PluginFlyvemdmGeolocation::prepareInputForUpdate(array $input)

Prepares data before update



* Visibility: **public**


#### Arguments
* $input **array**



### addDefaultJoin

    mixed PluginFlyvemdmGeolocation::addDefaultJoin()





* Visibility: **public**
* This method is **static**.




### addDefaultWhere

    mixed PluginFlyvemdmGeolocation::addDefaultWhere()





* Visibility: **public**
* This method is **static**.




### showForAgent

    string PluginFlyvemdmGeolocation::showForAgent(\CommonDBTM $item)

Displays the agents according the datetime



* Visibility: **public**
* This method is **static**.


#### Arguments
* $item **CommonDBTM**



### getSearchOptions

    mixed PluginFlyvemdmGeolocation::getSearchOptions()





* Visibility: **public**




### hook_computer_purge

    mixed PluginFlyvemdmGeolocation::hook_computer_purge(\CommonDBTM $item)

Deletes the geolocation related with the computer



* Visibility: **public**


#### Arguments
* $item **CommonDBTM**


