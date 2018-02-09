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
 *
 * @since 0.1.0.33
 */
class PluginFlyvemdmPolicyDropdown extends PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /**
    * @var array $valueList
    */
   protected $valueList;

   /**
    * PluginFlyvemdmPolicyDropdown constructor.
    * @param PluginFlyvemdmPolicy $policy
    */
   public function __construct(PluginFlyvemdmPolicy $policy) {
      parent::__construct($policy);
      $this->valueList = json_decode($policy->getField('type_data'), true);

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
      return array_key_exists($value, $this->valueList);
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
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

   /**
    * @return array
    */
   public function translateData() {
      $translated = [];
      foreach ($this->valueList as $key => $value) {
         $translated[$key] = __($value, 'flyvemdm');
      }

      return $translated;
   }

   /**
    * @param string $value
    * @param string $itemType
    * @param int $itemId
    * @return int|string
    */
   public function showValueInput($value = '', $itemType = '', $itemId = 0) {
      return Dropdown::showFromArray('value', $this->valueList, ['display' => false, 'value' => $value]);
   }

   /**
    * @param PluginFlyvemdmTask $task
    * @return mixed
    */
   public function showValue(PluginFlyvemdmTask $task) {
      $value = $task->getField('value');
      return $this->valueList[$value];
   }
}