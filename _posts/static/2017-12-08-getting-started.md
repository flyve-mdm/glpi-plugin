---
layout: post
code: false
howtos: true
published: true
title: Getting Started
permalink: howtos/getting-started
description: Welcome to Flyve MDM
---
The Flyve MDM plugin for GLPI integrates the intuitive and outstanding features of Flyve MDM into GLPI platform providing you the security functionality for your IT infrastructure.

As GLPI is a Free Asset and IT Management Software package, we wanted to provide a plugin that helps you to keep control of your mobile devices, here we'll guide you in the steps to manage your mobile fleet from the basics.

## Adding agents

In order to add agents you must invite them, go to the Invitations section, click on the "+" button and write the email of the user whose device you'll control.

Automatically, the user will receive an invitation in his email account, and now it will appear to you on the _Pending Invitations_ until it is accepted, helping you to keep track of the devices that already belong to your infrastructure.

![Invitations](https://raw.githubusercontent.com/Naylin15/Screenshots/master/glpi/invitations.gif)

Please note that the users must have installed in their devices the Android or iOS Agent, see these links for more information:

* [Android Agent](http://flyve.org/android-mdm-agent/)
* [iOS Agent](http://flyve.org/ios-mdm-agent/)

Once the user receives the email and enrolls, you will now see it in the Agent section, you can from there on access to the features, such as:

* Assign the Agent to a fleet
* Inventory
* Geolocation

## Create your Fleet

You must create a new fleet since the "not managed fleet" is the default one and therefore comes locked.

<img src="{{ '/images/picto-warning.png' | absolute_url }}" alt="!" height="16px"> Carefull, be sure to not delete it since it will bring you some problems.

In order to create it, you only have to click in the "+" button on the Fleet section, name it and click on add.

To add the policies go to the Fleets section and select your Fleet by clicking on it, with the left side menu you can add all the [policies](#our-policies) you require your fleet to have.

![Creating Fleets](https://raw.githubusercontent.com/Naylin15/Screenshots/master/glpi/fleet.gif)

Once you assign the devices to the Fleet, all the policies will be immediately applied!

## Our Policies

* Password, set the specifications as
  * Lenght
  * Quality
  * Minimun of letters (lowercase and uppercase)
  * Minimun of numbers
  * Wipe the device at a number of failed attempts to unlock the device
* Encryption of Internal Storage
* Peripherals
  * Cameras
  * Wifi
  * Bluetooth
  * GPS

## Adding Files & Applications

You must add in their respective sections the Files and Packages so you can use them on your Fleet.

Go to the Files section, click on the "+" button, name and select your file, click on "add"

![Files](https://raw.githubusercontent.com/Naylin15/Screenshots/master/glpi/file.gif)

You can now select it when you use the Deploy file policy

![Deploy file](https://raw.githubusercontent.com/Naylin15/Screenshots/master/glpi/df1.png)

The same procedure applies to the applications.

Go to the Package section, click on the "+" button, name and select your application, click on "add"

![Packages](https://raw.githubusercontent.com/Naylin15/Screenshots/master/glpi/da.gif)

And now you can select it when you use the Deploy application policy

![Deploy apps](https://raw.githubusercontent.com/Naylin15/Screenshots/master/glpi/app4.png)