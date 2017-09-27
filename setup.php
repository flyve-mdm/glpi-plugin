<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2016 by the FusionInventory Development Team.
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
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

define('PLUGIN_FLYVEMDM_VERSION', '2.0.0-dev');
// is or is not an official release of the plugin
define('PLUGIN_FLYVEMDM_IS_OFFICIAL_RELEASE', false);
// Minimal GLPI version, inclusive
define('PLUGIN_FLYVEMDM_GLPI_MIN_VERSION', '9.2');
// Maximum GLPI version, exclusive
define('PLUGIN_FLYVEMDM_GLPI_MAX_VERSION', '9.3');

define('PLUGIN_FLYVEMDM_ROOT', GLPI_ROOT . '/plugins/flyvemdm');

define('PLUGIN_FLYVEMDM_AGENT_DOWNLOAD_URL', 'https://play.google.com/store/apps/details?id=com.teclib.flyvemdm');

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

   if ($plugin->isActivated('flyvemdm')) {
      require_once(__DIR__ . '/vendor/autoload.php');
      if (!class_exists('GlpiLocalesExtension')) {
         require_once(__DIR__ . '/lib/GlpiLocalesExtension.php');
      }

      plugin_flyvemdm_registerClasses();
      plugin_flyvemdm_addHooks();

      $CFG_GLPI['fleet_types'] = [PluginFlyvemdmFile::class, PluginFlyvemdmPackage::class];

      Html::requireJs('charts');
      $PLUGIN_HOOKS['add_css']['flyvemdm'][] = "css/style.css";
      // Warning 'pluginflyvemdmmenu' MUST be lower case
      $CFG_GLPI['javascript']['admin']['pluginflyvemdmmenu']['Menu'] = ['charts'];
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
      $PLUGIN_HOOKS['config_page']['flyvemdm'] = 'front/config.form.php';
   }

   // Hooks for the plugin : objects inherited from GLPI or
   $PLUGIN_HOOKS['item_add']['flyvemdm']     = [
      Entity::class                    => 'plugin_flyvemdm_hook_entity_add',
   ];
   $PLUGIN_HOOKS['item_purge']['flyvemdm']   = [
      Entity::class                    => 'plugin_flyvemdm_hook_entity_purge',
      Computer::class                  => 'plugin_flyvemdm_computer_purge',
   ];
   $PLUGIN_HOOKS['pre_item_purge']['flyvemdm']   = [
      PluginFlyvemdmInvitation::class => [PluginFlyvemdmInvitation::class, 'hook_pre_self_purge'],
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

// Get the name and the version of the plugin - Needed
function plugin_version_flyvemdm() {
   $author = '<a href="http://www.teclib.com">Teclib</a>';
   if (defined('GLPI_PREVER') && PLUGIN_FLYVEMDM_IS_OFFICIAL_RELEASE == false) {
      $glpiVersion = version_compare(GLPI_PREVER, PLUGIN_FLYVEMDM_GLPI_MIN_VERSION, 'lt');
   } else {
      $glpiVersion = PLUGIN_FLYVEMDM_GLPI_MIN_VERSION;
   }
   return [
      'name'           => __s('Flyve Mobile Device Management', "flyvemdm"),
      'version'        => PLUGIN_FLYVEMDM_VERSION,
      'author'         => $author,
      'license'        => 'AGPLv3+',
      'homepage'       => '',
      'minGlpiVersion' => $glpiVersion,
      'requirements'   => [
         'glpi' => [
            'min' => $glpiVersion,
            'max' => '9.3',
            'dev' => PLUGIN_FLYVEMDM_IS_OFFICIAL_RELEASE == false,
            'plugins'   => [
               'fusioninventory',
            ],
            'params' => [
               'url_base',
               'enable_api',
               'use_notifications',
               'notifications_mailing'
            ],
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
   return $prerequisitesSuccess;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
function plugin_flyvemdm_check_config() {
   return true;
}


function plugin_flyvemdm_getTemplateEngine() {
   $loader = new Twig_Loader_Filesystem(__DIR__ . '/tpl');
   $twig =  new Twig_Environment($loader, [
         'cache'        => FLYVEMDM_TEMPLATE_CACHE_PATH,
         'auto_reload'  => ($_SESSION['glpi_use_mode'] == 2),
   ]);
   $twig->addExtension(new GlpiLocalesExtension());
   return $twig;
}

// center all columns of plugin
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
