---
layout: post
code: true
howtos: false
published: true
title: Plugin Flyve MDM Task Status
permalink: development/devdocs/PluginFlyvemdmTaskstatus
---

* Class name: PluginFlyvemdmTaskstatus
* Namespace: 
* Parent class: CommonDBTM





Properties
----------


### $rightname

    public mixed $rightname = 'flyvemdm:taskstatus'





* Visibility: **public**
* This property is **static**.


Methods
-------


### getTypeName

    mixed PluginFlyvemdmTaskstatus::getTypeName($nb)

Localized name of the type



* Visibility: **public**
* This method is **static**.


#### Arguments
* $nb **mixed** - integer  number of item in the type (default 0)



### updateStatus

    mixed PluginFlyvemdmTaskstatus::updateStatus(\PluginFlyvemdmPolicyBase $policy, string $status)

Update status of a task



* Visibility: **public**


#### Arguments
* $policy **[PluginFlyvemdmPolicyBase](PluginFlyvemdmPolicyBase)**
* $status **string**


