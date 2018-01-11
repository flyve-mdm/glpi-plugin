---
layout: post
howtos: true
published: true
title: Installation
permalink: howtos/installation
description: Step by step
---

## General view of the infrastructure

You need several servers to run Flyve MDM:

* a server running Linux, Apache, Mysql/MariaDB and PHP (a LAMP server) for the backend (GLPI and Flyve MDM for GLPI),
* a server running Mosquitto,
* a server running the web interface. It may run on the same server as GLPI

## Installation overview

Flyve MDM runs on GLPI 9.1.1 and later. It depends on inventory features of FusionInventory for GLPI. You need FusionInventory 9.1+1.0 or later. The version depends on the version of GLPI you're planning to setup.

The general steps to properly configure the whole infrastructure are:

* Install GLPI
* Install FusionInventory and Flyve MDM plugin for GLPI
* Configure Flyve MDM plugin for GLPI
* Configure your DBMS
* Install and configure Mosquitto
* Install and configure the web application

## Dependencies

This plugin depends on GLPI, FusionInventory for GLPI and some packages

* Download our specific version of GLPI 9.2.x (please refer to its documentation to install)
* Download FusionInventory 9.2+1.0 for GLPI and put it in GLPI/plugins/
* Donwnload Flyve MDM for GLPI 2.0.0-dev and put it in glpi/plugins/

You will probably ask why you need a specific version of GLPI. Flyve MDM relies on a rest API GLPI developed recently. Flyve MDM requires some improvements which are not in the latest stable relase of GLPI. The specific version of GLPI we provide is the latest stable version, with a few backports from the development versions, to satisfy our needs.

### Compatibility matrix

<table>
    <tr>
        <td style="width:150px">GLPI</td>
        <td style="width:100px">9.1.1</td>
        <td style="width:100px">9.1.2</td>
        <td style="width:100px">9.1.3</td>
        <td style="width:100px">9.2.0</td>
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
        <td>9.1-1.1</td>
        <td>9.1-1.1</td>
        <td>9.1-1.1</td>
        <td>9.2+1.0</td>
    </tr>
</table>

You should have a directory structure like this:

<img src="{{ '/images/glpi-tree-structure.png' | absolute_url }}" alt="GLPI Tree Insfrastructure">

* Go in the directory glpi/plugins/flyvemdm
* Run **composer install --no-dev**

<img src="{{ '/images/picto-information.png' | absolute_url }}" alt="Good to know:" height="16px"> For Flyve MDM versions 1.x the folder must be renamed to storkmdm in order to appear in Setup > Plugins

## Configuration of GLPI

These steps are mandatory.

### Cron

Ensure the system has PHP CLI, then setup a cron job similar to the example below.

```
*/1 * * * * /usr/bin/php5 /var/www/glpi/front/cron.php &>/dev/null
```

Adjust the **path to PHP** and the **path to cron.php**

### Server configuration

Flyve MDM allows uploading files, Android packages and Uhuru Mobile packages. You should ensure php.ini allows reasonable upload sizes.

Here is an example of settings in php.ini
```
; Maximum size of POST data that PHP will accept.
; Its value may be 0 to disable the limit. It is ignored if POST data reading
; is disabled through enable_post_data_reading.
; http://php.net/post-max-size
post_max_size = 8M

; Whether to allow HTTP file uploads.
; http://php.net/file-uploads
file_uploads = On

; Maximum allowed size for uploaded files.
; http://php.net/upload-max-filesize
upload_max_filesize = 8M
```

### Notifications

Login in GLPI with a super admin account.

In the menu **Setup > Notifications** click on Enable followup via email.

<img src="{{ '/images/enable-email-notification.png' | absolute_url }}" alt="Enable Email Notifications">

The page refreshes itself to the email settings.

Click on **Email followups configuration** and setup the form depending on your requirements to send emails.

<img src="{{ '/images/email-notification-settings.png' | absolute_url }}" alt="Email Notification Settings">

In Setup > Automatic actions open queuednotifications. Set Run mode to CLI. This action is now triggered by the cron job every minute.

<img src="{{ '/images/picto-information.png' | absolute_url }}" alt="Good to know:" height="16px">  For GLPI 9.1.x versions, in **Setup > Automatic actions** search for **queuedmail** instead.

To ensure the cronjob is properly configured, check the log **glpi/files/_log/cron.log**. If a log entry contains the word **External** then the job fired from cron. Jobs manually fired from the UI would show **Internal** instead.

Example of a log entry
```
External #1: Launch queuedmail
2016-12-21 10:22:02 [@my-server]
```

### Enabling the rest API

* Open the menu **Setup > General** and select the tab **API**
* Enable rest API
* Enable **login with credentials**
* Enable **login with external tokens**
* Check there is a full access API client able to use the API from any IPv4 or IPv6 address (click it to read and/or edit)

<img src="{{ '/images/enable-api.png' | absolute_url }}" alt="Enable API">

### Configuration of FusionIventory

#### Fusioninventory greater than **9.1+1.1**

