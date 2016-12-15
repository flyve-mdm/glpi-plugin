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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginStorkmdmConfig extends CommonDBTM {

   // Type reservation : https://forge.indepnet.net/projects/plugins/wiki/PluginTypesReservation
   const RESERVED_TYPE_RANGE_MIN = 11000;
   const RESERVED_TYPE_RANGE_MAX = 11049;

   const PLUGIN_STORKMDM_MQTT_CLIENT = "storkmdm";

   const SERVICE_ACCOUNT_NAME = 'storknologin';

   static $config = array();

   /**
    * Uninstall process
    */
   public static function uninstall() {
      global $DB;

      // To cleanup display preferences if any
      //$displayPreference = new DisplayPreference();
      //$displayPreference->deleteByCriteria(array("`num` >= " . self::RESERVED_TYPE_RANGE_MIN . " AND `num` <= " . self::RESERVED_TYPE_RANGE_MAX));

      Config::deleteConfigurationValues('storkmdm');
   }

   /**
    * Display the configuration form for the plugin.
    */
   public function showForm() {
      $config = Config::getConfigurationValues('storkmdm');

      echo '<form id="pluginStorkmdm-config" method="post" action="./config.form.php">';
      echo '<table class="tab_cadre" cellpadding="5">';
      echo '<tr><th colspan="3">'.__('Stork MDM settings', "storkmdm").'</th></tr>';

      $user = new User();
      if ($user->getFromDBbyName(self::SERVICE_ACCOUNT_NAME)) {
         $apiKey = $user->getField('personal_token');
      } else {
         $apiKey = '';
      }

      echo '<tr><th colspan="3">'.__("MQTT broker", "storkmdm").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("mqtt broker address", "storkmdm").'</td>';
      echo '<td><input type="text" name="mqtt_broker_address"' .
         'value="'. $config['mqtt_broker_address'] .'" />';
      echo '</td>';
      echo '<td>'. __("mqtt broker address example", "storkmdm").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("mqtt broker internal address", "storkmdm").'</td>';
      echo '<td><input type="text" name="mqtt_broker_internal_address"' .
         'value="'. $config['mqtt_broker_internal_address'] .'" />';
      echo '</td>';
      echo '<td>'. __("mqtt broker address example", "storkmdm").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("mqtt broker port", "storkmdm").'</td>';
      echo '<td><input type="number" name="mqtt_broker_port"' .
            'value="'. $config['mqtt_broker_port'] .'" min="1" max="65535" />';
      echo '</td>';
      echo '<td>'. __("mqtt broker port example", "storkmdm").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("use TLS", "storkmdm").'</td>';
      echo '<td>' . Dropdown::showYesNo('mqtt_broker_tls', $config['mqtt_broker_tls'], -1, array('display' => false));
      echo '</td>';
      echo '<td></td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("CA certificate", "storkmdm").'</td>';
      echo '<td>' . Html::file(array('name' => 'CACertificateFile')) . '</td>';
      echo '</td>';
      echo '<td></td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Cipher suite (TLS enabled)", "storkmdm").'</td>';
      echo '<td><input type="text" name="mqtt_broker_tls_ciphers"' .
            'value="'. $config['mqtt_broker_tls_ciphers'] .'" />';
      echo '</td>';
      echo '<td>'. __("mqtt broker cipher suite", "storkmdm").'</td>';
      echo '</tr>';

      echo '<tr><td>'.__('test', "storkmdm").'</td>';
      echo '<td><input type="button" id="pluginStorkmdm-mqtt-test" name="mqtt-test" value="'.__('Test', "storkmdm").'" class="submit">';
      echo '<span id="pluginStorkmdm-test-feedback"></span>';
      echo '</td></tr>';

      echo '<tr><th colspan="3">'.__("Client certificate server (Broker MQTT with TLS enabled)", "storkmdm").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("use client certificates", "storkmdm").'</td>';
      echo '<td>' . Dropdown::showYesNo('mqtt_use_client_cert', $config['mqtt_use_client_cert'], -1, array('display' => false));
      echo '</td>';
      echo '<td></td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Ssl certificate server for MQTT clients", "storkmdm").'</td>';
      echo '<td><input type="text" name="ssl_cert_url"' .
            'value="'. $config['ssl_cert_url'] .'" />';
      echo '</td>';
      echo '<td>'. __("https://cert.domain.com/path/to/service", "storkmdm").'</td>';
      echo '</tr>';

      echo '<tr><th colspan="3">'.__('Debug mode', "storkmdm").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Enable explicit enrolment failures", "storkmdm").'</td>';
      echo '<td>' . Dropdown::showYesNo('debug_enrolment', $config['debug_enrolment'], -1, array('display' => false));
      echo '</td>';
      echo '<td>'. '' .'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Disable token expiration on successful enrolment", "storkmdm").'</td>';
      echo '<td>' . Dropdown::showYesNo('debug_noexpire', $config['debug_noexpire'], -1, array('display' => false));
      echo '</td>';
      echo '<td>'. '' .'</td>';
      echo '</tr>';

      echo '<tr><th colspan="3">'.__('Restrictions', "storkmdm").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Default device limit per entity", "storkmdm").'</td>';
      echo '<td><input type="number" name="default_device_limit"' .
            'value="'. $config['default_device_limit'] .'" min="0" />';
      echo '</td>';
      echo '<td>'. __("No more devices than this quantity are allowed per entity by default (0 = no limitation)", "storkmdm").'</td>';
      echo '</tr>';

      echo '<tr><th colspan="3">'.__('Frontend setup', "storkmdm").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Service's User Token", "storkmdm").'</td>';
      echo '<td>' . $apiKey;
      echo '</td>';
      echo '<td>'. __("To be saved in frontend's app/config.js file", "storkmdm").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1"><td class="center" colspan="2">';
      echo '<input type="hidden" name="id" value="1" class="submit">';
      echo '<input type="hidden" name="config_context" value="storkmdm">';
      echo '<input type="hidden" name="config_class" value="PluginStorkmdmConfig">';
      echo '<input type="submit" name="update" value="'.__('Save').'" class="submit">';
      echo '</td></tr>';

      echo '</table>';

      Html::closeForm();
   }

   /**
    * {@inheritDoc}
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
               rename($file, STORKMDM_CONFIG_CACERTMQTT);
            }
         }
      }
      unset($input['_CACertificateFile']);
      unset($input['_tag_CACertificateFile']);
      unset($input['CACertificateFile']);
      return $input;
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      return $input;
   }

}
