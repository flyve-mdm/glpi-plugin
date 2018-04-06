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
 * @since 0.1.33
 */
class PluginFlyvemdmPolicyRemoveapplication extends PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /**
    * PluginFlyvemdmPolicyRemoveapplication constructor.
    * @param PluginFlyvemdmPolicy $policy
    */
   public function __construct(PluginFlyvemdmPolicy $policy) {
      parent::__construct($policy);
      $this->symbol = $policy->getField('symbol');
      $this->unicityRequired = ($policy->getField('unicity') != '0');
      $this->group = $policy->getField('group');
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @return boolean
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      if ($value == '') {
         Session::addMessageAfterRedirect(__('An application ID is required', 'flyvemdm'));
         return false;
      }
      // Check the itemtype is empty
      if ($itemtype != '' || $itemId != 0) {
         return false;
      }
      // The value is a string: an android Package ID
      return true;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    * @return boolean
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      $notifiableType = $notifiable->getType();
      $notifiableId = $notifiable->getID();
      $task = new PluginFlyvemdmTask();
      $rows = $task->find("`itemtype_applied` = '$notifiableType'
            AND `items_id_applied` = '$notifiableId'
            AND `plugin_flyvemdm_policies_id` = '" . $this->policyData->getID() . "'
            AND `value` = '$value'", "", "1");
      return (count($rows) == 0);
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @return array|boolean
    */
   public function getMqttMessage($value, $itemtype, $itemId) {
      if (!$this->integrityCheck($value, $itemtype, $itemId)) {
         return false;
      }

      $array = [
         $this->symbol  => $value,
      ];
      return $array;
   }

   public static function getEnumSpecificStatus() {
      return [
         'waiting' => __('Waiting', 'flyvemdm'),
      ];
   }
}