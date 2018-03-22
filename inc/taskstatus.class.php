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

class PluginFlyvemdmTaskstatus extends CommonDBTM {

   // name of the right in DB
   public static $rightname = 'flyvemdm:taskstatus';

   /**
    * Localized name of the type
    * @param $nb  integer  number of item in the type (default 0)
    * @return string
    */
   public static function getTypeName($nb = 0) {
      return __s('Task status', 'flyvemdm');
   }

   public function prepareInputForAdd($input) {
      if (!isset($input['status'])) {
         return false;
      }

      $task = new PluginFlyvemdmTask();
      if (!isset($input[$task::getForeignKeyField()])) {
         return false;
      }
      if (!$task->getFromDB($input[$task::getForeignKeyField()])) {
         return false;
      }

      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($task->getField(PluginFlyvemdmPolicy::getForeignKeyField()));

      $input['status'] = $policy->filterStatus($input['status']);
      if ($input['status'] === null) {
         return false;
      }

      return $input;
   }

   public function prepareInputForUpdate($input) {
      if (!isset($input['status'])) {
         return false;
      }

      unset($input[PluginFlyvemdmPolicy::getForeignKeyField()]);
      unset($input[PluginFlyvemdmTask::getForeignKeyField()]);

      $task = new PluginFlyvemdmTask();
      if (!$task->getFromDB($this->fields[$task::getForeignKeyField()])) {
         return false;
      }

      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($task->getField(PluginFlyvemdmPolicy::getForeignKeyField()));

      $input['status'] = $policy->filterStatus($input['status']);
      if ($input['status'] === null) {
         return false;
      }

      return $input;
   }

   /**
    * Update status of a task
    *
    * @param PluginFlyvemdmPolicyBase $policy
    * @param string $status
    */
   public function updateStatus(PluginFlyvemdmPolicyBase $policy, $status) {
      $status = $policy->filterStatus($status);

      if ($status === null) {
         return;
      }

      $this->update([
         'id'     => $this->getID(),
         'status' => $status,
      ]);
   }
}