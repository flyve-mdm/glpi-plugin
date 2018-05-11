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


class PluginFlyvemdmPolicyM2m extends PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /** @var string $server name or IP of the server to use for M2M */

   /** @var integer $port port to use for M2M */

   /** @var integer $tls use TLS for M2M (1 for true, 0 for false) */

   /**
    * PluginFlyvemdmPolicyInteger constructor.
    * @param PluginFlyvemdmPolicy $policy
    */
   public function __construct(PluginFlyvemdmPolicy $policy) {
      parent::__construct($policy);
      $defaultProperties = [
         'server' => '',
         'port' => '0',
         'tls'  => '0',
      ];
      $propertyCollection = $this->jsonDecodeProperties($policy->getField('type_data'),
         $defaultProperties);
      $this->server = $propertyCollection['server'];
      $this->port = $propertyCollection['port'];
      $this->tls = $propertyCollection['tls'];

      $this->symbol = $policy->getField('symbol');
      $this->unicityRequired = ($policy->getField('unicity') != '0');
      $this->group = $policy->getField('group');
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    *
    * @return bool
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      $keys = [
         'server',
         'port',
         'tls'
      ];
      $settingSufficient = false;
      foreach ($keys as $key) {
         if (!isset($value[$key])) {
            Session::addMessageAfterRedirect(__('All parameters must be specified', 'flyvemdm'));
            return false;
         }
      }

      if (empty($value['server']) && empty($value['port'] && $value['tls'] != 0 && $value['tls'] != 1)) {
         Session::addMessageAfterRedirect(__('At least one parameter must be set', 'flyvemdm'));
         return false;
      }

      return  true;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    *
    * @return array|boolean
    */
   public function getMqttMessage($value, $itemtype, $itemId) {
      $decodedValue = json_decode($value, JSON_OBJECT_AS_ARRAY);
      if (!$this->integrityCheck($decodedValue, $itemtype, $itemId)) {
         return false;
      }

      $array = [
         $this->symbol  => $value['server'],
         'port'         => $value['port'],
         'tls'          => $value['tls'],
      ];

      return $array;
   }

   public function showValueInput($value = '', $itemType = '', $itemId = 0) {
      if ($value === '') {
         $value = json_decode($this->policyData->getField('recommended_value'), JSON_OBJECT_AS_ARRAY);
      }
      $out = __('M2M server', 'flyvemdm') . '&nbsp;' . Html::input('value[server]', ['value' => $value['server']]);
      $out .= '<br>' . __('Port', 'flyvemdm') . '&nbsp;' . Html::input('value[port]', ['value' => $value['port']]);
      $out .= '<br>' . __('Use TLS', 'flyvemdm') . '&nbsp;' . Dropdown::showYesNo('value[tls]', $value['tls'], -1, ['display' => false]);

      return $out;
   }
}