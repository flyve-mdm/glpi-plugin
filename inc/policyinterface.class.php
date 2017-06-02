<?php
/*
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
 * @since 0.1.0.33
 */
interface PluginFlyvemdmPolicyInterface {

   public function __construct(PluginFlyvemdmPolicy $policy);

   /**
    * Check the policy may apply with respect of unicity constraint
    * @param PluginFlyvemdmFleet $fleet
    * @param string $value
    * @param string $itemtype the itemtype of an item
    * @param integer $itemId the id of an item
    */
   public function canApply(PluginFlyvemdmFleet $fleet, $value, $itemtype, $itemId);

   /**
    * Check the unicity of the policy
    * @param unknown $value
    * @param unknown $itemtype
    * @param unknown $itemId
    * @param PluginFlyvemdmFleet $fleet
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet);

   /**
    * Check the value used to apply a policy is valid, and check the the item to link
    * @param string $value
    * @param string $itemtype the itemtype of an item
    * @param integer $itemId the id of an item
    */
   public function integrityCheck($value, $itemtype, $itemId);

   /**
    * Check there is not a conflict with an already applied policy
    * @param unknown $value
    * @param unknown $itemtype
    * @param unknown $itemId
    * @param PluginFlyvemdmFleet $fleet
    */
   public function conflictCheck($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet);

   /**
    * Returns an array describing the policy applied vith the given value and item
    * @param string $value
    * @param string $itemtype the itemtype of an item
    * @param integer $itemId the id of an item
    * @return array
    */
   public function getMqttMessage($value, $itemtype, $itemId);

   /**
    * Translate type_data field
    */
   public function translateData();

   /**
    * get the group the policy belongs to
    * @return string
    */
   public function getGroup();

   /**
    * Actions done before a policy is applied to a fleet
    * @param PluginFlyvemdmFleet $fleet
    * @param string $value
    */
   public function apply(PluginFlyvemdmFleet $fleet, $value, $itemtype, $itemId);

   /**
    * Actions done after a policy is unapplied to a fleet
    * @param PluginFlyvemdmFleet $fleet
    * @param string $value
    */
   public function unapply(PluginFlyvemdmFleet $fleet, $value, $itemtype, $itemId);

   /**
    * return HTML input to set policy value
    */
   public function showValueInput();


   /**
    * return policy value for display
    */
   public function showValue(PluginFlyvemdmTask $task);

   /**
    * Transforms form data to match the format expected by the API
    *
    * When using GLPI the form data send in a different structure comapred to the API
    * This method converts it back to the format used in the API
    *
    * Does nothing by default, override if needed
    *
    * @param array $input
    *
    * @return array
    */
   public function preprocessFormData($input);

}
