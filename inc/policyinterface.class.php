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
 * @since 0.1.0.33
 */
interface PluginFlyvemdmPolicyInterface {

   /**
    * PluginFlyvemdmPolicyInterface constructor.
    * @param PluginFlyvemdmPolicy $policy
    */
   public function __construct(PluginFlyvemdmPolicy $policy);

   /**
    * Check the policy may apply with respect of unicity constraint
    * @param mixed                             $value
    * @param mixed                             $itemtype
    * @param integer                           $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    */
   public function canApply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable);

   /**
    * Check the unicity of the policy
    * @param mixed                             $value
    * @param mixed                             $itemtype
    * @param integer                           $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable);

   /**
    * Check the value used to apply a policy is valid, and check the the item to link
    * @param mixed $value
    * @param mixed $itemtype the itemtype of an item
    * @param integer $itemId the id of an item
    * @return boolean False if integrity not satisfyed
    */
   public function integrityCheck($value, $itemtype, $itemId);

   /**
    * Check there is not a conflict with an already applied policy
    *
    * @param mixed                             $value
    * @param mixed                             $itemtype
    * @param integer                           $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    *
    * @return boolean False if there is a conflict with an already applied
    */
   public function conflictCheck($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable);

   /**
    * Returns an array describing the policy applied vith the given value and item
    * @param mixed $value
    * @param mixed $itemtype the itemtype of an item
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
    * Actions done before a policy is applied to a notifiable
    *
    * @param mixed                             $value
    * @param mixed                             $itemtype
    * @param integer                           $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    */
   public function pre_apply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable);

   /**
    * Actions done before a policy is unapplied to a notifiable
    *
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    */
   public function pre_unapply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable);

   /**
    * Actions done after a policy is unapplied to a notifiable
    *
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    */
   public function post_unapply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable);

    /**
    * return HTML input to set policy value
    * @param string $value value of the task
    * @param string $itemType type of the item linked to the task
    * @param integer $itemId ID of the item
    * @return string
    */
   public function showValueInput($value = '', $itemType = '', $itemId = 0);


   /**
    * return policy value for display
    * @param PluginFlyvemdmTask $task
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
