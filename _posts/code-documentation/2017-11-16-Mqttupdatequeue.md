---
layout: code-documentation
code: true
howtos: false
published: true
title: Plugin Flyve MDM Update Queue
permalink: development/devdocs/PluginFlyvemdmMqttupdatequeue
---

* Class name: PluginFlyvemdmMqttupdatequeue
* Namespace:
* Parent class: CommonDBTM





Properties
----------


### $delay

    protected mixed $delay = 'PT30S'





* Visibility: **protected**
* This property is **static**.


Methods
-------


### prepareInputForAdd

    array PluginFlyvemdmMqttupdatequeue::prepareInputForAdd(array $input)

Prepares data before adding the item



* Visibility: **public**


#### Arguments
* $input **array**



### setDelay

    mixed PluginFlyvemdmMqttupdatequeue::setDelay(mixed $delay)

Sets the delay



* Visibility: **public**
* This method is **static**.


#### Arguments
* $delay **mixed**



### getTypeName

    mixed PluginFlyvemdmMqttupdatequeue::getTypeName(integer $count)

Returns the name of the type



* Visibility: **public**
* This method is **static**.


#### Arguments
* $count **integer**



### cronInfo

    array PluginFlyvemdmMqttupdatequeue::cronInfo($name)

get Cron description parameter for this class



* Visibility: **public**
* This method is **static**.


#### Arguments
* $name **mixed** - string name of the task



### cronUpdateTopics

    integer PluginFlyvemdmMqttupdatequeue::cronUpdateTopics($cronTask)

Update MQTT topics in the update queue



* Visibility: **public**
* This method is **static**.


#### Arguments
* $cronTask **mixed**
