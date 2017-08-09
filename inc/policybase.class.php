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
 * @since 0.1.33
 */
abstract class PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /**
    * @var bool $unicityRequired if true The posicy cannot be applied more than once to a fleet
    */
   protected $unicityRequired = true;

   /**
    * @var string $symbol symbol of the policy
    */
   protected $symbol;

   /**
    * @var string $group name of group thie policy belongs to
    */
   protected $group;

   /**
    * @var PluginFlyvemdmPolicy instance of the policy in the DB
    */
   protected $policyData;

   public function __construct(PluginFlyvemdmPolicy $policy) {
      $this->policyData = $policy;
   }

   /**
    * JSON decode properties for the policy and merges them with default values
    * @param string $properties
    * @param array $defaultProperties
    * @return array
    */
   protected function jsonDecodeProperties($properties, array $defaultProperties) {
      if (empty($properties)) {
         return $defaultProperties;
      } else {
         $propertyCollection = json_decode($properties, true);
      }
      if (empty($propertyCollection)) {
         return $defaultProperties;
      }
      $intersect  = array_intersect_key($propertyCollection, $defaultProperties);
      $difference = array_diff_key($defaultProperties, $propertyCollection);
      return $difference + $intersect;
   }

   /**
    *
    * @see PluginFlyvemdmPolicyInterface::canApply()
    */
   public function canApply(PluginFlyvemdmFleet $fleet, $value, $itemtype, $itemId) {
      return true;
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::unicityCheck()
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet) {
      if ($this->unicityRequired) {
         $policyId            = $this->policyData->getID();
         $fleetId             = $fleet->getID();
         $fleet_policy = new PluginFlyvemdmFleet_Policy();
         $relationCollection  = $fleet_policy->find("`plugin_flyvemdm_fleets_id`='$fleetId' AND `plugin_flyvemdm_policies_id`='$policyId'", '', '1');
         if (count($relationCollection) > 0) {
            // A relation already exists for this policy and this fleet
            return false;
         }
      }
      return true;
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::conflictCheck()
    */
   public function conflictCheck($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet) {
      return true;
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::integrityCheck()
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      return true;
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::translateData()
    */
   public function translateData() {
      return '';
   }

   public function getGroup() {
      return $this->group;
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::apply()
    */
   public function apply(PluginFlyvemdmFleet $fleet, $value, $itemtype, $itemId) {
      return true;
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::unapply()
    */
   public function unapply(PluginFlyvemdmFleet $fleet, $value, $itemtype, $itemId) {
      // Do nothing by default
      // May be overriden by inhrited classes
      return true;
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::showValueInput()
    */
   public function showValueInput() {
      $html = '<input name="value" value="" >';

      return $html;
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::showValue()
    */
   public function showValue(PluginFlyvemdmFleet_Policy $fleet_policy) {
      return $fleet_policy->getField('value');
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::preprocessFormData()
    */
   public function preprocessFormData($input) {
      return $input;
   }
}
