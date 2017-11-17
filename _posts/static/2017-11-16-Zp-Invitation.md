---
layout: post
code: true
wiki: false
published: true
title: Plugin Flyve MDM Invitation
permalink: development/devdocs/PluginFlyvemdmInvitation
---

* Class name: PluginFlyvemdmInvitation
* Namespace: 
* Parent class: CommonDBTM



Constants
----------


### DEFAULT_TOKEN_LIFETIME

    const DEFAULT_TOKEN_LIFETIME = "P7D"





Properties
----------


### $rightname

    public string $rightname = 'flyvemdm:invitation'





* Visibility: **public**
* This property is **static**.


### $user

    protected \User $user





* Visibility: **protected**


Methods
-------


### getEnumInvitationStatus

    array PluginFlyvemdmInvitation::getEnumInvitationStatus()

Gets the possibles statuses that an invitation can have



* Visibility: **public**




### getTypeName

    mixed PluginFlyvemdmInvitation::getTypeName($nb)

Localized name of the type



* Visibility: **public**
* This method is **static**.


#### Arguments
* $nb **mixed** - integer  number of item in the type (default 0)



### getMenuPicture

    string PluginFlyvemdmInvitation::getMenuPicture()

Returns the URI to the picture file relative to the front/folder of the plugin



* Visibility: **public**
* This method is **static**.




### getRights

    mixed PluginFlyvemdmInvitation::getRights($interface)





* Visibility: **public**


#### Arguments
* $interface **mixed**



### prepareInputForAdd

    array|false PluginFlyvemdmInvitation::prepareInputForAdd(array $input)

Prepares input to follow the most used description convention



* Visibility: **public**


#### Arguments
* $input **array** - the data to use when creating a new row in the DB



### prepareInputForUpdate

    mixed PluginFlyvemdmInvitation::prepareInputForUpdate($input)





* Visibility: **public**


#### Arguments
* $input **mixed**



### getFromDBByToken

    boolean PluginFlyvemdmInvitation::getFromDBByToken(string $token)

Finds the invitation that matches the token given in argument



* Visibility: **public**


#### Arguments
* $token **string**



### setInvitationToken

    string PluginFlyvemdmInvitation::setInvitationToken()

Generates the Invitation Token



* Visibility: **protected**




### pre_deleteItem

    mixed PluginFlyvemdmInvitation::pre_deleteItem()





* Visibility: **public**




### post_addItem

    mixed PluginFlyvemdmInvitation::post_addItem()





* Visibility: **public**




### hook_pre_self_purge

    mixed PluginFlyvemdmInvitation::hook_pre_self_purge(\CommonDBTM $item)





* Visibility: **public**
* This method is **static**.


#### Arguments
* $item **CommonDBTM**



### hook_pre_document_purge

    mixed PluginFlyvemdmInvitation::hook_pre_document_purge(\CommonDBTM $item)

Actions done when a document is being purged



* Visibility: **public**
* This method is **static**.


#### Arguments
* $item **CommonDBTM** - Document


### getUser

    \the PluginFlyvemdmInvitation::getUser()





* Visibility: **public**




### createQRCodeDocument

    string PluginFlyvemdmInvitation::createQRCodeDocument(\User $user, string $învitationToken)

get the enrollment URL of the agent



* Visibility: **protected**


#### Arguments
* $user **User** - Recipient of the QR code
* $învitationToken **string** - Invitation token



### sendInvitation

    mixed PluginFlyvemdmInvitation::sendInvitation()

Sends an invitation



* Visibility: **public**




### getSearchOptions

    mixed PluginFlyvemdmInvitation::getSearchOptions()





* Visibility: **public**




### hook_entity_purge

    mixed PluginFlyvemdmInvitation::hook_entity_purge(\CommonDBTM $item)

Deletes the invitation related to the entity being purged



* Visibility: **public**


#### Arguments
* $item **CommonDBTM**



### showForm

    mixed PluginFlyvemdmInvitation::showForm($ID, $options)

Show form for edition



* Visibility: **public**


#### Arguments
* $ID **mixed**
* $options **mixed**



### showMassiveActionInviteUser

    string PluginFlyvemdmInvitation::showMassiveActionInviteUser()

Displays the massive actions related to the invitation of the user



* Visibility: **protected**




### showMassiveActionsSubForm

    mixed PluginFlyvemdmInvitation::showMassiveActionsSubForm(\MassiveAction $ma)





* Visibility: **public**
* This method is **static**.


#### Arguments
* $ma **MassiveAction**



### processMassiveActionsForOneItemtype

    mixed PluginFlyvemdmInvitation::processMassiveActionsForOneItemtype(\MassiveAction $ma, \CommonDBTM $item, array $ids)

Executes the code to process the massive actions



* Visibility: **public**
* This method is **static**.


#### Arguments
* $ma **MassiveAction**
* $item **CommonDBTM**
* $ids **array**