* Open **Administration > Rules > FusionInventory - Equipment import and link rules**
* If a rule named **Computer constraint (name)** exists, then open it and disable it.

<img src="{{ '/images/fusioninventory-computer-name-constraint.png' | absolute_url }}" alt="FusionInventory Computer Name Constraint">

Missing this will make FusionInventory reject inventories from devices.

### Configuration of Flyve MDM for GLPI

* Open **Configuration > Plugins**
* Click on **Flyve Mobile Device Management**

<img src="{{ '/images/flyve-mdm-general-settings.png' | absolute_url }}" alt="Flyve MDM General Settings">

* **mqtt broker address** is the public hostname or IP address of Mosquitto. It is sent to devices to tell them where is your Mosquitto server on the Internet. (*mandatory*).
* **mqtt broker internal address** is the private hostname or IP address of Mosquitto. It is used to tell GLPI where is your Mosquitto server in your local network. (*mandatory*).
* **mqtt broker port** is the port used by your mobile devices *and* GLPI (*mandatory*).
* **use TLS** enables TLS communication for mobile devices *and* GLPI.
* **CA certificate** is the certificate of an authority to verify the Mosquitto server.
* **Cipher suite** is used to limit the ciphers used with TLS.

<!-- Commented out: feature not ready
* **use client certificates** (*not working yet*) is used to allow mobile devices to verify the Mosquitto server
* **Ssl certificate server for MQTT clients** (*not working yet*) is a server which signs certificate requests of devices. This is for a future and stronger authentication method of devices with Mosquitto.
-->

* **Enable explicit enrollment failures** sends to devices the exact reason of an enrollment failure. For debug purpose only.
* **Disable token expiration on successful enrollment** is to prevent a token to expire when a device successfully enrolls. For debug purpose only.
* **Android bug collector URL** is the URL of a ACRA server. This server collects crash reports sent by Flyve MDM for Android.
* **Android bug collector user** is the username used by devices when they send a crash report.
* **Android bug collector password** is the password used by devices when they send a crash report.
* **Default device limit per entity** is the maximum quantity of devices allowed in an entity. Designed for the demo mode, but might be useful to enhance security.
* **Service's User Token** is the token to put a config.js when setting up the web application.

### Security

FlyveMDM needs only the REST API feature of GLPI to work with devices and its web interface.
Expose to the world only the API of GLPI, and keep inacessible GLPI's user interface.

Have a look into **glpi/.htaccess** if you can use Apache's mod_rewrite.

<img src="{{ '/images/picto-warning.png' | absolute_url }}" alt="Beware!" height="16px"> The directory **glpi/plugins/flyvemdm/scripts** must be inaccessible from the webserver.

* If running Apache, the .htaccess file in this directory will do the job.
* If running in another server like Nginx, please configure the host properly.

## Mysql / MariaDB

The DBMS must provide an access to the message queuing server for the authentication process of its clients. This server will be configured below; let's focus on the DBMS for now.

Assuming your DBMS server is not exposed to the world and the message queuing is on another server, edit **my.cnf** to listen on **0.0.0.0** instead of 127.0.0.1.

```
bind-address = 0.0.0.0
```

Create a new user in the DBMS able to read only the GLPI's database, and restrict this user to the IP of the future message queuing server.

## Mosquitto

<!-- Debian Stretch includes mosquitto-auth-plug and Mosquitto > 1.4.8. These instructions should be obsoleted
### Version considerations
Use Mosquitto **v1.4.8** or greater. You may encouteer crashes with older versions.

Flyve MDM needs an authentication plugin for Mosquitto to authenticate MQTT clients against users stored in GLPI's DBMS.

### Compile Mosquitto (needs improvement)

If you need to compile Mosquitto use the official sources

