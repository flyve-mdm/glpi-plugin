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
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

// Version of the plugin
define('PLUGIN_FLYVEMDM_VERSION', '2.0.0-rc.2');
// Schema version of this version
define('PLUGIN_FLYVEMDM_SCHEMA_VERSION', '2.1');
// is or is not an official release of the plugin
define('PLUGIN_FLYVEMDM_IS_OFFICIAL_RELEASE', true);
// Minimal GLPI version, inclusive
define('PLUGIN_FLYVEMDM_GLPI_MIN_VERSION', '9.3');
// Maximum GLPI version, exclusive
define('PLUGIN_FLYVEMDM_GLPI_MAX_VERSION', '9.4');

define('PLUGIN_FLYVEMDM_ROOT', GLPI_ROOT . '/plugins/flyvemdm');

define('PLUGIN_FLYVEMDM_AGENT_DOWNLOAD_URL', 'https://play.google.com/store/apps/details?id=org.flyve.mdm.agent');

define('PLUGIN_FLYVEMDM_AGENT_BETA_DOWNLOAD_URL', 'https://play.google.com/apps/testing/org.flyve.mdm.agent');

define('PLUGIN_FLYVEMDM_APPLE_DEVELOPER_ENTERPRISE_URL', 'https://developer.apple.com/programs/enterprise/');

define('PLUGIN_FLYVEMDM_DEEPLINK', 'http://flyve.org/deeplink/');

if (!defined('FLYVEMDM_CONFIG_PATH')) {
   define('FLYVEMDM_CONFIG_PATH', GLPI_PLUGIN_DOC_DIR . '/flyvemdm');
}

if (!defined('FLYVEMDM_CONFIG_CACERTMQTT')) {
   define('FLYVEMDM_CONFIG_CACERTMQTT', FLYVEMDM_CONFIG_PATH . '/CACert-mqtt.crt');
}

if (!defined('FLYVEMDM_PACKAGE_PATH')) {
   define('FLYVEMDM_PACKAGE_PATH', GLPI_PLUGIN_DOC_DIR . '/flyvemdm/package');
}

if (!defined('FLYVEMDM_FILE_PATH')) {
   define('FLYVEMDM_FILE_PATH', GLPI_PLUGIN_DOC_DIR . '/flyvemdm/file');
}

if (!defined('FLYVEMDM_INVENTORY_PATH')) {
   define('FLYVEMDM_INVENTORY_PATH', GLPI_PLUGIN_DOC_DIR . '/flyvemdm/inventory');
}

if (!defined('FLYVEMDM_TEMPLATE_CACHE_PATH')) {
   define('FLYVEMDM_TEMPLATE_CACHE_PATH', GLPI_PLUGIN_DOC_DIR . '/flyvemdm/cache');
}

// Init the hooks of the plugins -Needed
function plugin_init_flyvemdm() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['flyvemdm'] = true;
   $PLUGIN_HOOKS['undiscloseConfigValue']['flyvemdm'] = [PluginFlyvemdmConfig::class,
                                                         'undiscloseConfigValue'];

   $plugin = new Plugin();

   $config = Config::getConfigurationValues('flyvemdm', ['version']);
   if (isset($config['version']) && $config['version'] != PLUGIN_FLYVEMDM_VERSION) {
      $plugin->getFromDBbyDir('flyvemdm');
      $plugin->update([
            'id'     => $plugin->getID(),
            'state'  => Plugin::NOTUPDATED
      ]);
   }

   if (!$plugin->getFromDBbyDir('flyvemdm')) {
      // nothing more to do at this moment
      return;
   }

   $state = $plugin->getField('state');
   if ($state != Plugin::NOTACTIVATED) {
      require_once(__DIR__ . '/vendor/autoload.php');
   }

   if ($state == Plugin::ACTIVATED) {
      if (!class_exists('GlpiLocalesExtension')) {
         require_once(__DIR__ . '/lib/GlpiLocalesExtension.php');
      }

      plugin_flyvemdm_registerClasses();
      plugin_flyvemdm_addHooks();

      Html::requireJs('charts');
      $PLUGIN_HOOKS['add_css']['flyvemdm'][] = "css/style.css";
      // Warning 'pluginflyvemdmmenu' MUST be lower case
      $CFG_GLPI['javascript']['admin']['pluginflyvemdmmenu']['Menu'] = ['charts'];

      if (strpos($_SERVER['REQUEST_URI'], "plugins/flyvemdm/front/agent.form.php") !== false) {
         $PLUGIN_HOOKS['add_css']['flyvemdm'][] = 'lib/leaflet-1.0.3/leaflet.css';
         $PLUGIN_HOOKS['add_javascript']['flyvemdm'][] = 'lib/leaflet-1.0.3/leaflet.js';
      }
   }
}

