---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Policy Interface
permalink: development/devdocs/pluginflyvemdmpolicyinterface
---

* Interface name: PluginFlyvemdmPolicyInterface
* Namespace: 
* This is an **interface**






Methods
-------


### __construct

    mixed PluginFlyvemdmPolicyInterface::__construct(\PluginFlyvemdmPolicy $policy)





* Visibility: **public**


#### Arguments
* $policy **[PluginFlyvemdmPolicy](PluginFlyvemdmPolicy)**



### canApply

    mixed PluginFlyvemdmPolicyInterface::canApply(\PluginFlyvemdmFleet $fleet, string $value, string $itemtype, integer $itemId)

Check the policy may apply with respect of unicity constraint



* Visibility: **public**


#### Arguments
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**
* $value **string**
* $itemtype **string** - &lt;p&gt;the itemtype of an item&lt;/p&gt;
* $itemId **integer** - &lt;p&gt;the id of an item&lt;/p&gt;



### unicityCheck

    mixed PluginFlyvemdmPolicyInterface::unicityCheck(string $value, string $itemtype, integer $itemId, \PluginFlyvemdmFleet $fleet)

Check the unicity of the policy



* Visibility: **public**


#### Arguments
* $value **string**
* $itemtype **string**
* $itemId **integer**
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**



### integrityCheck

    mixed PluginFlyvemdmPolicyInterface::integrityCheck(string $value, string $itemtype, integer $itemId)

Check the value used to apply a policy is valid, and check the the item to link



* Visibility: **public**


#### Arguments
* $value **string**
* $itemtype **string** - &lt;p&gt;the itemtype of an item&lt;/p&gt;
* $itemId **integer** - &lt;p&gt;the id of an item&lt;/p&gt;



### conflictCheck

    mixed PluginFlyvemdmPolicyInterface::conflictCheck(string $value, string $itemtype, integer $itemId, \PluginFlyvemdmFleet $fleet)

Check there is not a conflict with an already applied policy



* Visibility: **public**


#### Arguments
* $value **string**
* $itemtype **string**
* $itemId **integer**
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**



### getMqttMessage

    array PluginFlyvemdmPolicyInterface::getMqttMessage(string $value, string $itemtype, integer $itemId)

Returns an array describing the policy applied vith the given value and item



* Visibility: **public**


#### Arguments
* $value **string**
* $itemtype **string** - &lt;p&gt;the itemtype of an item&lt;/p&gt;
* $itemId **integer** - &lt;p&gt;the id of an item&lt;/p&gt;



### translateData

    mixed PluginFlyvemdmPolicyInterface::translateData()

Translate type_data field



* Visibility: **public**




### getGroup

    string PluginFlyvemdmPolicyInterface::getGroup()

get the group the policy belongs to



* Visibility: **public**




### apply

    mixed PluginFlyvemdmPolicyInterface::apply(\PluginFlyvemdmFleet $fleet, string $value, $itemtype, $itemId)

Actions done before a policy is applied to a fleet



* Visibility: **public**


#### Arguments
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**
* $value **string**
* $itemtype **mixed**
* $itemId **mixed**



### unapply

    mixed PluginFlyvemdmPolicyInterface::unapply(\PluginFlyvemdmFleet $fleet, string $value, $itemtype, $itemId)

Actions done after a policy is unapplied to a fleet



* Visibility: **public**


#### Arguments
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**
* $value **string**
* $itemtype **mixed**
* $itemId **mixed**



### showValueInput

    mixed PluginFlyvemdmPolicyInterface::showValueInput()

return HTML input to set policy value



* Visibility: **public**




### showValue

    mixed PluginFlyvemdmPolicyInterface::showValue(\PluginFlyvemdmTask $task)

return policy value for display



* Visibility: **public**


#### Arguments
* $task **[PluginFlyvemdmTask](PluginFlyvemdmTask)**



### preprocessFormData

    array PluginFlyvemdmPolicyInterface::preprocessFormData(array $input)

Transforms form data to match the format expected by the API

When using GLPI the form data send in a different structure comapred to the API
This method converts it back to the format used in the API

Does nothing by default, override if needed

* Visibility: **public**


#### Arguments
* $input **array**


