---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Notifiable
permalink: development/devdocs/pluginflyvemdmnotifiable
---

* Interface name: PluginFlyvemdmNotifiable
* Namespace: 
* This is an **interface**






Methods
-------


### getTopic

    mixed PluginFlyvemdmNotifiable::getTopic()

Gets the topic related to the notifiable



* Visibility: **public**




### getAgents

    array PluginFlyvemdmNotifiable::getAgents()

get the agents related to the notifiable



* Visibility: **public**




### getFleet

    \PluginFlyvemdmFleet PluginFlyvemdmNotifiable::getFleet()

get the fleet attached to the notifiable



* Visibility: **public**




### getPackages

    array PluginFlyvemdmNotifiable::getPackages()

get the applications related to the notifiable



* Visibility: **public**




### getFiles

    array PluginFlyvemdmNotifiable::getFiles()

get the files related to the notifiable



* Visibility: **public**




### notify

    mixed PluginFlyvemdmNotifiable::notify(string $topic, string $mqttMessage, \number $qos, \number $retain)

Send a MQTT message



* Visibility: **public**


#### Arguments
* $topic **string**
* $mqttMessage **string**
* $qos **number**
* $retain **number**