/**
 * Register classes
 */
function plugin_flyvemdm_registerClasses() {
   Plugin::registerClass(PluginFlyvemdmAgent::class);
   Plugin::registerClass(PluginFlyvemdmFleet::class);
   Plugin::registerClass(PluginFlyvemdmPolicy::class);
   Plugin::registerClass(PluginFlyvemdmTask::class);
   Plugin::registerClass(PluginFlyvemdmProfile::class,
         ['addtabon' => Profile::class]);
   Plugin::registerClass(PluginFlyvemdmGeolocation::class);
   Plugin::registerClass(PluginFlyvemdmPackage::class);
   Plugin::registerClass(PluginFlyvemdmFile::class);
   Plugin::registerClass(PluginFlyvemdmInvitation::class,
         ['notificationtemplates_types' => true, /* 'document_types' => true */]);
   Plugin::registerClass(PluginFlyvemdmEntityConfig::class,
         ['addtabon' => Entity::class]);

   // Dropdowns
   Plugin::registerClass(PluginFlyvemdmWellknownpath::class);
   Plugin::registerClass(PluginFlyvemdmPolicyCategory::class);
}

/**
 * Adds all hooks the plugin needs
 */
function plugin_flyvemdm_addHooks() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['change_profile']['flyvemdm'] = [PluginFlyvemdmProfile::class, 'changeProfile'];

   //if glpi is loaded
   if (Session::getLoginUserID()) {
      $PLUGIN_HOOKS['menu']['flyvemdm'] = true;
   }
   $PLUGIN_HOOKS['post_init']['flyvemdm'] = 'plugin_flyvemdm_postinit';

   if (Session::haveRight(PluginFlyvemdmProfile::$rightname, PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE)) {
      // Define menu entries
      $PLUGIN_HOOKS['menu_toadd']['flyvemdm'] = [
         'admin'  => 'PluginFlyvemdmMenu',
      ];
      $PLUGIN_HOOKS['autoinventory_information']['flyvemdm'] = [
         'Computer' => [PluginFlyvemdmAgent::class, 'displayTabContentForComputer'],
      ];
      $PLUGIN_HOOKS['config_page']['flyvemdm'] = 'front/config.form.php';
   }

   // Hooks for the plugin : objects inherited from GLPI or
   $PLUGIN_HOOKS['item_add']['flyvemdm']     = [
      Entity::class                    => 'plugin_flyvemdm_hook_entity_add',
   ];
   $PLUGIN_HOOKS['item_purge']['flyvemdm']   = [
      Entity::class                    => 'plugin_flyvemdm_hook_entity_purge',
      Computer::class                  => 'plugin_flyvemdm_hook_computer_purge',
   ];
   $PLUGIN_HOOKS['pre_item_purge']['flyvemdm']   = [
      PluginFlyvemdmInvitation::class => 'plugin_flyvemdm_hook_pre_invitation_purge',
      Document::class                 => [PluginFlyvemdmInvitation::class, 'hook_pre_document_purge'],
      Profile_User::class             => 'plugin_flyvemdm_hook_pre_profileuser_purge',
   ];

   // Notifications
   $PLUGIN_HOOKS['item_get_events']['flyvemdm'] = [];
   $PLUGIN_HOOKS['item_get_datas']['flyvemdm'] = [];

   $PLUGIN_HOOKS['item_get_events']['flyvemdm'][PluginFlyvemdmNotificationTargetInvitation::class] = [
      PluginFlyvemdmNotificationTargetInvitation::class, 'addEvents'
   ];
   $PLUGIN_HOOKS['item_get_datas']['flyvemdm'][PluginFlyvemdmNotificationTargetInvitation::class] = [
      PluginFlyvemdmNotificationTargetInvitation::class, 'getAdditionalDatasForTemplate'
   ];

   $PLUGIN_HOOKS['use_massive_action']['flyvemdm'] = 1;

   $PLUGIN_HOOKS['import_item']['flyvemdm'] = [Computer::class => ['Plugin']];
}

