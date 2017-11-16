---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Task Status
permalink: development/devdocs/pluginflyvemdmtaskstatus
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
* $nb **mixed** - &lt;p&gt;integer  number of item in the type (default 0)&lt;/p&gt;



### updateStatus

    mixed PluginFlyvemdmTaskstatus::updateStatus(\PluginFlyvemdmPolicyBase $policy, string $status)

Update status of a task



* Visibility: **public**


#### Arguments
* $policy **[PluginFlyvemdmPolicyBase](PluginFlyvemdmPolicyBase)**
* $status **string**


