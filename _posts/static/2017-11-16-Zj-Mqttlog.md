---
layout: post
code: true
howtos: false
published: true
title: Plugin Flyve MDM Mqtt Log
permalink: development/devdocs/PluginFlyvemdmMqttlog
---

* Class name: PluginFlyvemdmMqttlog
* Namespace: 
* Parent class: CommonDBTM



Constants
----------


### MQTT_MAXIMUM_DURATION

    const MQTT_MAXIMUM_DURATION = 60







Methods
-------


### __construct

    mixed PluginFlyvemdmMqttlog::__construct()





* Visibility: **public**




### getTypeName

    mixed PluginFlyvemdmMqttlog::getTypeName($nb)

Name of the type



* Visibility: **public**
* This method is **static**.


#### Arguments
* $nb **mixed** - integer  number of item in the type (default 0)



### saveIngoingMqttMessage

    mixed PluginFlyvemdmMqttlog::saveIngoingMqttMessage(String $topic, String $msg)

Save in DB an incoming MQTT message



* Visibility: **public**


#### Arguments
* $topic **String** - topic
* $msg **String** - Message



### saveOutgoingMqttMessage

    mixed PluginFlyvemdmMqttlog::saveOutgoingMqttMessage(array $topicList, String $msg)

Save in the DB an outgoing MQTT message



* Visibility: **public**


#### Arguments
* $topicList **array** - array of topics. String allowed for a single topic
* $msg **String** - Message



### saveMqttMessage

    mixed PluginFlyvemdmMqttlog::saveMqttMessage(String $direction, array $topicList, String $msg)

Save MQTT messages sent or received



* Visibility: **protected**


#### Arguments
* $direction **String** - I for input O for output
* $topicList **array** - array of topics. String allowed for a single topic
* $msg **String** - Message
