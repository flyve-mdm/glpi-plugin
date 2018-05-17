---
layout: post
code: true
howtos: false
published: true
title: Plugin Flyve MDM Mqttacl
permalink: development/devdocs/PluginFlyvemdmMqttacl
---

* Class name: PluginFlyvemdmMqttacl
* Namespace: 
* Parent class: CommonDBTM



Constants
----------


### MQTTACL_NONE

    const MQTTACL_NONE = 0





### MQTTACL_READ

    const MQTTACL_READ = 1





### MQTTACL_WRITE

    const MQTTACL_WRITE = 2





### MQTTACL_READ_WRITE

    const MQTTACL_READ_WRITE = 3





### MQTTACL_ALL

    const MQTTACL_ALL = 3







Methods
-------


### removeAllForUser

    mixed PluginFlyvemdmMqttacl::removeAllForUser(\PluginFlyvemdmMQTTUser $mqttUser)

Delete all MQTT ACLs for the MQTT user



* Visibility: **public**


#### Arguments
* $mqttUser **PluginFlyvemdmMQTTUser**



### prepareInputForAdd

    mixed PluginFlyvemdmMqttacl::prepareInputForAdd($input)





* Visibility: **public**


#### Arguments
* $input **mixed**


