---
layout: post
howtos: true
published: true
title: MQTT Messages Specifications
permalink: howtos/mqtt-messages-specifications
description: MQ Telemetry Transport
---
# Index

* [Subscribe](#subscription-to-topics)
* [Device status policies](#device-status-policies)
* [Ping](#ping-query)
* [Geolocation](#geolocation-query)
* [File deployment policies](#file-deployment-policies)
* [Application deployment policies](#application-deployment-policies)
* [Device access policies](#device-access-policies)
* [Task status](#task-status)

# Introduction

MQTT messages are JSON strings

# MQTT Topic hierarchy

```
<FlyvemdmManifest>
+- Status
|   +- Version

<1st entity-ID>
+- agent
|  +- <1st Device's serial>
|  |  +- Command/Subscribe
|  |  +- Command/Ping
|  |  +- Command/Geolocate
|  |  +- Command/Lock
|  |  +- Command/Wipe
|  |  +- Command/Inventory
|  |  +- Command/Unenroll
|  | 
|  |  +- Status/Ping
|  |  +- Status/Geolocation
|  |  +- Status/Inventory
|  |  +- Status/Install
|  |  +- Status/Unenroll
|  |  +- Status/Task
|  |  +- Status/Online
|  |  
|  +- <2nd Device's serial ...>
|  +- <Nth Device's serial >
|
+- fleet
   +- <1st fleet ID>
   |  +- <Policy>
   |     +- <1st policySymbol>
   |     |  +- Task
   |     |     +- <task ID>
   |     +- <2nd policySymbol>
   |     |  +- Task
   |     |     +- <task ID>
   |     +- <Nth policySymbol>
   |        +- Task
   |           +- <task ID>
   |           
   |   
   +- <2nd fleet ID ...>
   +- <Nth fleet ID>

+- <2nd Entity-ID...>
+- <Nth entity-ID>
```

# MQTT message for policy deployment

There are many policies available. Some may be applied, some not.

When the backend needs to notify a fleet or an agent about new policy settings, the backends send all policies actually applied, in a single message. 

Example :
```json
{
   "storageEncryption":"false",
   "taskId":37
}
```

# MQTT messages sent by the backend

## Subscription to topics

Subscription to a fleet occurs when a device enrolls, and when an administrator moves a device from a fleet to another.

The database model makes a device is assigned to one and only one fleet. However the JSON format in the message allows a possible removal of this contraint in the future.

Sub topic ```/Command/Subscribe```

```json
{
    "subscribe" : [
        {"topic": "topic_1"},
        {"topic": "topic_2"},
        {"topic": "topic_3"}
    ]
}
```

QoS of the message = 1

## Device status policies

### Ping query

Sub topic ```Command/Ping```

```json
{
    "query" : "Ping"
}
```

Expected answer

Sub topic ```Status/Ping```
``` ! ```

### Geolocation query


Sub topic ```Command/Geolocate```

```json
{
    "query" : "Geolocate"
}
 ```

Expected answer

Sub topic ```Status/Geolocation```
```  {"latitude":48.1054276,"longitude":-1.67820699,"datetime":1476345332}  ```

Note: the datetime is in Unix time format, and *must* be on UTC timezone for proper save in DB by the backend.

## Unenroll query

Sub topic ```Command/Unenroll```

```json
{
    "unenroll": "Now"
}
 ```

Expected answer

Subtopic ```Status/Unenroll```

```json
{
    "unenroll": "unenrolled"
}
```

### Password settings policies

There are several password policies to setup the type of password required on a device and the complexity of the challenge.

Topic: 0/fleet/1/Policy/passwordEnabled/Task/2

```json
{ "passwordEnabled": "true|false", "taskId": "2"},
```

Topic: 0/fleet/1/Policy/passwordQuality/Task/3

```json
{ "passwordQuality" : "PASSWORD_QUALITY_NUMERIC|PASSWORD_QUALITY_ALPHABETIC|PASSWORD_QUALITY_ALPHANUMERIC|PASSWORD_QUALITY_COMPLEX|PASSWORD_QUALITY_SOMETHING|PASSWORD_QUALITY_UNSPECIFIED", "taskId": "3"},
```

Topic: 0/fleet/1/Policy/passwordMinLetters/Task/4

```json
{ "passwordMinLetters" : "0|1|2|..", "taskId": "4"},
```

Topic: 0/fleet/1/Policy/passwordMinLowerCase/Task/5

```json
{ "passwordMinLowerCase" : "0|1|2|..", "taskId": "5"},
```

Topic: 0/fleet/1/Policy/passwordMinUpperCase/Task/6

```json
{ "passwordMinUpperCase" : "0|1|2|..", "taskId": "6"},
```

Topic: 0/fleet/1/Policy/passwordMinNonLetter/Task/7

```json
{ "passwordMinNonLetter" : "0|1|2|..", "taskId": "7"},
```

Topic: 0/fleet/1/Policy/passwordMinNumeric/Task/8

```json
{ "passwordMinNumeric" : "0|1|2|..", "taskId": "8"},
```

Topic: 0/fleet/1/Policy/passwordMinLength/Task/9

```json
{ "passwordMinLength" : "0|1|2|..", "taskId": "9"},
```

Topic: 0/fleet/1/Policy/MaximumFailedPasswordsForWipe/Task/10

```json
{ "MaximumFailedPasswordsForWipe" : "0|1|2|..", "taskId": "10"},
```

Topic: 0/fleet/1/Policy/MaximumTimeToLock/Task/11

```json
{ "MaximumTimeToLock" : "time in MS", "taskId": "11"},
```

Topic: 0/fleet/1/Policy/passwordMinSymbols/Task/12

```json
{ "passwordMinSymbols" : "0|1|2|..", "taskId": "12"}
```

### Application deployment policies

There are two application deployment policies. One policy actually deploys an application, the other one removes an application. These policies may both apply multiple times on the same fleet target. 

The deployment policy retains a remove_on_delete flag. If this flag is set, removal of the deployment policy will create a policy in charge of the deletion of the same application, applied to the same fleet target.

#### Example 

##### Three deployment policies are applied to a single fleet target

Topic: 0/fleet/1/Policy/deployApp/Task/11

```json
{"deployApp" : "org.fdroid.fdroid", "id" : "1", "version": "18", "taskId": "11"},
```

Topic: 0/fleet/1/Policy/deployApp/Task/14

```json
{"deployApp" : "com.domain.application", "id" : "42", "version": "2", "taskId": "14"},
```

Topic: 0/fleet/1/Policy/deployApp/Task/19

```json
{"deployApp" : "com.domain.application", "id" : "5", "version": "42", "taskId": "19"}
```

##### One application removal policies is applied to a fleet target

Topic: 0/fleet/1/Policy/removeApp/Task/16

```json
{
   "removeApp" : "org.fdroid.fdroid", 
   "taskId": "16"
}
```

### File deployment policies

#### Example of file deployment policy

Topic: 0/fleet/1/Policy/removeApp/Task/23

```json
{
  "deployFile": "%SDCARD%/path/to/file.ext",
  "version": "18",
  "taskId": "23"
}

```

### Peripheral related policies

Topic: 0/fleet/1/Policy/removeApp/Task/25

```json
{ 
	"disableCamera" : "true|false", 
	"taskId": "25"
}
```

### Device access policies

#### Lock a device

To lock a device as soon as possible

```json
{
    "lock": "now"
}
```

#### Unlock a device

To unlock a device

```json
{
    "lock": "unlock"
}
```

#### Wipe a device

Sub topic ```/Command/Wipe```

```json
{
    "wipe" : "now"
}
```

QoS of the message = 2

### Connectivity policies

3 policies are available, a registered user can choose to apply only some of them. This means the array in the JSON may contain a subset of the JSON array below.

Topic: 0/fleet/1/Policy/removeApp/Task/25

```json
{ 
   "disableWifi" : "true|false",
   "taskId": "25"
}
```

Topic: 0/fleet/1/Policy/removeApp/Task/27

```json
{ 
	"disableGPS" : "true|false",
	"taskId": "27"
}
```json

Topic: 0/fleet/1/Policy/removeApp/Task/28

```json
{ 
	"disableBluetooth" : "true|false",
	"taskId": "28"
}
```

## (Uhuru Mobile) Applications available from the launcher

(specification only, not implemented)

```json
{
    "launcher": 
        { "code" : "update|start|unlock",
          "data" : [
            {"name" : "com.android.contacts"},
            {"name" : "com.android.mms"},
            {"name" : "com.android.settings"}
        ]}
}
```

### Property :
- **code** : identifiant de commande
        _start_ : lance l'application launcher
        _update_ : met à jour l'application launcher
        _unlock_ : déverouille le 'screen pinning'
- **data** : liste des applications
        _name_ : package de l'application à autoriser sur le terminal

Ps1: Dans le cas où une seule application est référencée, celle-ci est exécutée automatiquement (autolaunch).
Ps2: Dans le cas d'une liste d'applications, celles_ci sont affichées sur un bureau.

# MQTT messages sent by a device

## FlyveMDM version manifest

This subtopic contains metadata about Flyve MDM published to each device. This is the current version of the backend.

Sub topic ```/FlyvemdmManifest/Status/Version```

```json
{
    "version":"0.6.0"
}
```

## Task status

This subtopic is used by agents to feedback the progress of a policy deployment.

Sub topic ```/Status/Task/<task ID>```

```json
{
	"status": "in progress"
},
```

The status value may be any string up to 255 chars except the reserved statuses (see below). The status should be a short string. In the future, statuses will be normalized.

Reserved statuses:
* queued (when a task is created, this value is used to initialize the task status)
* pushed (when a message is sent by the backend, this value is used to update the status)


# Sources

Spec MQTT  3.1.1 : http://docs.oasis-open.org/mqtt/mqtt/v3.1.1/csprd01/mqtt-v3.1.1-csprd01.html#_Toc376954407