[mosquitto repository](https://github.com/eclipse/mosquitto)

* Edit **config.mk** and change WITH_SRV:=NO

* Install dependencies and compile

* Install Mosquitto

* Copy /etc/mosquitto/mosquitto.conf.example to /etc/mosquitto/mosquitto.conf
```
cp /etc/mosquitto/mosquitto.conf.example /etc/mosquitto/mosquitto.conf
```

* Create /var/lib/mosquitto
```
mkdir /var/lib/mosquitto
```

Create a user to run Mosquitto
```
adduser mosquitto --home /var/lib/mosquitto --shell /usr/sbin/nologin --no-create-home --system --group
```

* Make a init.d startup script
-->

### Configuration

If you installed Mosquitto from your distribution package, its settings may be located in several files.
The main configuration file is **/etc/mosquitto/mosquitto.conf**

<img src="{{ '/images/picto-information.png' | absolute_url }}" alt="Good to know:" height="16px"> [Official documentation to configure Mosquitto](http://mosquitto.org/man/mosquitto-conf-5.html)

<img src="{{ '/images/picto-warning.png' | absolute_url }}" alt="Beware!" height="16px"> We recomend you setup Mosquitto without encryption first, validate its configuration, then enable encryption. Of course, don't expose your setup to the world without encryption!

#### default unencrypted listener

Check the following settings to setup an unencrypted communication :

* pid_file /var/run/mosquitto.pid
* user mosquitto
* persistent_client_expiration 2m
* persistence true
* persistence_file mosquitto.db
* persistence_location /var/lib/mosquitto
* port 1883
* allow_anonymous false

#### TLS listener

Assuming you successfully configured and tested Mosquitto without encryption, use the following:

This example assumes cachain.pem contains the CA chain and your certificate.

<img src="{{ '/images/picto-warning.png' | absolute_url }}" alt="Beware!" height="16px"> It is quite common to find certificate failes with the extension .crt. Mosquitto requires the filenames ends with **.pem** and use PEM format.

* Copy in /etc/mosquitto/certs your certificate, your certificate authority chain and your private key.
* Secure your private key

```
chmod 600 /etc/mosquitto/certs/private-key.key
chown mosquitto:root /etc/mosquitto/certs/private-key.key
```

* refresh hash and symlinks to your certificates

```
c_rehash /etc/mosquitto/certs
```

You should use a certificate signed by a certified authority or you may have trouble with the android devices. Using android devices with custom certification authorities might not work (not tested).

```
listener 8883

cafile /etc/mosquitto/certs/cachain.pem
certfile /etc/mosquitto/certs/cachain.pem
keyfile /etc/mosquitto/certs/private-key.key
tls_version tlsv1.2
ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-ECDSA-RC4-SHA:AES128:AES256:HIGH:!RC4:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK
```

<img src="{{ '/images/picto-information.png' | absolute_url }}" alt="Good to know:" height="16px"> You should NOT use tls_version lower than tlsv1.2. TLS version 1.0 and 1.1 are no longer considered safe. Mosquitto does not supports SSLv2 or sslv3.

Restart Mosquitto

Test you can successfully connect to mosquitto

```
mosquitto_sub -h ip_of_mosquitto -t "#"  -p 8883 -i test-client --cafile /tmp/mycert.pem --capath /etc/ssl/certs/
```

#### disable default unencrypted listener
Assuming you successfully enabled and tested TLS, remove the following settings:

* bind_address
* port

Restart Mosquitto

## Mosquitto authentication plugin

<!-- obsoleted since the plugin is available in  the incoming Debian Stetch
### Compile the plugin if needed

If you need to compile the plugin, use the official sources.

[mosquitto-auth-plugin repository](https://github.com/jpmens/mosquitto-auth-plug)

* copy **config.mk.in** to **config.mk**
```
cp config.mk.in config.mk
```
* Edit config.mk to customize the path to Mosquitto's sources if you had to compile it
* Compile the plugin
* Copy the module auth-plug.so into /usr/local/lib/libmosquitto-auth-plug.so
-->

### Configure the plugin

<!--should NOT
If you compiled the plugin edit **/etc/mosquitto/mosquitto.conf**. If you installed it from your distro's packages you should edit **/etc/mosquitto/conf.d/auth-plug.conf**.
-->

Edit **/etc/mosquitto/conf.d/auth-plug.conf**

Add or edit the following settings

```
auth_plugin /usr/local/lib/libmosquitto-auth-plug.so
auth_opt_backends mysql
auth_opt_host backend-server-ip-or-fqdn
auth_opt_port 3306
auth_opt_user database-user
auth_opt_dbname glpi
auth_opt_pass StrongPassword
auth_opt_userquery SELECT password FROM glpi_plugin_flyvemdm_mqttusers WHERE user='%s' AND enabled='1'
auth_opt_aclquery SELECT topic FROM glpi_plugin_flyvemdm_mqttacls a LEFT JOIN glpi_plugin_flyvemdm_mqttusers u ON (a.plugin_flyvemdm_mqttusers_id = u.id) WHERE u.user='%s' AND u.enabled='1' AND (a.access_level & %d)
auth_opt_cacheseconds 300
```

Adapt the server, port and credentials to your setup.

* The server is your MySQL or MariaDB IP or hostname
* the port is  the listening port of your DBMS
* user and password should be an user able to read only your DB. No need to grant any write access. You created these credentials while setting up the DBMS.

## Configure the MQTT client service

The backend needs to listen to MQTT messages sent by devices. It needs a daemon  acting as a MQTT client. an init.d script is provided in ```glpi/plugins/flyvemdm/scripts/service.sh```. Create a symlink to /etc/init.d

```shell
sudo cd /etc/init
sudo ln -s /var/www/html/glpi/plugins/flyvemdm/scripts/service.sh /etc/init.d/flyvemdm
sudo update-rc.d flyvemdm defaults
```

## Optional features

### Orion malware scanner

If the Orion plugin is installed and enabled, when you upload a package to Flyve MDM the package will be submitted to the online Orion malware scanner. This third party service will produce a report to evaluate the risk level of the package.

#### Installation

Download and intall the Orion plugin for GLPI. Open its configuration page in Setup > Plugins > Orion. Set your user name and the API key you obtained to access the web service.