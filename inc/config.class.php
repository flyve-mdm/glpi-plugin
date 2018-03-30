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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmConfig extends CommonDBTM {
   // From CommonGLPI
   protected $displaylist         = false;

   // From CommonDBTM
   public $auto_message_on_action = false;
   public $showdebug              = true;

   static $rightname              = 'config';

   // Type reservation : https://forge.indepnet.net/projects/plugins/wiki/PluginTypesReservation
   const RESERVED_TYPE_RANGE_MIN = 11000;
   const RESERVED_TYPE_RANGE_MAX = 11049;

   const PLUGIN_FLYVEMDM_MQTT_CLIENT = 'flyvemdm';

   // first and last steps of the welcome pages of wizard
   const WIZARD_WELCOME_BEGIN = 1;
   const WIZARD_WELCOME_END = 1;

   // first and last steps of requirement pages of wizard
   const WIZARD_REQUIREMENT_BEGIN = 100;
   const WIZARD_REQUIREMENT_END = 105;

   // first and last steps of the MQTT pages of wizard
   const WIZARD_MQTT_BEGIN = 106;
   const WIZARD_MQTT_END = 109;

   const WIZARD_FINISH = -1;
   static $config = [];

   /**
    * @param string|null $classname
    * @return string
    */
   public static function getTable($classname = null) {
      return Config::getTable();
   }

   /**
    * Gets permission to create an instance of the itemtype
    * @return boolean true if permission granted, false otherwise
    */
   public static function canCreate() {
      return (!isAPI() && parent::canCreate());
   }

   /**
    * Gets permission to view an instance of the itemtype
    * @return boolean true if permission granted, false otherwise
    */
   public static function canView() {
      return (!isAPI() && parent::canView());
   }

   /**
    * Gets permission to update an instance of the itemtype
    * @return boolean true if permission granted, false otherwise
    */
   public static function canUpdate() {
      return (!isAPI() && parent::canUpdate());
   }

   /**
    * Gets permission to delete an instance of the itemtype
    * @return boolean true if permission granted, false otherwise
    */
   public static function canDelete() {
      return (!isAPI() && parent::canDelete());
   }

   /**
    * Gets permission to purge an instance of the itemtype
    * @return boolean true if permission granted, false otherwise
    */
   public static function canPurge() {
      return (!isAPI() && parent::canPurge());
   }

   /**
    * Define tabs available for this itemtype
    * @return array
    */
   public function defineTabs($options = []) {
      $tab = [];
      $this->addStandardTab(__CLASS__, $tab, $options);

      return $tab;
   }

   /**
    * @param CommonGLPI $item
    * @param integer $withtemplate
    * @return string
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item->getType()) {
         case __CLASS__:
            $tabs = [];
            $config = Config::getConfigurationValues('flyvemdm', ['show_wizard']);
            if ($config['show_wizard'] !== '0') {
               $tabs[1] = __('Installation wizard', 'flyvemdm');
            }
            $tabs[2] = __('General configuration', 'flyvemdm');
            $tabs[3] = __('Message queue', 'flyvemdm');
            $tabs[4] = __('Debug', 'flyvemdm');
            return $tabs;
            break;
      }

      return '';
   }

   /**
    * @param CommonGLPI $item object
    * @param integer $tabnum (default 1)
    * @param integer $withtemplate (default 0)
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1:
               $item->showFormWizard();
               break;

            case 2:
               $item->showFormGeneral();
               break;

            case 3:
               $item->showFormMessageQueue();
               break;

            case 4:
               $item->showFormDebug();
               break;
         }
      }

   }

   /**
    * adds document types needed by the plugin in GLPI configuration
    */
   public function addDocumentTypes() {
      $extensions = [
            'apk'    => 'Android application package',
            'upk'    => 'Uhuru application package'
      ];

      foreach ($extensions as $extension => $name) {
         $documentType = new DocumentType();
         if (!$documentType->getFromDBByCrit(['ext' => $extension])) {
            $documentType->add([
                  'name'            => $name,
                  'ext'             => $extension,
                  'is_uploadable'   => '1',
            ]);
         }
      }
   }

   /**
    * Displays the general configuration form for the plugin.
    */
   public function showFormGeneral() {
      $canedit = PluginFlyvemdmConfig::canUpdate();
      if ($canedit) {
         echo '<form name="form" id="pluginFlyvemdm-config" method="post" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
      }

      $fields = Config::getConfigurationValues('flyvemdm');
      $fields['android_bugcollector_passwd_placeholder'] = __('Bugcollector password', 'flyvemdm');
      if (strlen($fields['android_bugcollector_passwd']) > 0) {
         $fields['android_bugcollector_passwd_placeholder'] = '******';
      }
      unset($fields['android_bugcollector_passwd']);

      $fields['computertypes_id'] = ComputerType::dropdown([
                                                            'display' => false,
                                                            'name'   => 'computertypes_id',
                                                            'value'  => $fields['computertypes_id'],
                                                          ]);
      $fields['agentusercategories_id'] = UserCategory::dropdown([
                                                            'display' => false,
                                                            'name'   => 'agentusercategories_id',
                                                            'value'  => $fields['agentusercategories_id'],
                                                           ]);
      $data = [
         'config' => $fields
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config.html.twig', $data);

      Html::closeForm();
   }

   /**
    * Displays the message queue configuration form for the plugin.
    */
   public function showFormMessageQueue() {
      $canedit = PluginFlyvemdmConfig::canUpdate();
      if ($canedit) {
         echo '<form name="form" id="pluginFlyvemdm-config" method="post" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
      }

      $fields = Config::getConfigurationValues('flyvemdm');
      unset($fields['android_bugcollector_passwd']);

      $fields['mqtt_tls_for_clients'] = Dropdown::showYesNo(
         'mqtt_tls_for_clients', $fields['mqtt_tls_for_clients'],
         -1,
         ['display' => false]
      );

      $fields['mqtt_tls_for_backend'] = Dropdown::showYesNo(
         'mqtt_tls_for_backend', $fields['mqtt_tls_for_backend'],
         -1,
         ['display' => false]
      );

      $fields['mqtt_use_client_cert'] = Dropdown::showYesNo(
         'mqtt_use_client_cert',
         $fields['mqtt_use_client_cert'],
         -1,
         ['display' => false]
      );

      $fields['CACertificateFile'] = Html::file([
         'name'      => 'CACertificateFile',
         'display'   => false,
      ]);

      $data = [
         'config' => $fields
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config-messagequeue.html.twig', $data);

      Html::closeForm();
   }

   /**
    * Displays the message queue configuration form for the plugin.
    */
   public function showFormDebug() {
      $canedit = PluginFlyvemdmConfig::canUpdate();
      if ($canedit) {
         echo '<form name="form" id="pluginFlyvemdm-config" method="post" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
      }

      $fields = Config::getConfigurationValues('flyvemdm');
      unset($fields['android_bugcollector_passwd']);

      $fields['debug_enrolment'] = Dropdown::showYesNo(
         'debug_enrolment',
         $fields['debug_enrolment'],
         -1,
         ['display' => false]
      );
      $fields['debug_noexpire'] = Dropdown::showYesNo(
         'debug_noexpire',
         $fields['debug_noexpire'],
         -1,
         ['display' => false]
      );
      $fields['debug_save_inventory'] = Dropdown::showYesNo(
         'debug_save_inventory',
         $fields['debug_save_inventory'],
         -1,
         ['display' => false]
      );
      $fields['show_wizard'] = Dropdown::showYesNo(
         'show_wizard',
         $fields['show_wizard'],
         -1,
         ['display' => false]
      );
      $fields['revision'] = PLUGIN_FLYVEMDM_VERSION;
      if (file_exists(PLUGIN_FLYVEMDM_ROOT . '/.git') && function_exists('exec')) {
         $fields['revision'] = exec('cd "' . __DIR__ . '" && git describe --tags');
      }

      $data = [
         'config' => $fields
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config-debug.html.twig', $data);

      Html::closeForm();
   }

   /**
    * Displays the message queue configuration form for the plugin.
    */
   public function showFormWizard() {
      $canedit = PluginFlyvemdmConfig::canUpdate();
      if ($canedit) {
         if (!isset($_SESSION['plugin_flyvemdm_wizard_step'])) {
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_WELCOME_BEGIN;
         }
         echo '<form name="form" id="pluginFlyvemdm-config" method="post" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';

         $texts = [];
         $data = [];
         $paragraph = 1;
         switch ($_SESSION['plugin_flyvemdm_wizard_step']) {
            default:
               // Nothing here for now
         }

         $data = $data + [
            'texts'  => $texts,
            'update' => $_SESSION['plugin_flyvemdm_wizard_step'] === static::WIZARD_FINISH ? __('Finish', 'flyvemdm') : __('Next', 'flyvemdm'),
            'step' => $_SESSION['plugin_flyvemdm_wizard_step'],
         ];
         $twig = plugin_flyvemdm_getTemplateEngine();
         echo $twig->render('config-wizard.html.twig', $data);

         Html::closeForm();
      }
   }

   /**
    * Initializes the instance of the item with default values
    */
   public function post_getEmpty() {
      $this->fields['id'] = 1;
      $this->fields['mqtt_broker_address'] = '127.0.0.1';
      $this->fields['mqtt_broker_port'] = '1883';
      $this->fields['mqtt_broker_tls_port'] = '8883';
   }

   /**
    * Hook for config validation before update
    * @param array $input configuration settings
    * @return array
    */
   public static function configUpdate($input) {
      if (isset($input['back'])) {
         // Going one step backwards in wizard
         return static::backwardStep();
      }

      // process certificates update
      if (isset($input['_CACertificateFile'])) {
         if (isset($input['_CACertificateFile'][0])) {
            $file = GLPI_TMP_DIR . "/" . $input['_CACertificateFile'][0];
            if (is_writable($file)) {
               rename($file, FLYVEMDM_CONFIG_CACERTMQTT);
            }
         }
      }

      if (isset($input['invitation_deeplink'])) {
         // Ensure there is a trailing slash
         if (strrpos($input['invitation_deeplink'], '/') != strlen($input['invitation_deeplink']) - 1) {
            $input['invitation_deeplink'] .= '/';
         }
      }

      if (isset($_SESSION['plugin_flyvemdm_wizard_step'])) {
         $input = static::processStep($input);
         if (count($input) > 0 && $input !== false) {
            static::forwardStep($input);
         } else {
            $input = [];
         }
      }

      unset($input['_CACertificateFile']);
      unset($input['_tag_CACertificateFile']);
      unset($input['CACertificateFile']);
      return $input;
   }

   /**
    * Does an action for the step saved in session, and defines the next step to run
    * @param array $input the data send in the submitted step
    * @return array modified input
    */
   protected static function processStep($input) {
      switch ($_SESSION['plugin_flyvemdm_wizard_step']) {
         case static::WIZARD_FINISH:
            Config::setConfigurationValues('flyvemdm', ['show_wizard' => '0']);
            break;
      }
      return $input;
   }

   /**
    * Goes one step forward in the wizard
    * @param array $input the data send in the submitted step
    */
   protected static function forwardStep($input) {
      // Choose next step depending on current step and form data
      switch ($_SESSION['plugin_flyvemdm_wizard_step']) {
         case static::WIZARD_WELCOME_END:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_REQUIREMENT_BEGIN;
            break;

         case static::WIZARD_REQUIREMENT_END:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_MQTT_BEGIN;
            break;

         case static::WIZARD_MQTT_END:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_FINISH;
            break;

         default:
            $_SESSION['plugin_flyvemdm_wizard_step']++;
      }
   }

   /**
    * Goes one step backward in the wizard
    */
   protected static function backwardStep() {
      switch ($_SESSION['plugin_flyvemdm_wizard_step']) {
         case static::WIZARD_REQUIREMENT_BEGIN:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_WELCOME_END;
            break;

         case static::WIZARD_MQTT_BEGIN:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_REQUIREMENT_END;
            break;

         case static::WIZARD_FINISH:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_MQTT_END;
            break;

         default:
            $_SESSION['plugin_flyvemdm_wizard_step']--;
      }

      return [];
   }

   /**
    * Remove the value from sensitive configuration entry
    * @param array $fields
    * @return array the filtered configuration entry
    */
   public static function undiscloseConfigValue($fields) {
      $undisclosed = [
            'mqtt_passwd',
            'android_bugcollector_passwd',
      ];

      if ($fields['context'] == 'flyvemdm'
            && in_array($fields['name'], $undisclosed)) {
         unset($fields['value']);
      }
      return $fields;
   }

   /**
    * Checks that GLPI is propery configured
    * @return boolean true if configuration matches the plugin, false otherwise
    */
   public function isGlpiConfigured() {
      $config = Config::getConfigurationValues('core', [
         'url_base',
         'enable_api',
         'use_notifications',
         'notifications_mailing'
      ]);

      if (!isset($config['url_base']) || strlen($config['url_base']) == 0) {
         return false;
      }

      if (!isset($config['enable_api']) || $config['enable_api'] == 0) {
         return false;
      }

      if (!isset($config['use_notifications']) || $config['use_notifications'] == 0) {
         return false;
      }

      if (!isset($config['notifications_mailing']) || $config['notifications_mailing'] == 0) {
         return false;
      }

      return true;
   }
}
