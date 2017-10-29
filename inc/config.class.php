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

   static $config = [];

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
            $tabs[1] = __('General configuration', 'flyvemdm');
            $tabs[2] = __('Messge queue', 'flyvemdm');
            $tabs[3] = __('Debug', 'flyvemdm');
            return $tabs;
            break;
      }

      return '';
   }

   /**
    * @param $item         CommonGLPI object
    * @param $tabnum       (default 1)
    * @param $withtemplate (default 0)
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1:
               $item->showFormGeneral();
               break;

            case 2:
               $item->showFormMessageQueue();
               break;

            case 3:
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
         if (!$documentType->getFromDBByQuery("WHERE LOWER(`ext`)='$extension'")) {
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

      $fields['mqtt_broker_tls'] = Dropdown::showYesNo(
                                                          'mqtt_broker_tls', $fields['mqtt_broker_tls'],
                                                          -1,
                                                          ['display' => false]
                                                       );
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
      $fields['CACertificateFile'] = Html::file([
         'name'      => 'CACertificateFile',
         'display'   => false,
      ]);
      $data = [
         'config' => $fields
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config.html', $data);

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

      $fields['mqtt_broker_tls'] = Dropdown::showYesNo(
         'mqtt_broker_tls', $fields['mqtt_broker_tls'],
         -1,
         ['display' => false]
      );

      $fields['mqtt_use_client_cert'] = Dropdown::showYesNo(
         'mqtt_use_client_cert',
         $fields['mqtt_use_client_cert'],
         -1,
         ['display' => false]
      );

      $data = [
         'config' => $fields
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config-messagequeue.html', $data);

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

      $data = [
         'config' => $fields
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config-debug.html', $data);

      Html::closeForm();
   }

   /**
    * Initializes the instance if the item with default values
    */
   public function post_getEmpty() {
      $this->fields['id'] = 1;
      $this->fields['mqtt_broker_address'] = '127.0.0.1';
      $this->fields['mqtt_broker_port'] = '1883';
   }

   /**
    * Hook for config validation before update
    * @param array $input configuration settings
    * @return array
    */
   public static function configUpdate($input) {
      // process certificates update
      if (isset($input['_CACertificateFile'])) {
         if (isset($input['_CACertificateFile'][0])) {
            $file = GLPI_TMP_DIR . "/" . $input['_CACertificateFile'][0];
            if (is_writable($file)) {
               rename($file, FLYVEMDM_CONFIG_CACERTMQTT);
            }
         }
      }
      unset($input['_CACertificateFile']);
      unset($input['_tag_CACertificateFile']);
      unset($input['CACertificateFile']);
      return $input;
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
}
