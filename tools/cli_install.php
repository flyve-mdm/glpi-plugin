<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2018 Teclib'
 * Copyright © 2010-2018 by the FusionInventory Development Team.
 *
 * This file is part of Flyve MDM Plugin for GLPI.
 *
 * Flyve MDM Plugin for GLPI is a subproject of Flyve MDM. Flyve MDM is a mobile
 * device management software.
 *
 * Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 * ------------------------------------------------------------------------------
 * @author    Thierry Bugier
 * @copyright Copyright © 2018 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

(PHP_SAPI == 'cli') or die("Only available from command line");

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

include (__DIR__ . "/../vendor/docopt/docopt/src/docopt.php");

$doc = <<<DOC
cli_install.php

Usage:
   cli_install.php [--as-user USER] [--api-user-token APITOKEN] [--enable-api ] [--enable-email ] [ --tests ] [--dev] [--mqtt-address MQTTADDRESS] [--mqtt-internal-address MQTTINTERNALADDRESS] [--mqtt-port MQTTPORT] [--mqtt-port-tls MQTTPORTTLS]

Options:
   --as-user USER                               Do install/upgrade as specified USER. If not provided, 'glpi' user will be used
   --api-user-token                             APITOKEN    APITOKEN
   --enable-api                                 Enable GLPI's API
   --enable-email                               Enable GLPI's email notification
   --tests                                      Use GLPI test database
   --dev                                        Change the Agent download URL for the Beta testing url
   --mqtt-address MQTTADDRESS                   Sets the address for Mosquitto MQTTADDRESS. This parameter can be [ IP Address/Hostname ]
   --mqtt-internal-address MQTTINTERNALADDRESS  Sets the Internal address for Mosquitto MQTTINTERNALADDRESS. This parameter can be [ IP Address/Hostname ]
   --mqtt-port MQTTPORT                         Sets the Listen Port for Mosquitto MQTTPORT
   --mqtt-port-tls MQTTPORTTLS                  Sets the Listen Port TLS for Mosquitto MQTTPORTTLS

DOC;

$docopt = new \Docopt\Handler();
$args = $docopt->handle($doc);

$asUser = 'glpi';
if (!is_null($args['--as-user'])) {
   $asUser = $args['--as-user'];
}
if (isset($args['--tests']) && $args['--tests'] !== false) {
   // Use test GLPi's database
   // Requires use of cliinstall of GLPI with --tests argument
   define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
   define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
}

// disable session cookie for CLI mode
ini_set("session.use_cookies", "0");

include (__DIR__ . "/../../../inc/includes.php");

if (isset($args['--enable-api']) && $args['--enable-api'] !== false) {
   $config = [
         'enable_api'                        => '1',
         'enable_api_login_credentials'      => '1',
         'enable_api_login_external_token'   => '1',
   ];
   Config::setConfigurationValues('core', $config);
   $CFG_GLPI = $config + $CFG_GLPI;
}

if (isset($args['--enable-email']) && $args['--enable-email'] !== false) {
   $config = [
         'use_notifications'     => '1',
         'notifications_mailing' => '1',
   ];
   Config::setConfigurationValues('core', $config);
   $CFG_GLPI = $config + $CFG_GLPI;
}

if (!plugin_flyvemdm_check_prerequisites()) {
   exit(1);
}

// Setup plugin configuration
$pluginConfig = [];
if (isset($args['--mqtt-address']) && $args['--mqtt-address'] !== false) {
    $pluginConfig['mqtt_broker_address'] = $args['--mqtt-address'];
}
if (isset($args['--mqtt-internal-address']) && $args['--mqtt-internal-address'] !== false) {
    $pluginConfig['mqtt_broker_internal_address'] = $args['--mqtt-internal-address'];
}
if (isset($args['--mqtt-port']) && $args['--mqtt-port'] !== false) {
   $pluginConfig['mqtt_broker_port'] = $args['--mqtt-port'];
}
if (isset($args['--mqtt-port-tls']) && $args['--mqtt-port-tls'] !== false) {
   $pluginConfig['mqtt_broker_tls_port'] = $args['--mqtt-port-tls'];
}

// Init debug variable
$_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
$_SESSION['glpilanguage']  = "en_GB";

Session::loadLanguage();

// Only show errors
$CFG_GLPI["debug_sql"]        = $CFG_GLPI["debug_vars"] = 0;
$CFG_GLPI["use_log_in_files"] = 1;
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
//set_error_handler('userErrorHandlerDebug');

$DB = new DB();
if (!$DB->connected) {
   die("No DB connection\n");
}

$user = new User();
if (!$user->getFromDBbyName($asUser)) {
   die("User $asUser not found in DB\n");
}
$auth = new Auth();
$auth->auth_succeded = true;
$auth->user = $user;
Session::init($auth);

$apiUserToken = $args['--api-user-token'];
$dev = $args['--dev'];

/*---------------------------------------------------------------------*/

if (!$DB->tableExists("glpi_configs")) {
   echo "GLPI not installed\n";
   exit(1);
}

$plugin = new Plugin();

// Install the plugin
$plugin->getFromDBbyDir("flyvemdm");
print("Installing Plugin Id: " . $plugin->fields['id'] . " version " . $plugin->fields['version'] . "\n");
ob_start(function($in) { return ''; });
$plugin->install($plugin->fields['id']);
ob_end_clean();
print("Install Done\n");
if ($apiUserToken) {
   $serviceUser = PluginFlyvemdmConfig::SERVICE_ACCOUNT_NAME;
   $flyveUser = new User();
   $flyveUser->getFromDBbyName($serviceUser);
   $sqlUpdate = "UPDATE glpi_users set personal_token = '" . $apiUserToken . "' WHERE id = ". $flyveUser->fields['id'];
   $DB->query($sqlUpdate);
   print("update $serviceUser user with provided api token " . $apiUserToken . "\n");
}

print("setting queuednotification to Queue mode\n");
$cronQuery = "update glpi_crontasks set mode = 2 where name ='queuednotification'";
$DB->query($cronQuery);

print("opening glpi client api access\n");
$apiClientQuery = "UPDATE glpi_apiclients
                   SET `name` = 'full access flyve',
                       `ipv4_range_start` = null,
                       `ipv4_range_end` = null
                   WHERE `name` like 'full access from localhost'";
$DB->query($apiClientQuery);

if($dev) {
   $entityConfig = new PluginFlyvemdmEntityConfig();
   $entityConfig->getFromDBByCrit([
      'entities_id' => '0',
   ]);
   $entityConfig->update([
      'id'           => $entityConfig->getID(),
      'download_url' => PLUGIN_FLYVEMDM_AGENT_BETA_DOWNLOAD_URL
   ]);
}
Config::setConfigurationValues('flyvemdm', $pluginConfig);

// Enable the plugin
print("Activating Plugin...\n");
$plugin->activate($plugin->fields['id']);
if (!$plugin->activate($plugin->fields['id'])) {
   print("Activation failed\n");
   exit(1);
}
print("Activation Done\n");

//Load the plugin
print("Loading Plugin...\n");
$plugin->load("flyvemdm");
print("Load Done...\n");
