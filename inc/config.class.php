<?php
/*
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
 @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmConfig extends CommonDBTM {

   // Type reservation : https://forge.indepnet.net/projects/plugins/wiki/PluginTypesReservation
   const RESERVED_TYPE_RANGE_MIN = 11000;
   const RESERVED_TYPE_RANGE_MAX = 11049;

   const PLUGIN_FLYVEMDM_MQTT_CLIENT = "flyvemdm";

   static $config = array();

   /**
    * add document types needed by the plugin in GLPI configuration
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
    * Display the configuration form for the plugin.
    */
   public function showForm() {
      echo '<form id="pluginFlyvemdm-config" method="post" action="./config.form.php">';

      $fields = Config::getConfigurationValues('flyvemdm');
      unset($fields['android_bugcollector_passwd']);
      $fields['mqtt_broker_tls'] = Dropdown::showYesNo(
                                                          'mqtt_broker_tls', $fields['mqtt_broker_tls'],
                                                          -1,
                                                          array('display' => false)
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
      $fields['mqtt_use_client_cert'] = Dropdown::showYesNo(
                                                               'mqtt_use_client_cert',
                                                               $fields['mqtt_use_client_cert'],
                                                               -1,
                                                               array('display' => false)
                                                            );
      $fields['debug_enrolment'] = Dropdown::showYesNo(
                                                          'debug_enrolment',
                                                          $fields['debug_enrolment'],
                                                          -1,
                                                          array('display' => false)
                                                       );
      $fields['debug_noexpire'] = Dropdown::showYesNo(
                                                         'debug_noexpire',
                                                         $fields['debug_noexpire'],
                                                         -1,
                                                         array('display' => false)
                                                      );
      $fields['CACertificateFile'] = Html::file(array(
            'name'      => 'CACertificateFile',
            'display'   => false,
      ));
      $data = [
            'config' => $fields
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config.html', $data);

      Html::closeForm();
   }

   /**
    * @see CommonDBTM::post_getEmpty()
    */
   public function post_getEmpty() {
      $this->fields['id'] = 1;
      $this->fields['mqtt_broker_address'] = '127.0.0.1';
      $this->fields['mqtt_broker_port'] = '1883';
   }

   /**
    * Hook for config validation before update
    * @param array $input
    */
   public static function configUpdate($input) {
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
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      return $input;
   }

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
