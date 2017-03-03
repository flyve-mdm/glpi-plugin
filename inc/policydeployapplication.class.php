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
 @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.33
 */
class PluginFlyvemdmPolicyDeployapplication extends PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /**
    * @param string $properties
    */
   public function __construct(PluginFlyvemdmPolicy $policy) {
      parent::__construct($policy);
      $this->symbol = $policy->getField('symbol');
      $this->unicityRequired = ($policy->getField('unicity') != '0');
      $this->group = $policy->getField('group');
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::integrityCheck()
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      // Check the value exists
      if (!isset($value['remove_on_delete'])) {
         Session::addMessageAfterRedirect(__('The remove on delete flag is mandatory', 'flyvemdm'));
         return false;
      }

      // Check the value is a boolean
      if ($value['remove_on_delete'] != '0' && $value['remove_on_delete'] != '1') {
         Session::addMessageAfterRedirect(__('The remove on delete flag must be 0 or 1', 'flyvemdm'));
         return false;
      }

      // Check the itemtype is an application
      if ($itemtype != 'PluginFlyvemdmPackage') {
         Session::addMessageAfterRedirect(__('You must choose an application to apply this policy', 'flyvemdm'));
         return false;
      }

      //check the item exists
      $package = new PluginFlyvemdmPackage();
      if (!$package->getFromDB($itemId)) {
         Session::addMessageAfterRedirect(__('The application does not exists', 'flyvemdm'));
         return false;
      }

      return true;
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::jsonEncode()
    */
   public function getMqttMessage($value, $itemtype, $itemId) {
      $decodedValue = json_decode($value, JSON_OBJECT_AS_ARRAY);
      if (!$this->integrityCheck($decodedValue, $itemtype, $itemId)) {
         return false;
      }
      $package = new PluginFlyvemdmPackage();
      $package->getFromDB($itemId);
      $array = [
            $this->symbol  => $package->getField('name'),
            'id'           => $package->getID(),
            'versionCode'  => $package->getField('version_code'),
      ];

      return $array;
   }

   /**
    *
    * @see PluginFlyvemdmPolicyBase::unicityCheck()
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet) {
      // Check the policy is already applied
      $fleetId = $fleet->getID();
      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $rows = $fleet_policy->find("`plugin_flyvemdm_fleets_id` = '$fleetId'
            AND `itemtype` = '$itemtype' AND `items_id` = '$itemId'", '', '1');
      if (count($rows) > 0) {
         return false;
      }
      return true;
   }

   /**
    * @see PluginFlyvemdmPolicyInterface::conflictCheck()
    */
   public function conflictCheck($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet) {
      // Check there is not already a removal policy (to avoind opposite policy)
      $package = new PluginFlyvemdmPackage();
      if (!$package->getFromDB($itemId)) {
         // Cannot apply a non existing applciation
         Session::addMessageAfterRedirect(__('The application does not exists', 'flyvemdm'), false, ERROR);
         return false;
      }
      $packageName = $package->getField('name');

      $policyData = new PluginFlyvemdmPolicy();
      if (!$policyData->getFromDBBySymbol('removeApp')) {
         Toolbox::logInFile('php-errors', 'Plugin FlyveMDM: Application removal policy not found\n');
         // Give up this check
      } else {
         $policyId = $policyData->getID();
         $fleetId = $fleet->getID();
         $count = countElementsInTable(PluginFlyvemdmFleet_Policy::getTable(), "`plugin_flyvemdm_fleets_id` = '$fleetId'
               AND `plugin_flyvemdm_policies_id` = '$policyId' AND `value` = '$packageName'");
         if ($count > 0) {
            Session::addMessageAfterRedirect(__('A removal policy for this application is applied. Please, remove it first.', 'flyvemdm'), false, ERROR);
            return false;
         }
      }

      return true;
   }

    /**
    * @see PluginFlyvemdmPolicyBase::unapply()
    */
   public function unapply(PluginFlyvemdmFleet $fleet, $value, $itemtype, $itemId) {
      $decodedValue = json_decode($value, JSON_OBJECT_AS_ARRAY);
      if ($this->integrityCheck($decodedValue, $itemtype, $itemId) === false) {
         return false;
      }
      if ($decodedValue['remove_on_delete'] !=  '0') {
         $policyData = new PluginFlyvemdmPolicy();
         if (!$policyData->getFromDBBySymbol('removeApp')) {
            Toolbox::logInFile('php-errors', 'Plugin FlyveMDM: Application removal policy not found\n');
            return false;
         }
         $fleet_policy = new PluginFlyvemdmFleet_Policy();
         $package = new PluginFlyvemdmPackage();
         if ($package->getFromDB($itemId)) {
            if (!$fleet_policy->add([
                  'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
                  'plugin_flyvemdm_policies_id' => $policyData->getID(),
                  'value'                       => $package->getField('name'),
                  '_silent'                     => true,
            ])) {
               return false;
            }
         }
      }

      return true;
   }

   public function showValueInput() {
      $out = PluginFlyvemdmPackage::dropdown(array(
            'display'      => false,
            'displaywith'  => ['alias'],
            'name'         => 'items_id',
      ));
      $out .= '<input type="hidden" name="itemtype" value="PluginFlyvemdmPackage" />';
      $out .= '<input type="hidden" name="value[remove_on_delete]" value="1" />';

      return $out;
   }

   public function showValue(PluginFlyvemdmFleet_Policy $fleet_policy) {
      $package = new PluginFlyvemdmPackage();
      if ($package->getFromDB($fleet_policy->getField('items_id'))) {
         $alias = $package->getField('alias');
         $name  = $package->getField('name');
         return "$alias ($name)";
      }
      return NOT_AVAILABLE;
   }
}
