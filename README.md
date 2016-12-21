[![Build Status](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi.svg?branch=master)](https://travis-ci.org/flyve-mdm/flyve-mdm-glpi)

# Abstract

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

# Installation

## Dependencies

This plugin is depends on GLPi, FusionInventory for GLPi and a some packages

* Download GLPi 9.1.1 or later (please refer to its documentation)
* Download FusionInventory 9.1+1.0 for GLPi and put it in glpi/plugins/
* Donwload Flyve MDM for GLPi and put it in glpi/plugins/

You should have a directory structure like this :

```
glpi
|
+ plugins
  |
  + fusioninventory
  + storkmdm
```

* Go in the directory  glpi/plugins/storkmdm
* Run composer install --no-dev

## Configuration of GLPi

These steps are mandatory.

### Cron

Ensure the system has PHP CLI, then setup a cron job similar to the example below.

```
*/1 * * * * /usr/bin/php5 /var/www/glpi/front/cron.php &>/dev/null
```

Adjust the **path to PHP** and the **path to cron.php**

### Notifications

Login in GLPi with a super admin account
In the menu **Setup > Notifications** click on Enable followup via email. The page refreshes itself. Click on **Email followups configuration** and setup the form depending on your requirements to send emails.

In **Setup > Automatic actions** open queuedmail. Set Run mode to **CLI**. This action is now triggered by the cron job every minute.

To ensure the cronjob is properly configured, check the log **glpi/files/_log/cron.log**. If a log entry contains the word **External** then the job fired from cron. Jobs manually fired from the UI would show **Internal** instead.

Example of a log entry
```
External #1: Launch queuedmail
2016-12-21 10:22:02 [@my-server]
```

### Enabling the rest API

* Open the menu **Setup > General** and select the tab **API**
* enable rest API
* enable **login with credentials**
* enable **login with external tokens**
* Check there is a full access API client able to use the API from any IPv4 or IPv6 address (click it to read and/or edit)

### Configuration of FusionIventory

#### Fusioninventory greater than **9.1+1.0**

* Open **Administration > Rules > FusionInventory - Equipment import and link rules**

* If a rule named **Computer constraint (name)** exists, then open it and disable it.

Missing this will make FusionInventory reject inventories from devices.

### Security

FlyveMDM needs only the REST API feature of GLPi to work with devices and its web interface.
Expose to the world only the API of GLPi, and keep inacessible GLPi's user interface.

Have a look into **glpi/.htaccess** if you can use  Apache's mod_rewrite.

The directory **glpi/plugins/storkmdm/scripts** must be inaccessible from the webserver.

* If running Apache, the .htaccess file in this directory will do the job.
* If running an other server like Nginx, please configure the host properly.

## Mysql / MariaDB

Mosquitto requires an access to the database.

Assuming your DBMS server is not exposed to the world and Mosquitto is on an other server, edit **my.cnf** to listen on **0.0.0.0** instead of 127.0.0.1.

```
bind-address = 0.0.0.0
```

## Mosquitto

### Version considerations
Use Mosquitto **v1.4.8** or greater. You may encouteer crashes with older versions.

Flyve MDM needs an authentication plugin for Mosquitto to authenticate MQTT clients against users stored in GLPi's DBMS.

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

### Configuration

If you installed Mosquitto from your distribution package, its settings may be located in several files.
The main configuration file is **/etc/mosquitto/mosquitto.conf**

Official documentation to configure Mosquitto : http://mosquitto.org/man/mosquitto-conf-5.html

We recomend you setup Mosquitto without encryption frist, validate its configuration, then enable encryption. Of course, don't expose your setup to the world without encryption !

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
Assuming you successfully configured Mosquitto without encryption, use the following :

This  example assumes cachain.crt contains the CA chain and your certificate.

* Copy in /etc/mosquitto/certs your certificate your certificate authority chain and your private key. 
* Secure your private key
```
chmod 600 /etc/mosquitto/certs/private-key.key
chown mosquitto:root /etc/mosquitto/certs/private-key.key
```
* refresh hash and symlinks to your certificates
```
c_rehash /etc/mosquitto/certs
```

You should use an certificate signed by a certified authority or you may have trouble with the android devices. Using android devices with custom certification authorities might not work (not tested).

```
listener 8883

cafile /etc/mosquitto/certs/cachain.crt
certfile /etc/mosquitto/certs/cachain.crt
keyfile /etc/mosquitto/certs/private-key.key
tls_version tlsv1.2
```

Note : **you should NOT use tls_version lower than tlsv1.2**. TLS version 1.0 and 1.1 are no longer considered safe.

Restart Mosquitto

#### disable default unencrypted listener
Assuming you successfully enabled TLS, remove the following settings :
* bind_address
* port

Restart Mosquitto

## Mosquitto authentication plugin

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

### Configure the plugin

If you compiled the plugin edit **/etc/mosquitto/mosquitto.conf**. If you installed it from your distro's packages you should edit **/etc/mosquitto/conf.d/auth-plug.conf**

Add / edit the following settings

```
auth_plugin /usr/local/lib/libmosquitto-auth-plug.so
auth_opt_backends mysql
auth_opt_host backend-server-ip-or-fqdn
auth_opt_port 3306
auth_opt_user database-user
auth_opt_dbname glpi
auth_opt_pass StrongPassword
#auth_opt_superquery
auth_opt_userquery SELECT password FROM glpi_plugin_storkmdm_mqttusers WHERE user='%s' AND enabled='1'
auth_opt_aclquery SELECT topic FROM glpi_plugin_storkmdm_mqttacls a LEFT JOIN glpi_plugin_storkmdm_mqttusers u ON (a.plugin_storkmdm_mqttusers_id = u.id) WHERE u.user='%s' AND u.enabled='1' AND (a.access_level & %d)
auth_opt_cacheseconds 300

```

Adapt the server, port and credentials to your setup.
* The server is your MySQL or MariaDB IP or hostname
* the port is  the listening port of your DBMS
* user and password should be a user able to read only your DB. No need to grant any write access.



# Contributing

## Tests

* Go to the folder containing GLPi
* Run composer install
* Run php tools/cliinstall.php --tests --user=database-user --pass=database-pass --db=glpi-test
* Go to plugins/storkmdm
* Run php tools/cliinstall.php --tests
* Run phpunit
