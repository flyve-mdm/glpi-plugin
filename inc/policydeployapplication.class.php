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
 * @since 0.1.33
 */
class PluginStorkmdmPolicyDeployapplication extends PluginStorkmdmPolicyBase implements PluginStorkmdmPolicyInterface {

   /**
    * @param string $properties
    */
   public function __construct(PluginStorkmdmPolicy $policy) {
      parent::__construct($policy);
      $this->symbol = $policy->getField('symbol');
      $this->unicityRequired = ($policy->getField('unicity') != '0');
      $this->group = $policy->getField('group');
   }

   /**
    * {@inheritDoc}
    * @see PluginStorkmdmPolicyInterface::integrityCheck()
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      // Check the value exists
      if (!isset($value['remove_on_delete'])) {
         Session::addMessageAfterRedirect(__('The remove on delete flag is mandatory', 'storkmdm'));
         return false;
      }

      // Check the value is a boolean
      if ($value['remove_on_delete'] != '0' && $value['remove_on_delete'] != '1') {
         Session::addMessageAfterRedirect(__('The remove on delete flag must be 0 or 1', 'storkmdm'));
         return false;
      }

      // Check the itemtype is an application
      if ($itemtype != 'PluginStorkmdmPackage') {
         Session::addMessageAfterRedirect(__('You must choose an application to apply this policy', 'storkmdm'));
         return false;
      }

      //check the item exists
      $package = new PluginStorkmdmPackage();
      if (!$package->getFromDB($itemId)) {
         Session::addMessageAfterRedirect(__('The application does not exists', 'storkmdm'));
         return false;
      }

      return true;
   }

   /**
    * {@inheritDoc}
    * @see PluginStorkmdmPolicyInterface::jsonEncode()
    */
   public function getMqttMessage($value, $itemtype, $itemId) {
      $decodedValue = json_decode($value, JSON_OBJECT_AS_ARRAY);
      if (!$this->integrityCheck($decodedValue, $itemtype, $itemId)) {
         return false;
      }
      $package = new PluginStorkmdmPackage();
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
    * {@inheritDoc}
    * @see PluginStorkmdmPolicyBase::unicityCheck()
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginStorkmdmFleet $fleet) {
      // Check the policy is already applied
      $fleetId = $fleet->getID();
      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $rows = $fleet_policy->find("`plugin_storkmdm_fleets_id` = '$fleetId'
            AND `itemtype` = '$itemtype' AND `items_id` = '$itemId'", '', '1');
      if (count($rows) > 0) {
         return false;
      }
      return true;
   }

   /**
    * {@inheritDoc}
    * @see PluginStorkmdmPolicyBase::unapply()
    */
   public function unapply(PluginStorkmdmFleet $fleet, $value, $itemtype, $itemId) {
      $decodedValue = json_decode($value, JSON_OBJECT_AS_ARRAY);
      if ($this->integrityCheck($decodedValue, $itemtype, $itemId) === false) {
         return false;
      }
      if ($decodedValue['remove_on_delete'] !=  '0') {
         $policyData = new PluginStorkmdmPolicy();
         if (!$policyData->getFromDBBySymbol('removeApp')) {
            Toolbox::logInFile('php-errors', 'Plugin FlyveMDM: Application removal policy not found\n');
            return false;
         }
         $fleet_policy = new PluginStorkmdmFleet_Policy();
         $package = new PluginStorkmdmPackage();
         if ($package->getFromDB($itemId)) {
            if (!$fleet_policy->add([
                  'plugin_storkmdm_fleets_id'   => $fleet->getID(),
                  'plugin_storkmdm_policies_id' => $policyData->getID(),
                  'value'                       => $package->getField('name'),
                  '_silent'                     => true,
            ])) {
               return false;
            }
         }
      }

      return true;
   }
}
