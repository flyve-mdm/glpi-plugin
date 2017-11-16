---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Mqtt Log
permalink: development/devdocs/pluginflyvemdmmqttlog
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
* $nb **mixed** - &lt;p&gt;integer  number of item in the type (default 0)&lt;/p&gt;



### saveIngoingMqttMessage

    mixed PluginFlyvemdmMqttlog::saveIngoingMqttMessage(String $topic, String $msg)

Save in DB an incoming MQTT message



* Visibility: **public**


#### Arguments
* $topic **String** - &lt;p&gt;topic&lt;/p&gt;
* $msg **String** - &lt;p&gt;Message&lt;/p&gt;



### saveOutgoingMqttMessage

    mixed PluginFlyvemdmMqttlog::saveOutgoingMqttMessage(array $topicList, String $msg)

Save in the DB an outgoing MQTT message



* Visibility: **public**


#### Arguments
* $topicList **array** - &lt;p&gt;array of topics. String allowed for a single topic&lt;/p&gt;
* $msg **String** - &lt;p&gt;Message&lt;/p&gt;



### saveMqttMessage

    mixed PluginFlyvemdmMqttlog::saveMqttMessage(String $direction, array $topicList, String $msg)

Save MQTT messages sent or received



* Visibility: **protected**


#### Arguments
* $direction **String** - &lt;p&gt;I for input O for output&lt;/p&gt;
* $topicList **array** - &lt;p&gt;array of topics. String allowed for a single topic&lt;/p&gt;
* $msg **String** - &lt;p&gt;Message&lt;/p&gt;


