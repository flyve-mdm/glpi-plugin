<?php
/**
 LICENSE

Copyright (C) 2016 Teclib'
Copyright (C) 2010-2016 by the FusionInventory Development Team.

This file is part of Flyve MDM Plugin for GLPI.

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
You should have received a copy of the GNU Affero General Public License
along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 ------------------------------------------------------------------------------
 @author    Thierry Bugier Pineau
 @copyright Copyright (c) 2016 Flyve MDM plugin team
 @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 @link      https://github.com/flyvemdm/backend
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

define ("PLUGIN_STORKMDM_VERSION", "0.7.0-dev");
// Minimal GLPI version, inclusive
define ("PLUGIN_STORKMDM_GLPI_MIN_VERSION", "9.1");
// Maximum GLPI version, exclusive
define ("PLUGIN_STORKMDM_GLPI_MAX_VERSION", "9.3");
// Minimum PHP version inclusive
define ("PLUGIN_STORKMDM_PHP_MIN_VERSION", "5.5");

define ("PLUGIN_STORKMDM_ROOT", GLPI_ROOT . "/plugins/storkmdm");

define ("PLUGIN_STORKMDM_AGENT_DOWNLOAD_URL", 'https://flyve-mdm.com/download/android-agent.apk');

if (!defined("STORKMDM_CONFIG_PATH")) {
   define("STORKMDM_CONFIG_PATH", GLPI_PLUGIN_DOC_DIR . "/storkmdm");
}

if (!defined("STORKMDM_CONFIG_CACERTMQTT")) {
   define("STORKMDM_CONFIG_CACERTMQTT", STORKMDM_CONFIG_PATH . "/CACert-mqtt.crt");
}

if (!defined("STORKMDM_PACKAGE_PATH")) {
   define("STORKMDM_PACKAGE_PATH", GLPI_PLUGIN_DOC_DIR . "/storkmdm/package");
}

if (!defined("STORKMDM_FILE_PATH")) {
   define("STORKMDM_FILE_PATH", GLPI_PLUGIN_DOC_DIR . "/storkmdm/file");
}

// Init the hooks of the plugins -Needed
function plugin_init_storkmdm() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['storkmdm'] = true;
   $PLUGIN_HOOKS['undiscloseConfigValue']['storkmdm'] = array('PluginStorkmdmConfig', 'undiscloseConfigValue');

   $plugin = new Plugin();

   $config = Config::getConfigurationValues('storkmdm', array('version'));
   if (isset($config['version']) && $config['version'] != PLUGIN_STORKMDM_VERSION) {
      $plugin->getFromDBbyDir('storkmdm');
      $plugin->update([
            'id'     => $plugin->getID(),
            'state'  => Plugin::NOTUPDATED
      ]);
   }

   if ($plugin->isInstalled("storkmdm") && $plugin->isActivated("storkmdm")) {
      require_once(__DIR__ . '/vendor/autoload.php');

      $PLUGIN_HOOKS['change_profile']['storkmdm']   = array('PluginStorkmdmProfile','changeProfile');

      Plugin::registerClass('PluginStorkmdmMqttsubscriber');
      Plugin::registerClass('PluginStorkmdmAgent');
      Plugin::registerClass('PluginStorkmdmProfile',
            array('addtabon' => 'Profile'));
      Plugin::registerClass('PluginStorkmdmPackage');
      Plugin::registerClass('PluginStorkmdmFile');

      // Dropdowns
      Plugin::registerClass('PluginStorkmdmWellknownpath');
      Plugin::registerClass('PluginStorkmdmWPolicyCategory');

      //if glpi is loaded
      if (Session::getLoginUserID()) {
         $PLUGIN_HOOKS['menu']["storkmdm"]                  = true;
      }
      $PLUGIN_HOOKS['post_init']["storkmdm"]                = 'plugin_storkmdm_postinit';

      // Notifications
      $PLUGIN_HOOKS['item_get_events']['storkmdm'] =
            array('PluginStorkmdmNotificationTargetInvitation' => array('PluginStorkmdmNotificationTargetInvitation', 'addEvents'));
      $PLUGIN_HOOKS['item_get_datas']['storkmdm'] =
            array('PluginStorkmdmNotificationTargetInvitation' => array('PluginStorkmdmNotificationTargetInvitation', 'getAdditionalDatasForTemplate'));
      Plugin::registerClass('PluginStorkmdmInvitation', array(
         'notificationtemplates_types' => true, // 'document_types' => true
      ));

      if (Session::haveRight(PluginStorkmdmProfile::$rightname, PluginStorkmdmProfile::RIGHT_STORKMDM_USE)) {
         // Display a menu entries
         $PLUGIN_HOOKS['menu_toadd']["storkmdm"] = array(
               'tools'  => 'PluginStorkmdmMenu',
         );
         $PLUGIN_HOOKS['config_page']["storkmdm"] = 'front/config.form.php';
      }

      // Hooks for the plugin : objects inherited from GLPI or
      $PLUGIN_HOOKS['pre_item_add']['storkmdm']     = array(
            'User'                  => array('PluginStorkmdmUser', 'hook_pre_user_add'),
            'Entity'                => array('PluginStorkmdmEntityconfig', 'hook_pre_entity_add'),
      );
      $PLUGIN_HOOKS['item_add']['storkmdm']     = array(
            'Entity'                => array('PluginStorkmdmEntityconfig', 'hook_entity_add'),
            'PluginStorkmdmEntity'  => array('PluginStorkmdmEntityconfig', 'hook_entity_add'),
      );
      $PLUGIN_HOOKS['item_purge']['storkmdm']   = array(
            'User'                  => array('PluginStorkmdmUser', 'hook_pre_user_purge'),
            'Entity'                => array('PluginStorkmdmEntityconfig', 'hook_entity_purge'),
            'Computer'              => array('PluginStorkmdmGeolocation', 'hook_computer_purge'),
      );
      $PLUGIN_HOOKS['pre_item_purge']['storkmdm']   = array(
            'PluginStorkmdmInvitation' => array('PluginStorkmdmInvitation', 'hook_pre_self_purge'),
            'Document'                 => array('PluginStorkmdmInvitation', 'hook_pre_document_purge'),
      );

      // Add css and js resources if the requested page needs them
      if (strpos($_SERVER['REQUEST_URI'], "storkmdm" . "/front/config.form.php") !== false) {
         $PLUGIN_HOOKS['add_javascript']["storkmdm"][] = 'config.js';
      }

      $CFG_GLPI['fleet_types'] = array('PluginStorkmdmFile', 'PluginStorkmdmPackage');
   }
}

// Get the name and the version of the plugin - Needed
function plugin_version_storkmdm() {
   global $LANG;

   $author = "<a href='http://www.teclib.com'>Teclib</a>";
   return array ('name'           => __s('Stork Mobile Device Management', "storkmdm"),
         'version'        => PLUGIN_STORKMDM_VERSION,
         'author'         => $author,
         'license'        => 'GPLv2+',
         'homepage'       => '',
         'minGlpiVersion' => PLUGIN_STORKMDM_GLPI_MIN_VERSION);
}

// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_storkmdm_check_prerequisites() {
   global $CFG_GLPI;
   $prerequisitesSuccess = true;

   if (version_compare(GLPI_VERSION, PLUGIN_STORKMDM_GLPI_MIN_VERSION, 'lt') || version_compare(GLPI_VERSION, PLUGIN_STORKMDM_GLPI_MAX_VERSION, 'ge')) {
      echo "This plugin requires GLPi >= " . PLUGIN_STORKMDM_GLPI_MIN_VERSION . " and GLPI < " . PLUGIN_STORKMDM_GLPI_MAX_VERSION . "<br>";
      $prerequisitesSuccess = false;
   }

   if (version_compare(PHP_VERSION, PLUGIN_STORKMDM_PHP_MIN_VERSION, 'lt')) {
      echo "This plugin requires PHP >=" . PLUGIN_STORKMDM_PHP_MIN_VERSION . "<br>";
      $prerequisitesSuccess = false;
   }

   if (! function_exists("openssl_random_pseudo_bytes")) {
      echo "This plugin requires PHP compiled with --with-openssl<br>";
      $prerequisitesSuccess = false;
   }

   if (! function_exists("socket_create")) {
      echo "This plugin requires PHP compiled with --enable-sockets<br>";
      $prerequisitesSuccess = false;
   }

   if (!isset($CFG_GLPI["url_base"]) || strlen($CFG_GLPI["url_base"]) == 0) {
      echo "This plugin requires GLPi url base set<br>";
      $prerequisitesSuccess = false;
   }

   if (! extension_loaded('zip')) {
      echo "This plugin requires PHP ZIP extension<br>";
      $prerequisitesSuccess = false;
   }

   $plugin = new Plugin();
   if ( !($plugin->isInstalled('fusioninventory') && $plugin->isActivated('fusioninventory')) ) {
      echo "This plugin requires Fusioninventory for GLPi<br>";
      $prerequisitesSuccess = false;
   }
   return $prerequisitesSuccess;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_storkmdm_check_config() {
   return true;
}
