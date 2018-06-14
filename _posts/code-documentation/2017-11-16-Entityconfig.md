---
layout: code-documentation
code: true
howtos: false
published: true
title: Plugin Flyve MDM Entity Config
permalink: development/devdocs/PluginFlyvemdmEntityconfig
---

* Class name: PluginFlyvemdmEntityconfig
* Namespace:
* Parent class: CommonDBTM



## Constants



### RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT

    const RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT = 128





### RIGHT_FLYVEMDM_APP_DOWNLOAD_URL

    const RIGHT_FLYVEMDM_APP_DOWNLOAD_URL = 256





### RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE

    const RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE = 512





### CONFIG_DEFINED

    const CONFIG_DEFINED = -3





### CONFIG_PARENT

    const CONFIG_PARENT = -2

## Properties



### $dohistory

    public boolean $dohistory = true





* Visibility: **public**


### $rightname

    public mixed $rightname = 'flyvemdm:entity'





* Visibility: **public**
* This property is **static**.

## Methods



### getTypeName

    mixed PluginFlyvemdmEntityconfig::getTypeName(integer $nb)

Returns the name of the type



* Visibility: **public**
* This method is **static**.


#### Arguments
* $nb **integer** - number of item in the type



### canUpdate

    Boolean PluginFlyvemdmEntityconfig::canUpdate()





* Visibility: **public**
* This method is **static**.




### post_getFromDB

    mixed PluginFlyvemdmEntityconfig::post_getFromDB()

Actions done after the getFromDB method



* Visibility: **public**




### prepareInputForAdd

    mixed PluginFlyvemdmEntityconfig::prepareInputForAdd($input)





* Visibility: **public**


#### Arguments
* $input **mixed**



### prepareInputForUpdate

    mixed PluginFlyvemdmEntityconfig::prepareInputForUpdate($input)




* Visibility: **public**


#### Arguments
* $input **mixed**



### sanitizeTokenLifeTime

    array|false PluginFlyvemdmEntityconfig::sanitizeTokenLifeTime(string $input)

Sanitizes the token life time of the agent



* Visibility: **protected**


#### Arguments
* $input **string**



### hook_entity_add

    mixed PluginFlyvemdmEntityconfig::hook_entity_add(\CommonDBTM $item)

create folders and initial setup of the entity related to MDM


* Visibility: **public**


#### Arguments
* $item **CommonDBTM**



### hook_entity_purge

    mixed PluginFlyvemdmEntityconfig::hook_entity_purge(\CommonDBTM $item)

Cleanup MDM related data for the entity being deleted



* Visibility: **public**


#### Arguments
* $item **CommonDBTM**



### setEnrollToken

    mixed PluginFlyvemdmEntityconfig::setEnrollToken()

Generate a displayable token for enrollment



* Visibility: **protected**




### getFromDBOrCreate

    boolean PluginFlyvemdmEntityconfig::getFromDBOrCreate(string $ID)

Retrieve the entity or create it



* Visibility: **public**


#### Arguments
* $ID **string**



### getTabNameForItem

    array PluginFlyvemdmEntityconfig::getTabNameForItem(\CommonGLPI $item, integer $withtemplate)

Gets the tabs name



* Visibility: **public**


#### Arguments


   *  $item **CommonGLPI**
   *  $withtemplate **integer** - if it is showed with a template (default 0)


### getUsedConfig

    mixed PluginFlyvemdmEntityconfig::getUsedConfig($fieldref, $entities_id, $fieldval, $default_value)

Retrieve data of current entity or parent entity



* Visibility: **public**
* This method is **static**.


#### Arguments


   * $fieldref **mixed** - string   name of the referent field to know if we look at parent entity
   * $entities_id **mixed**
   * $fieldval **mixed** - string   name of the field that we want value (default &#039;&#039;)
   * $default_value **mixed** - value to return (default -2)


### displayTabContentForItem

    mixed PluginFlyvemdmEntityconfig::displayTabContentForItem(\CommonGLPI $item, integer $tabnum, integer $withtemplate)

Shows the tab content



* Visibility: **public**
* This method is **static**.


#### Arguments
* $item **CommonGLPI**
* $tabnum **integer**
* $withtemplate **integer**



### isNewID

    boolean PluginFlyvemdmEntityconfig::isNewID(integer $ID)

is the parameter ID must be considered as new one ?



* Visibility: **public**
* This method is **static**.


#### Arguments
* $ID **integer** - ID of the item (-1 if new item)



### showFormForEntity

    mixed PluginFlyvemdmEntityconfig::showFormForEntity(\Entity $item)

Displays form when the item is displayed from a related entity



* Visibility: **public**


#### Arguments
* $item **Entity**



### getSearchOptions

    mixed PluginFlyvemdmEntityconfig::getSearchOptions()





* Visibility: **public**
