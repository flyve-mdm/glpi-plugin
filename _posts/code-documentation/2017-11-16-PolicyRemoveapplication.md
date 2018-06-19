---
layout: code-documentation
code: true
howtos: false
published: true
title: Plugin Flyve MDM Policy Remove Application
permalink: development/devdocs/PluginFlyvemdmPolicyRemoveapplication
---

* Class name: PluginFlyvemdmPolicyRemoveapplication
* Namespace:
* Parent class: [PluginFlyvemdmPolicyBase](PluginFlyvemdmPolicyBase)
* This class implements: [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)




Properties
----------


### $unicityRequired

    protected boolean $unicityRequired = true





* Visibility: **protected**


### $symbol

    protected string $symbol





* Visibility: **protected**


### $group

    protected string $group





* Visibility: **protected**


### $policyData

    protected \PluginFlyvemdmPolicy $policyData





* Visibility: **protected**


Methods
-------


### __construct

    mixed PluginFlyvemdmPolicyInterface::__construct(\PluginFlyvemdmPolicy $policy)





* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)


#### Arguments
* $policy **[PluginFlyvemdmPolicy](PluginFlyvemdmPolicy)**



### integrityCheck

    mixed PluginFlyvemdmPolicyInterface::integrityCheck(string $value, string $itemtype, integer $itemId)

Check the value used to apply a policy is valid, and check the the item to link



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)


#### Arguments
* $value **string**
* $itemtype **string** - the itemtype of an item
* $itemId **integer** - the id of an item



### unicityCheck

    mixed PluginFlyvemdmPolicyInterface::unicityCheck(string $value, string $itemtype, integer $itemId, \PluginFlyvemdmFleet $fleet)

Check the unicity of the policy



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)


#### Arguments
* $value **string**
* $itemtype **string**
* $itemId **integer**
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**



### getMqttMessage

    array PluginFlyvemdmPolicyInterface::getMqttMessage(string $value, string $itemtype, integer $itemId)

Returns an array describing the policy applied vith the given value and item



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)


#### Arguments
* $value **string**
* $itemtype **string** - the itemtype of an item
* $itemId **integer** - the id of an item



### jsonDecodeProperties

    array PluginFlyvemdmPolicyBase::jsonDecodeProperties(string $properties, array $defaultProperties)

JSON decode properties for the policy and merges them with default values



* Visibility: **protected**
* This method is defined by [PluginFlyvemdmPolicyBase](PluginFlyvemdmPolicyBase)


#### Arguments
* $properties **string**
* $defaultProperties **array**



### canApply

    mixed PluginFlyvemdmPolicyInterface::canApply(\PluginFlyvemdmFleet $fleet, string $value, string $itemtype, integer $itemId)

Check the policy may apply with respect of unicity constraint



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)


#### Arguments
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**
* $value **string**
* $itemtype **string** - the itemtype of an item
* $itemId **integer** - the id of an item



### conflictCheck

    mixed PluginFlyvemdmPolicyInterface::conflictCheck(string $value, string $itemtype, integer $itemId, \PluginFlyvemdmFleet $fleet)

Check there is not a conflict with an already applied policy



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)


#### Arguments
* $value **string**
* $itemtype **string**
* $itemId **integer**
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**



### translateData

    mixed PluginFlyvemdmPolicyInterface::translateData()

Translate type_data field



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)




### getGroup

    string PluginFlyvemdmPolicyInterface::getGroup()

get the group the policy belongs to



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)




### apply

    mixed PluginFlyvemdmPolicyInterface::apply(\PluginFlyvemdmFleet $fleet, string $value, $itemtype, $itemId)

Actions done before a policy is applied to a fleet



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)


#### Arguments
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**
* $value **string**
* $itemtype **mixed**
* $itemId **mixed**



### unapply

    mixed PluginFlyvemdmPolicyInterface::unapply(\PluginFlyvemdmFleet $fleet, string $value, $itemtype, $itemId)

Actions done after a policy is unapplied to a fleet



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)


#### Arguments
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**
* $value **string**
* $itemtype **mixed**
* $itemId **mixed**



### showValueInput

    mixed PluginFlyvemdmPolicyInterface::showValueInput()

return HTML input to set policy value



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)




### showValue

    mixed PluginFlyvemdmPolicyInterface::showValue(\PluginFlyvemdmTask $task)

return policy value for display



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)


#### Arguments
* $task **[PluginFlyvemdmTask](PluginFlyvemdmTask)**



### preprocessFormData

    array PluginFlyvemdmPolicyInterface::preprocessFormData(array $input)

Transforms form data to match the format expected by the API

When using GLPI the form data send in a different structure comapred to the API
This method converts it back to the format used in the API

Does nothing by default, override if needed

* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyInterface](PluginFlyvemdmPolicyInterface)


#### Arguments
* $input **array**



### filterStatus

    mixed PluginFlyvemdmPolicyBase::filterStatus(string $status)

get a status from a device and filters it to update a task



* Visibility: **public**
* This method is defined by [PluginFlyvemdmPolicyBase](PluginFlyvemdmPolicyBase)


#### Arguments
* $status **string**