/**
 * Get the name and the version of the plugin - Needed
 * @return array
 */
function plugin_version_flyvemdm() {
   $author = '<a href="http://www.teclib.com">Teclib</a>';
   $requirements = [
      'name'           => __s('Flyve Mobile Device Management', 'flyvemdm'),
      'version'        => PLUGIN_FLYVEMDM_VERSION,
      'author'         => $author,
      'license'        => 'AGPL-3.0-or-later',
      'homepage'       => 'https://flyve-mdm.com/',
      'minGlpiVersion' => PLUGIN_FLYVEMDM_GLPI_MIN_VERSION,
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_FLYVEMDM_GLPI_MIN_VERSION,
            'plugins'   => [
               'fusioninventory',
            ],
            'params' => [],
         ],
         'php' => [
            'exts'   => [
               'OpenSSL'   => [
                  'required'  => true,
               ],
               'sockets'   => [
                  'required'  => true,
                  'function'  => 'socket_create'
               ],
               'zip'       => [
                  'required'  => true,
               ],
               'gd'        => [
                  'required'  => true
               ]
            ]
         ]
      ]
    ];

   if (PLUGIN_FLYVEMDM_IS_OFFICIAL_RELEASE) {
      // This is not a development version
      $requirements['requirements']['glpi']['max'] = PLUGIN_FLYVEMDM_GLPI_MAX_VERSION;
   }
   return $requirements;
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_flyvemdm_check_prerequisites() {
   $prerequisitesSuccess = true;

   if (!is_readable(__DIR__ . '/vendor/autoload.php') || !is_file(__DIR__ . '/vendor/autoload.php')) {
      echo "Run composer install --no-dev in the plugin directory<br>";
      $prerequisitesSuccess = false;
   }

   if (version_compare(GLPI_VERSION, PLUGIN_FLYVEMDM_GLPI_MIN_VERSION, 'lt')
       || PLUGIN_FLYVEMDM_IS_OFFICIAL_RELEASE && version_compare(GLPI_VERSION, PLUGIN_FLYVEMDM_GLPI_MAX_VERSION, 'ge')) {
      echo "This plugin requires GLPi >= " . PLUGIN_FLYVEMDM_GLPI_MIN_VERSION . " and GLPI < " . PLUGIN_FLYVEMDM_GLPI_MAX_VERSION . "<br>";
      $prerequisitesSuccess = false;
   }

   return $prerequisitesSuccess;
}

/**
 * Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 * @return bool
 */
function plugin_flyvemdm_check_config($verbose = false) {
   return true;
}

/**
 * @return Twig_Environment
 */
function plugin_flyvemdm_getTemplateEngine() {
   $loader = new Twig_Loader_Filesystem(__DIR__ . '/tpl');
   $twig =  new Twig_Environment($loader, [
         'cache'        => FLYVEMDM_TEMPLATE_CACHE_PATH,
         'auto_reload'  => ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE),
   ]);
   $twig->addExtension(new GlpiLocalesExtension());
   return $twig;
}

/**
 * center all columns of plugin
 * @param string $itemtype
 * @param integer $ID
 * @param mixed $data
 * @param integer $num
 * @return string
 */
function plugin_flyvemdm_displayConfigItem($itemtype, $ID, $data, $num) {
   return "align='center'";
}

/**
 * Show the last SQL error, logs its backtrace and dies
 * @param Migration $migration
 */
function plugin_flyvemdm_upgrade_error(Migration $migration) {
   global $DB;

   $error = $DB->error();
   $migration->log($error . "\n" . Toolbox::backtrace(false, '', ['Toolbox::backtrace()']), false);
   die($error . "<br><br> Please, check migration log");
}
