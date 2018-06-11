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
 * Class PluginFlyvemdmPolicyApn
 * @since 2.1
 */
class PluginFlyvemdmPolicyApn extends PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /**
    * PluginFlyvemdmPolicyApn constructor.
    * @param PluginFlyvemdmPolicy $policy
    */
   public function __construct(PluginFlyvemdmPolicy $policy) {
      parent::__construct($policy);
      $this->symbol = $policy->getField('symbol');
      $this->unicityRequired = ($policy->getField('unicity') != '0');
      $this->group = $policy->getField('group');
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @return bool
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      // Check the value exists
      $inputNames = ['name', 'apn'];
      foreach ($inputNames as $key){
         if (!isset($value[$key])) {
            Session::addMessageAfterRedirect(sprintf(__('A value for "%s" is mandatory', 'flyvemdm'), $value));
            return false;
         }
      }
      return true;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param int $itemId
    * @return array|bool
    */
   public function getMqttMessage($value, $itemtype, $itemId) {
      if (!$this->integrityCheck($value, $itemtype, $itemId)) {
         return false;
      }
      $array = [
         $this->symbol => $value
      ];
      return $array;
   }

   public function showValueInput($value = '', $itemType = '', $itemId = 0) {

      $value = json_decode($value, JSON_OBJECT_AS_ARRAY);

      $fields = [
         'apn_name'           => ['label' => 'Name', 'type' => 'text'],
         'apn_apn'            => ['label' => 'APN', 'type' => 'text'],
         'apn_proxy'          => ['label' => 'Proxy', 'type' => 'text'],
         'apn_port'           => ['label' => 'Port', 'type' => 'text'],
         'apn_username'       => ['label' => 'Username', 'type' => 'text'],
         'apn_password'       => ['label' => 'Password', 'type' => 'password'],
         'apn_server'         => ['label' => 'Server', 'type' => 'text'],
         'apn_mmsc'           => ['label' => 'MMSC', 'type' => 'text'],
         'apn_proxy_mms'      => ['label' => 'Proxy MMS', 'type' => 'text'],
         'apn_proxy_mms_port' => ['label' => 'Proxy MMC port', 'type' => 'text'],
         'apn_mmc'            => ['label' => 'MMC', 'type' => 'text'],
         'apn_mnc'            => ['label' => 'MNC', 'type' => 'text'],
      ];

      $data = [];
      foreach ($fields as $inputName => $inputOptions) {
         $data['inputs'][] = [
            'id'    => $inputName,
            'label' => $inputOptions['label'],
            'type'  => $inputOptions['type'],
            'value' => ($value[$inputName]) ? $value[$inputName] : '',
         ];
      }

      $apnAuthType = Dropdown::showFromArray('apn_auth_type',
         ['No authentication', 'PAP', 'CHAP', 'CHAP/PAP'], ['display' => false]);
      $apnType = Dropdown::showFromArray('apn_type',
         ['default', 'mms', 'supl', 'dun', 'hipri', 'fota'], ['display' => false]);

      $data['apnAuthType'] = [
         'label' => 'Authentication Type',
         'dropdown' => $apnAuthType,
      ];
      $data['apnType'] = [
         'label' => 'APN Type',
         'dropdown' => $apnType,
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      return $twig->render('policy_apn_form.html.twig', $data);
   }

}