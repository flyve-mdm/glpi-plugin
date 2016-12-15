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
 * @since 0.1.0.33
 */
interface PluginStorkmdmPolicyInterface {

   public function __construct(PluginStorkmdmPolicy $policy);

   /**
    * Check the policy may apply with respect of unicity constraint
    * @param PluginStorkmdmFleet $fleet
    * @param string $value
    * @param string $itemtype the itemtype of an item
    * @param integer $itemId the id of an item
    */
   public function canApply(PluginStorkmdmFleet $fleet, $value, $itemtype, $itemId);

   /**
    * check the value used to apply a policy is valid, and check the the item to link
    * @param string $value
    * @param string $itemtype the itemtype of an item
    * @param integer $itemId the id of an item
    */
   public function integrityCheck($value, $itemtype, $itemId);

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
    * @param PluginStorkmdmFleet $fleet
    * @param string $value
    */
   public function apply(PluginStorkmdmFleet $fleet, $value, $itemtype, $itemId);

   /**
    * Actions done after a policy is unapplied to a fleet
    * @param PluginStorkmdmFleet $fleet
    * @param string $value
    */
   public function unapply(PluginStorkmdmFleet $fleet, $value, $itemtype, $itemId);
}