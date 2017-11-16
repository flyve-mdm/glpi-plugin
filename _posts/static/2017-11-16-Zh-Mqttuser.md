---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Mqtt User
permalink: development/devdocs/pluginflyvemdmmqttuser
---

* Class name: PluginFlyvemdmMqttuser
* Namespace: 
* Parent class: CommonDBTM







Methods
-------


### prepareInputForAdd

    mixed PluginFlyvemdmMqttuser::prepareInputForAdd($input)





* Visibility: **public**


#### Arguments
* $input **mixed**



### prepareInputForUpdate

    mixed PluginFlyvemdmMqttuser::prepareInputForUpdate($input)





* Visibility: **public**


#### Arguments
* $input **mixed**



### post_addItem

    mixed PluginFlyvemdmMqttuser::post_addItem()





* Visibility: **public**




### post_updateItem

    mixed PluginFlyvemdmMqttuser::post_updateItem($history)





* Visibility: **public**


#### Arguments
* $history **mixed**



### hashPassword

    string PluginFlyvemdmMqttuser::hashPassword(string $clearPassword)

Hash a password



* Visibility: **protected**


#### Arguments
* $clearPassword **string**



### getRandomPassword

    string PluginFlyvemdmMqttuser::getRandomPassword(\number $length, string $keyspace)

Generate a random password havind a determined set pf chars
http://stackoverflow.com/a/31284266



* Visibility: **public**
* This method is **static**.


#### Arguments
* $length **number** - &lt;p&gt;password length to generate&lt;/p&gt;
* $keyspace **string** - &lt;p&gt;characters available to build the pasword&lt;/p&gt;



### post_purgeItem

    mixed PluginFlyvemdmMqttuser::post_purgeItem()





* Visibility: **public**




### getByUser

    mixed PluginFlyvemdmMqttuser::getByUser(string $user)

Retrieve a mqtt user by name



* Visibility: **public**


#### Arguments
* $user **string**



### getACLs

    array<mixed,\PluginFlyvemdmMqttacl> PluginFlyvemdmMqttuser::getACLs()

Returns an array of PluginFlyvemdmMqttACL for the user



* Visibility: **public**



