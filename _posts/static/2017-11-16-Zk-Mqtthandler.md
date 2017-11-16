---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Mqtt Handler
permalink: development/devdocs/pluginflyvemdmmqtthandler
---

* Class name: PluginFlyvemdmMqtthandler
* Namespace: 
* Parent class: sskaje\mqtt\MessageHandler





Properties
----------


### $log

    protected \PluginFlyvemdmMqttlog $log





* Visibility: **protected**


### $startTime

    protected integer $startTime





* Visibility: **protected**


### $flyveManifestMissing

    protected mixed $flyveManifestMissing = true





* Visibility: **protected**


### $publishedVersion

    protected mixed $publishedVersion = null





* Visibility: **protected**


### $instance

    protected mixed $instance = null





* Visibility: **protected**
* This property is **static**.


Methods
-------


### __construct

    mixed PluginFlyvemdmMqtthandler::__construct()





* Visibility: **protected**




### getInstance

    \the PluginFlyvemdmMqtthandler::getInstance()

Gets the instance of the PluginFlyvemdmMqtthandler



* Visibility: **public**
* This method is **static**.




### publishManifest

    mixed PluginFlyvemdmMqtthandler::publishManifest(\sskaje\mqtt\MQTT $mqtt)

Maintains a MQTT topic to publish the current version of the backend



* Visibility: **protected**


#### Arguments
* $mqtt **sskaje\mqtt\MQTT**



### pingresp

    mixed PluginFlyvemdmMqtthandler::pingresp(\sskaje\mqtt\MQTT $mqtt, \sskaje\mqtt\Message\PINGRESP $pingresp_object)

Handle MQTT Ping response



* Visibility: **public**


#### Arguments
* $mqtt **sskaje\mqtt\MQTT**
* $pingresp_object **sskaje\mqtt\Message\PINGRESP**



### publish

    mixed PluginFlyvemdmMqtthandler::publish(\sskaje\mqtt\MQTT $mqtt, \sskaje\mqtt\Message\PUBLISH $publish_object)

Handle MQTT publish messages



* Visibility: **public**


#### Arguments
* $mqtt **sskaje\mqtt\MQTT**
* $publish_object **sskaje\mqtt\Message\PUBLISH**



### updateAgentVersion

    mixed PluginFlyvemdmMqtthandler::updateAgentVersion(string $topic, string $message)

Update the version of an agent



* Visibility: **protected**


#### Arguments
* $topic **string**
* $message **string**



### publishFlyveManifest

    mixed PluginFlyvemdmMqtthandler::publishFlyveManifest()

Publishes the current version of Flyve



* Visibility: **protected**




### updateInventory

    mixed PluginFlyvemdmMqtthandler::updateInventory(string $topic, string $message)

Updates the inventory



* Visibility: **protected**


#### Arguments
* $topic **string**
* $message **string**



### updateLastContact

    mixed PluginFlyvemdmMqtthandler::updateLastContact(string $topic, string $message)

Updates the last contact of the agent

The data to update is a datetime

* Visibility: **protected**


#### Arguments
* $topic **string**
* $message **string**



### deleteAgent

    mixed PluginFlyvemdmMqtthandler::deleteAgent(string $topic, string $message)

Deletes the agent



* Visibility: **protected**


#### Arguments
* $topic **string**
* $message **string**



### saveGeolocationPosition

    mixed PluginFlyvemdmMqtthandler::saveGeolocationPosition(string $topic, string $message)

Saves geolocation position



* Visibility: **protected**


#### Arguments
* $topic **string**
* $message **string**



### updateTaskStatus

    mixed PluginFlyvemdmMqtthandler::updateTaskStatus(string $topic, $message)

Update the status of a task from a notification sent by a device



* Visibility: **protected**


#### Arguments
* $topic **string**
* $message **mixed**



### updateOnlineStatus

    mixed PluginFlyvemdmMqtthandler::updateOnlineStatus(string $topic, $message)

Update the status of a task from a notification sent by a device



* Visibility: **protected**


#### Arguments
* $topic **string**
* $message **mixed**


