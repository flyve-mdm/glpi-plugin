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
 *
 * @since 0.1.0.33
 */
class PluginFlyvemdmPolicyDropdown extends PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /**
    * @var array $valueList
    */
   protected $valueList;

   /**
    * Constructor
    * @param string $properties
    */
   public function __construct(PluginFlyvemdmPolicy $policy) {
      parent::__construct($policy);
      $this->valueList = json_decode($policy->getField('type_data'), true);

      $this->symbol = $policy->getField('symbol');
      $this->unicityRequired = ($policy->getField('unicity') != '0');
      $this->group = $policy->getField('group');
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::integrityCheck()
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      return array_key_exists($value, $this->valueList);
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::jsonEncode()
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
    * @see PluginFlyvemdmPolicyBase::translateData()
    */
   public function translateData() {
      $translated = array();
      foreach ($this->valueList as $key => $value) {
         $translated[$key] = __($value, 'flyvemdm');
      }

      return $translated;
   }

   /**
    * Shows the input value
    * @return array a list of the values
    */
   public function showValueInput() {
      return Dropdown::showFromArray('value', $this->valueList, array('display' => false));
   }

   /**
    * Shows the value
    * @param PluginFlyvemdmTask $task
    * @return array a list of the values
    */
   public function showValue(PluginFlyvemdmTask $task) {
      $value = $task->getField('value');
      return $this->valueList[$value];
   }
}