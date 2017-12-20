---
layout: post
code: true
howtos: false
published: true
title: Plugin Flyve MDM Notification Target Invitation
permalink: development/devdocs/PluginFlyvemdmNotificationTargetInvitation
---

* Class name: PluginFlyvemdmNotificationTargetInvitation
* Namespace: 
* Parent class: NotificationTarget



Constants
----------


### EVENT_GUEST_INVITATION

    const EVENT_GUEST_INVITATION = 'plugin_flyvemdm_invitation'





### DEEPLINK

    const DEEPLINK = 'flyve://register?data='







Methods
-------


### getEvents

    Array PluginFlyvemdmNotificationTargetInvitation::getEvents()

Define plugins notification events



* Visibility: **public**




### addEvents

    mixed PluginFlyvemdmNotificationTargetInvitation::addEvents(\NotificationTarget $target)





* Visibility: **public**
* This method is **static**.


#### Arguments
* $target **NotificationTarget**



### getTags

    mixed PluginFlyvemdmNotificationTargetInvitation::getTags()

Get available tags for plugins notifications



* Visibility: **public**




### getAdditionalDatasForTemplate

    mixed PluginFlyvemdmNotificationTargetInvitation::getAdditionalDatasForTemplate(\NotificationTarget $event)





* Visibility: **public**
* This method is **static**.


#### Arguments
* $event **NotificationTarget**



### addNotificationTargets

    mixed PluginFlyvemdmNotificationTargetInvitation::addNotificationTargets($entity)

Return all the targets for this notification
Values returned by this method are the ones for the alerts
Can be updated by implementing the getAdditionnalTargets() method
Can be overwitten (like dbconnection)



* Visibility: **public**


#### Arguments
* $entity **mixed** - the entity on which the event is raised



### addSpecificTargets

    mixed PluginFlyvemdmNotificationTargetInvitation::addSpecificTargets(array $data, array $options)





* Visibility: **public**


#### Arguments
* $data **array**
* $options **array**


