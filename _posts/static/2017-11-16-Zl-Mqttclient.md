---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Mqtt Client
permalink: development/devdocs/pluginflyvemdmmqttclient
---

* Class name: PluginFlyvemdmMqttclient
* Namespace: 



Constants
----------


### MQTT_MAXIMUM_DURATION

    const MQTT_MAXIMUM_DURATION = 86400





Properties
----------


### $beginTimestamp

    protected integer $beginTimestamp





* Visibility: **protected**


### $mqtt

    protected \sskaje\mqtt\MQTT $mqtt





* Visibility: **protected**
* This property is **static**.


### $disconnect

    protected mixed $disconnect = false





* Visibility: **protected**


### $duration

    protected mixed $duration = self::MQTT_MAXIMUM_DURATION





* Visibility: **protected**


### $instance

    private \PluginFlyvemdmMqttclient $instance = null





* Visibility: **private**
* This property is **static**.


Methods
-------


### __construct

    mixed PluginFlyvemdmMqttclient::__construct()





* Visibility: **private**




### getInstance

    \PluginFlyvemdmMqttclient PluginFlyvemdmMqttclient::getInstance()

Get the unique instance of PluginFlyvemdmMqttclient



* Visibility: **public**
* This method is **static**.




### setHandler

    mixed PluginFlyvemdmMqttclient::setHandler(string $mqttHandler)

Sets the MQTT handler



* Visibility: **public**


#### Arguments
* $mqttHandler **string**



### setKeepalive

    mixed PluginFlyvemdmMqttclient::setKeepalive(integer $keepalive)

Sets the keep alive of the mqtt



* Visibility: **public**


#### Arguments
* $keepalive **integer**



### setMaxDuration

    mixed PluginFlyvemdmMqttclient::setMaxDuration(\numeric $duration)

Sets the maximun duration of the object



* Visibility: **public**


#### Arguments
* $duration **numeric**



### subscribe

    mixed PluginFlyvemdmMqttclient::subscribe($topic, \number $qos)

This method is used as a service running PHP-CLI only



* Visibility: **public**


#### Arguments
* $topic **mixed**
* $qos **number**



### publish

    true PluginFlyvemdmMqttclient::publish($topic, $message, \number $qos, \number $retain)





* Visibility: **public**


#### Arguments
* $topic **mixed**
* $message **mixed**
* $qos **number**
* $retain **number**



### pingresp

    mixed PluginFlyvemdmMqttclient::pingresp(string $mqtt, string $pingresp_object)

Breaks the infinite loop implemented in the MQTT client library using the ping response event



* Visibility: **public**


#### Arguments
* $mqtt **string**
* $pingresp_object **string**



### disconnect

    mixed PluginFlyvemdmMqttclient::disconnect()

Disconnects the MQTT client



* Visibility: **public**




### mustDisconnect

    mixed PluginFlyvemdmMqttclient::mustDisconnect()

Sets when it must disconnect the MQTT client



* Visibility: **protected**




### sendTestMessage

    boolean PluginFlyvemdmMqttclient::sendTestMessage(string $address, integer $port, $isTls, $sslCipher)

Send a test message to the MQTT broker



* Visibility: **public**


#### Arguments
* $address **string**
* $port **integer**
* $isTls **mixed**
* $sslCipher **mixed**



### getMQTTConnection

    \sskaje\mqtt\MQTT|false PluginFlyvemdmMqttclient::getMQTTConnection()

get an instance of sskaje/mqtt/MQTT



* Visibility: **protected**




### buildMqtt

    mixed PluginFlyvemdmMqttclient::buildMqtt(string $socketAddress, \TCP $port, string $isTls, string $sslCipher)

Builds a MQTT



* Visibility: **protected**


#### Arguments
* $socketAddress **string**
* $port **TCP**
* $isTls **string**
* $sslCipher **string**


