---
layout: post
howtos: true
published: true
title: Installation v2.0.0
permalink: howtos/installation-wizard
description: Now with Wizard
---

## Server Recommendations

You need several servers to run Flyve MDM:

* a server running Linux, Apache, Mysql/MariaDB and PHP (a LAMP server) for the backend (GLPI and Flyve MDM for GLPI),
* a server running Mosquitto,
* a server running the web interface. It may run on the same server as GLPI

<img src="{{ '/images/picto-information.png' | absolute_url }}" alt="Good to know:" height="16px"> This is not mandatory, Mosquitto and GLPI can run in the same server, there is no limitation.

## Flyve MDM Overview

Flyve MDM v2.0.0 runs on GLPI 9.2.1 or later. It depends on inventory features of FusionInventory for GLPI, therefore it is required FusionInventory 9.2+1.0 or later. Check the compatibility matrix to know which versions are appropriate.

### Compatibility matrix

<table>
    <tr>
        <td style="width:150px">GLPI</td>
        <td style="width:100px">9.1.1</td>
        <td style="width:100px">9.1.2</td>
        <td style="width:100px">9.1.3</td>
        <td style="width:100px">9.2.1</td>
    </tr>
    <tr>
        <td><b>Flyve MDM</b></td>
        <td>1.x.x</td>
        <td>1.x.x</td>
        <td>1.x.x</td>
        <td>2.0.0-dev</td>
    </tr>
    <tr>
        <td><b>FusionInventory</b></td>
        <td>9.1+1.0</td>
        <td>9.1+1.0</td>
        <td>9.1+1.0</td>
        <td>9.2+1.0</td>
    </tr>
</table>

## Early Steps

* Download our specific version of GLPI 9.2.1 (please refer to its documentation to install)
* Go to the glpi folder and **run composer install --no-dev**
* Download FusionInventory 9.2+1.0 for GLPI and put it in glpi/plugins/
* Download Flyve MDM for GLPI and put it in glpi/plugins/

You should have a directory structure like this:

<img src="{{ '/images/glpi-tree-structure.png' | absolute_url }}" alt="GLPI Tree Insfrastructure">

* Go in the directory glpi/plugins/flyvemdm
* Run **composer install --no-dev**

## Configuration of GLPI

These steps are mandatory in order to access the plugin configuration.

### Notifications

* Login in GLPI with a super admin account.
* Go to **Setup > Notifications**
  * Enable followup
  * Enable followups via email

<img src="{{ '/images/step-notifications.png' | absolute_url }}" alt="Enable Email Notifications">

Click on **Email followups configuration** and setup the form depending on your requirements to send emails. This step is available from the wizard.

### Enabling the Rest API

* Go to **Setup > General** and select the tab **API**
  * Enable Rest API
  * Enable **login with credentials**
  * Enable **login with external tokens**
  * Check there is a full access API client able to use the API from any IPv4 or IPv6 address (click it to read and/or edit)

<img src="{{ '/images/enable-api.png' | absolute_url }}" alt="Enable API">

From the wizard you will be able to check this configuration is as it should.

## Configuration of Flyve MDM for GLPI

These steps can be followed from the Installation Wizard.

The Wizard will help you to check and complete all the mandatory steps in order to obtain a successful setup of Flyve MDM.

### Cron Job

Flyve MDM requires cron job to run Automatic actions

<img src="{{ '/images/step-cron.png' | absolute_url }}" alt="Cron Job">

### Email configuration

Adapt the settings to your requirements

<img src="{{ '/images/step-email-notifications.png' | absolute_url }}" alt="Email Notifications">

## Mosquitto & Mosquitto authentication plugin

Flyve MDM needs an authentication plugin for Mosquitto to authenticate MQTT clients against users stored in GLPI's DBMS.

The Wizard has an specific set of pages that will guide you through this configuration.

<img src="{{ '/images/step-mosquitto.png' | absolute_url }}" alt="Mosquitto">

### TLS listener

TLS provides security to the communication, through authentication of server and client, besides data encryption.

<img src="{{ '/images/step-tls.png' | absolute_url }}" alt="TLS listener">
