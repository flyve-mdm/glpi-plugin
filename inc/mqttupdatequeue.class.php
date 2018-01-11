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
class PluginFlyvemdmMqttupdatequeue extends CommonDBTM {

   protected static $delay = 'PT30S';

   /**
    * Prepares data before adding the item
    * @param array $input
    * @return array the modified datas
    */
   public function prepareInputForAdd($input) {
      $input['date'] = (new DateTime("now"))->format('Y-m-d H:i:s');

      return $input;
   }

   /**
    * Sets the delay
    * @param mixed $delay
    */
   public static function setDelay($delay) {
      self::$delay = $delay;
   }

   /**
    * Returns the name of the type
    * @param integer $count
    * @return string
    */
   static function getTypeName($count = 0) {
      return _n('Queued MQTT message', 'Queued MQTT messages', $count);
   }

   /**
    * get Cron description parameter for this class
    *
    * @param $name string name of the task
    *
    * @return array of string
    **/
   static function cronInfo($name) {

      switch ($name) {
         case 'UpdateTopics' :
            return ['description' => __('Sends queued MQTT messages')];
      }
   }

   /**
    * Update MQTT topics in the update queue
    *
    * @param $cronTask
    * @return int >0 : done
    *    >0 : done
    *    <0 : to be run again (not finished)
    *     0 : nothing to do
    */
   static function cronUpdateTopics($cronTask) {
      global $DB;

      $cronStatus = 0;

      $cronTask->log("Refresh MQTT topics queued for update");

      // Select the queued items until 30 seconds ago
      // To avoid time sync problems between the plugin and the DBMS
      // the date computation is handled here rather than in SQL
      $lastDate = new DateTime("now");
      $lastDate->sub(new DateInterval(self::$delay));
      $mostRecent = $lastDate->format('Y-m-d H:i:s');

      // Select a limited quantity of queued items
      $query = "SELECT `id`
            FROM `" . self::getTable() . "`
            WHERE `status` = 'queued' AND `date` <= '$mostRecent'
            ORDER BY `date` ASC
            LIMIT 100";
      $result = $DB->query($query);

      // Aggregate the selected rows
      if ($result !== false) {
         $idList = [];
         while ($row = $DB->fetch_assoc($result)) {
            $idList[] = $row['id'];
         }

         $idList = "'" . implode("', '", $idList) . "'";
         $query = "SELECT *
            FROM `" . self::getTable() . "`
            WHERE `id` IN ($idList)
            GROUP BY `plugin_flyvemdm_fleets_id`, `group`
            ORDER BY `date` ASC";
         $result = $DB->query($query);

         $task = new PluginFlyvemdmTask();
         while ($row = $DB->fetch_assoc($result)) {
            // publish MQTT messages
            $fleetId = $row['plugin_flyvemdm_fleets_id'];
            $group = $row['group'];
            $fleet = new PluginFlyvemdmFleet();
            $fleet->getFromDB($fleetId);
            $task->publishPolicies($fleet, [$group]);

            // mark done more recent policies on the same group and fleet
            $updateQueue = new static();
            $rows = $updateQueue->find("`group` = '$group' AND `plugin_flyvemdm_fleets_id` = '$fleetId' AND `status` = 'queued'");
            foreach ($rows as $updateQueueId => $updateQueueItem) {
               $updateQueue->update([
                     'id'     => $updateQueueId,
                     'status' => 'done',
               ]);
            }
            $cronTask->addVolume(count($rows));
         }
         $cronStatus = 1;
      } else {
         $cronTask->setVolume(0);
      }
      return $cronStatus;
   }

}