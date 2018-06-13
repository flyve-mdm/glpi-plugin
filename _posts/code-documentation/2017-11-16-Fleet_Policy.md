---
layout: code-documentation
code: true
howtos: false
published: true
title: Plugin Flyve MDM Fleet Policy
permalink: development/devdocs/PluginFlyvemdmFleet_Policy
---

* Class name: PluginFlyvemdmFleet_Policy
* Namespace:
* Parent class: CommonDBRelation

## Properties



### $itemtype_1

    public string $itemtype_1 = 'PluginFlyvemdmFleet'





* Visibility: **public**
* This property is **static**.


### $items_id_1

    public string $items_id_1 = 'plugin_flyvemdm_fleets_id'





* Visibility: **public**
* This property is **static**.


### $itemtype_2

    public string $itemtype_2 = 'PluginFlyvemdmPolicy'





* Visibility: **public**
* This property is **static**.


### $items_id_2

    public string $items_id_2 = 'plugin_flyvemdm_policies_id'





* Visibility: **public**
* This property is **static**.


### $policy

    protected \PluginFlyvemdmPolicyBase $policy





* Visibility: **protected**


### $fleet

    protected \PluginFlyvemdmFleet $fleet





* Visibility: **protected**


### $silent

    protected boolean $silent





* Visibility: **protected**

## Methods

### getTabNameForItem

<p class="p-size5">
    mixed PluginFlyvemdmFleet_Policy::getTabNameForItem(\CommonGLPI $item, $withtemplate)
</p>




* Visibility: **public**


#### Arguments
* $item **CommonGLPI**
* $withtemplate **mixed**



### addNeededInfoToInput

<p class="p-size5">
    mixed PluginFlyvemdmFleet_Policy::addNeededInfoToInput($input)
</p>




* Visibility: **public**


#### Arguments
* $input **mixed**



### prepareInputForAdd

    mixed PluginFlyvemdmFleet_Policy::prepareInputForAdd($input)





* Visibility: **public**


#### Arguments
* $input **mixed**



### prepareInputForUpdate

<p class="p-size5">
    mixed PluginFlyvemdmFleet_Policy::prepareInputForUpdate($input)
</p>




* Visibility: **public**


#### Arguments
* $input **mixed**



### post_addItem

    mixed PluginFlyvemdmFleet_Policy::post_addItem()

$this->policy->field['group']



* Visibility: **public**




### post_updateItem

    mixed PluginFlyvemdmFleet_Policy::post_updateItem($history)





* Visibility: **public**


#### Arguments
* $history **mixed**



### pre_deleteItem

    mixed PluginFlyvemdmFleet_Policy::pre_deleteItem()





* Visibility: **public**




### post_purgeItem

    mixed PluginFlyvemdmFleet_Policy::post_purgeItem()





* Visibility: **public**




### updateQueue

<p class="p-size5">
    mixed PluginFlyvemdmFleet_Policy::updateQueue(\PluginFlyvemdmNotifiable $item, $groups)
</p>




* Visibility: **public**


#### Arguments
* $item **[PluginFlyvemdmNotifiable](PluginFlyvemdmNotifiable)**
* $groups **mixed**



### publishPolicies

<p class="p-size5">
    mixed PluginFlyvemdmFleet_Policy::publishPolicies(\PluginFlyvemdmNotifiable $item, array $groups)
</p>

MQTT publish all policies applying to the fleet



* Visibility: **public**


#### Arguments



*  $item **[PluginFlyvemdmNotifiable](PluginFlyvemdmNotifiable)**
  <ul class="p-size2">  
    <li> $groups <b>array</b> - the notifiable is updated only for the following policies groups </li>
  </ul>




### buildGroupOfPolicies

<p class="p-size5">
    mixed PluginFlyvemdmFleet_Policy::buildGroupOfPolicies(string $group, \PluginFlyvemdmFleet $fleet)
</p>

<p class="type-p2 p-size2">
Builds a group of policies using the value of an applied policy for a fleet, and the default value of
non applied policies of the same group
</p>


* Visibility: **protected**


#### Arguments
* $group **string** - name of a group of policies
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)** - fleet the group will built for



### cleanupPolicies

<p class="p-size5">
    mixed PluginFlyvemdmFleet_Policy::cleanupPolicies(\PluginFlyvemdmNotifiable $item, array $groups)
</p>

Removes persisted MQTT messages for groups of policies



* Visibility: **public**
* This method is **static**.


#### Arguments
* $item **[PluginFlyvemdmNotifiable](PluginFlyvemdmNotifiable)** - a notifiable item
* $groups **array** - array of groups to delete



### getSearchOptions

    mixed PluginFlyvemdmFleet_Policy::getSearchOptions()





* Visibility: **public**




### displayTabContentForItem

<p class="p-size5">
    mixed PluginFlyvemdmFleet_Policy::displayTabContentForItem(\CommonGLPI $item, $tabnum, $withtemplate)
</p>




* Visibility: **public**
* This method is **static**.


#### Arguments
* $item **CommonGLPI**
* $tabnum **mixed**
* $withtemplate **mixed**



### showForFleet

<p class="p-size5">
    mixed PluginFlyvemdmFleet_Policy::showForFleet(\CommonDBTM $item, $withtemplate)
</p>




* Visibility: **public**
* This method is **static**.


#### Arguments
* $item **CommonDBTM**
* $withtemplate **mixed**



### preprocessInput

    mixed PluginFlyvemdmFleet_Policy::preprocessInput($input)

Processes



* Visibility: **public**


#### Arguments
* $input **mixed**



### getAppliedPolicies

<p class="p-size5">
    mixed PluginFlyvemdmFleet_Policy::getAppliedPolicies(\PluginFlyvemdmFleet $fleet)
</p>




* Visibility: **public**


#### Arguments
* $fleet **[PluginFlyvemdmFleet](PluginFlyvemdmFleet)**
