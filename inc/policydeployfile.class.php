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
class PluginStorkmdmPolicyDeployfile extends PluginStorkmdmPolicyBase implements PluginStorkmdmPolicyInterface {

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
      // Check values exist
      if (!isset($value['destination']) || !isset($value['remove_on_delete'])) {
         Session::addMessageAfterRedirect(__('A destination and the remove on delete flag are mandatory', 'storkmdm'));
         return false;
      }

      // Check remove_on_delete is boolean
      if ($value['remove_on_delete'] != '0' && $value['remove_on_delete'] != '1') {
         Session::addMessageAfterRedirect(__('The remove on delete flag must be 0 or 1', 'storkmdm'));
         return false;
      }

      // Check the itemtype is a file
      if ($itemtype != 'PluginStorkmdmFile') {
         Session::addMessageAfterRedirect(__('You must choose a file to apply this policy', 'storkmdm'));
         return false;
      }

      // Check the file exists
      $file = new PluginStorkmdmFile();
      if (!$file->getFromDB($itemId)) {
         Session::addMessageAfterRedirect(__('The file does not exists', 'storkmdm'));
         return false;
      }

      // Check relative directory expression
      if (!strpos($value['destination'], '/../') === false || !strpos($value['destination'], '/./') === false) {
         Session::addMessageAfterRedirect(__('invalid base path', 'storkmdm'));
         return false;
      }

      // Check double directory separator
      if (!strpos($value['destination'], '//') === false) {
         Session::addMessageAfterRedirect(__('invalid base path', 'storkmdm'));
         return false;
      }

      // Check base path against well known paths
      $wellKnownPath = new PluginStorkmdmWellknownpath();
      $rows = $wellKnownPath->find('1');
      $basePathIsValid = false;
      foreach ($rows as $row) {
         if (strpos($value['destination'], $row['name']) === 0 ) {
            // Path begins with a well known path
            if ($value['destination'] == $row['name']) {
                // ... and is the same
                $basePathIsValid = true;
                break;
            } else {
               // ... or is longer and the same followed by a /
               if (strlen($value['destination']) > strlen($row['name'])) {
                  if (substr($value['destination'], 0, strlen($row['name']) + 1) == $row['name'] . '/') {
                     $basePathIsValid = true;
                     break;
                  }
               }
            }
         }
      }
      if (!$basePathIsValid) {
         Session::addMessageAfterRedirect(__('invalid base path', 'storkmdm'));
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

      // Ensure there is a trailing slash
      if (strrpos($decodedValue['destination'], '/') != strlen($decodedValue['destination']) - 1) {
         $decodedValue['destination'] .= '/';
      }

      $file = new PluginStorkmdmFile();
      $file->getFromDB($itemId);
      $array = [
            $this->symbol  => $decodedValue['destination'],
            'id'           => $file->getID(),
            'version'      => $file->getField('version'),
      ];

      return $array;
   }

   /**
    * {@inheritDoc}
    * @see PluginStorkmdmPolicyBase::unicityCheck()
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginStorkmdmFleet $fleet) {
      $fleetId = $fleet->getID();
      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $rows = $fleet_policy->find("`plugin_storkmdm_fleets_id` = '$fleetId'
            AND `itemtype` = '$itemtype'");
      foreach ($rows as $row) {
         $decodedValue = json_decode($row['value'], true);
         if ($decodedValue['destination'] == $value['destination']) {
            return false;
         }
      }
      return true;
   }

   /**
    * {@inheritDoc}
    * @see PluginStorkmdmPolicyInterface::conflictCheck()
    */
   public function conflictCheck($value, $itemtype, $itemId, PluginStorkmdmFleet $fleet) {
      $policyData = new PluginStorkmdmPolicy();
      if (!$policyData->getFromDBBySymbol('removeFile')) {
         Toolbox::logInFile('php-errors', 'Plugin FlyveMDM: File removal policy not found\n');
         // Give up this check
      } else {
         $fleetId = $fleet->getID();
         $policyId = $policyData->getID();
         $fleet_policy = new PluginStorkmdmFleet_Policy();
         $rows = $fleet_policy->find("`plugin_storkmdm_fleets_id` = '$fleetId'
               AND `plugin_storkmdm_policies_id` = '$policyId'");
         foreach ($rows as $row) {
            if ($row['value'] == $value['destination']) {
               Session::addMessageAfterRedirect(__('A removal policy is applied for this file destination. Please, remove it first.', 'storkmdm'), false, ERROR);
               return false;
            }
         }
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
      $value = json_decode($value, JSON_OBJECT_AS_ARRAY);
      if ($value['remove_on_delete'] !=  '0') {
         $policyData = new PluginStorkmdmPolicy();
         if (!$policyData->getFromDBBySymbol('removeFile')) {
            Toolbox::logInFile('php-errors', 'Plugin FlyveMDM: File removal policy not found\n');
            return false;
         }
         $file = new $itemtype();
         if (!$file->getFromDB($itemId)) {
            return false;
         }

         $fleet_policy = new PluginStorkmdmFleet_Policy();
         if (!$fleet_policy->add([
               'plugin_storkmdm_fleets_id'   => $fleet->getID(),
               'plugin_storkmdm_policies_id' => $policyData->getID(),
               'value'                       => $decodedValue['destination'] . $file->getField('name'),
               '_silent'                     => true,
         ])) {
            return false;
         }
      }

      return true;
   }
}
