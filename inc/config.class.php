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
class PluginFlyvemdmConfig extends CommonDBTM {

   // Type reservation : https://forge.indepnet.net/projects/plugins/wiki/PluginTypesReservation
   const RESERVED_TYPE_RANGE_MIN = 11000;
   const RESERVED_TYPE_RANGE_MAX = 11049;

   const PLUGIN_FLYVEMDM_MQTT_CLIENT = "flyvemdm";

   static $config = array();

   /**
    * Display the configuration form for the plugin.
    */
   public function showForm() {
      $config = Config::getConfigurationValues('flyvemdm');

      echo '<form id="pluginFlyvemdm-config" method="post" action="./config.form.php">';
      echo '<table class="tab_cadre" cellpadding="5">';
      echo '<tr><th colspan="3">'.__('Flyve MDM settings', "flyvemdm").'</th></tr>';

      $user = new User();

      echo '<tr><th colspan="3">'.__("MQTT broker", "flyvemdm").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("mqtt broker address", "flyvemdm").'</td>';
      echo '<td><input type="text" name="mqtt_broker_address"' .
         'value="'. $config['mqtt_broker_address'] .'" />';
      echo '</td>';
      echo '<td>'. __("mqtt broker address example", "flyvemdm").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("mqtt broker internal address", "flyvemdm").'</td>';
      echo '<td><input type="text" name="mqtt_broker_internal_address"' .
         'value="'. $config['mqtt_broker_internal_address'] .'" />';
      echo '</td>';
      echo '<td>'. __("mqtt broker address example", "flyvemdm").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("mqtt broker port", "flyvemdm").'</td>';
      echo '<td><input type="number" name="mqtt_broker_port"' .
            'value="'. $config['mqtt_broker_port'] .'" min="1" max="65535" />';
      echo '</td>';
      echo '<td>'. __("mqtt broker port example", "flyvemdm").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("use TLS", "flyvemdm").'</td>';
      echo '<td>' . Dropdown::showYesNo('mqtt_broker_tls', $config['mqtt_broker_tls'], -1, array('display' => false));
      echo '</td>';
      echo '<td></td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("CA certificate", "flyvemdm").'</td>';
      echo '<td>' . Html::file(array('name' => 'CACertificateFile')) . '</td>';
      echo '</td>';
      echo '<td></td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Cipher suite (TLS enabled)", "flyvemdm").'</td>';
      echo '<td><input type="text" name="mqtt_broker_tls_ciphers"' .
            'value="'. $config['mqtt_broker_tls_ciphers'] .'" />';
      echo '</td>';
      echo '<td>'. __("mqtt broker cipher suite", "flyvemdm").'</td>';
      echo '</tr>';

      echo '<tr><td>'.__('test', "flyvemdm").'</td>';
      echo '<td><input type="button" id="pluginFlyvemdm-mqtt-test" name="mqtt-test" value="'.__('Test', "flyvemdm").'" class="submit">';
      echo '<span id="pluginFlyvemdm-test-feedback"></span>';
      echo '</td></tr>';

      echo '<tr><th colspan="3">'.__("Client certificate server (Broker MQTT with TLS enabled)", "flyvemdm").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("use client certificates", "flyvemdm").'</td>';
      echo '<td>' . Dropdown::showYesNo('mqtt_use_client_cert', $config['mqtt_use_client_cert'], -1, array('display' => false));
      echo '</td>';
      echo '<td></td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Ssl certificate server for MQTT clients", "flyvemdm").'</td>';
      echo '<td><input type="text" name="ssl_cert_url"' .
            'value="'. $config['ssl_cert_url'] .'" />';
      echo '</td>';
      echo '<td>'. __("https://cert.domain.com/path/to/service", "flyvemdm").'</td>';
      echo '</tr>';

      echo '<tr><th colspan="3">'.__('Debug mode', "flyvemdm").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Enable explicit enrolment failures", "flyvemdm").'</td>';
      echo '<td>' . Dropdown::showYesNo('debug_enrolment', $config['debug_enrolment'], -1, array('display' => false));
      echo '</td>';
      echo '<td>'. '' .'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Disable token expiration on successful enrolment", "flyvemdm").'</td>';
      echo '<td>' . Dropdown::showYesNo('debug_noexpire', $config['debug_noexpire'], -1, array('display' => false));
      echo '</td>';
      echo '<td>'. '' .'</td>';
      echo '</tr>';

      echo '<tr><th colspan="3">'.__('Bug collector', "flyvemdm").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Android bug collector URL", "flyvemdm").'</td>';
      echo '<td><input type="text" name="android_bugcollecctor_url"' .
            'value="'. $config['android_bugcollecctor_url'] .'" />';
      echo '</td>';
      echo '<td>'. __("https://bugreport.flyvemdm.com/path/to/service", "flyvemdm").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Android bug collector user", "flyvemdm").'</td>';
      echo '<td><input type="text" name="android_bugcollector_login"' .
            'value="'. $config['android_bugcollector_login'] .'" />';
      echo '</td>';
      echo '<td></td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Android bug collector password", "flyvemdm").'</td>';
      echo '<td><input type="password" name="android_bugcollector_passwd"' .
            'value="'. $config['android_bugcollector_passwd'] .'" />';
      echo '</td>';
      echo '<td></td>';
      echo '</tr>';

      echo '<tr><th colspan="3">'.__('Restrictions', "flyvemdm").'</th></tr>';

      echo '<tr class="tab_bg_1">';
      echo '<td>'. __("Default device limit per entity", "flyvemdm").'</td>';
      echo '<td><input type="number" name="default_device_limit"' .
            'value="'. $config['default_device_limit'] .'" min="0" />';
      echo '</td>';
      echo '<td>'. __("No more devices than this quantity are allowed per entity by default (0 = no limitation)", "flyvemdm").'</td>';
      echo '</tr>';

      echo '<tr class="tab_bg_1"><td class="center" colspan="2">';
      echo '<input type="hidden" name="id" value="1" class="submit">';
      echo '<input type="hidden" name="config_context" value="flyvemdm">';
      echo '<input type="hidden" name="config_class" value="PluginFlyvemdmConfig">';
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
    * {@inheritDoc}
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      return $input;
   }

   public static function undiscloseConfigValue($fields) {
      if ($fields['context'] == 'flyvemdm'
            && in_array($fields['name'], array('mqtt_passwd'))) {
         unset($fields['value']);
      }
      return $fields;
   }
}
