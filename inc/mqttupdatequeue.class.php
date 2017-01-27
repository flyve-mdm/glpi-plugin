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
class PluginFlyvemdmMqttupdatequeue extends CommonDBTM {

   protected static $delay = 'PT30S';

   public function prepareInputForAdd($input) {
      $input['date'] = (new DateTime("now", new DateTimeZone("UTC")))->format('Y-m-d H:i:s');

      return $input;
   }

   public static function setDelay($delay) {
      self::$delay = $delay;
   }

   /**
    * Update MQTT topics in the update queue
    *
    * @param $task Object of CronTask class for log / stat
    *
    * @return interger
    *    >0 : done
    *    <0 : to be run again (not finished)
    *     0 : nothing to do
    */
   static function cronUpdateTopics($task) {
      global $DB;

      $task->log("Refresh MQTT topics queued for update");

      // Select the queued items until 30 seconds ago
      // To avoid time sync problems between the plugin and the DBMS
      // the date computation is handled here rather than in SQL
      $lastDate = new DateTime("now", new DateTimeZone("UTC"));
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
         $numRows = $DB->numrows($result);
         $idList = "'" . implode("', '", $idList) . "'";
         $query = "SELECT *
            FROM `" . self::getTable() . "`
            WHERE `id` IN ($idList)
            GROUP BY `plugin_flyvemdm_fleets_id`, `group`
            ORDER BY `date` ASC";
         $result = $DB->query($query);

         $fleet_policy = new PluginFlyvemdmFleet_Policy();
         while ($row = $DB->fetch_assoc($result)) {
            $fleet = new PluginFlyvemdmFleet();
            $fleet->getFromDB($row['plugin_flyvemdm_fleets_id']);
            $fleet_policy->publishPolicies($fleet, array($row['group']));
         }

         // update the status of the rows

         $query = "UPDATE `" . self::getTable() . "`
               SET `status` = 'done'
               WHERE `id` IN ($idList)";
         $result = $DB->query($query);

         $task->setVolume($numRows);
      } else {
         $task->setVolume(0);
      }
      return 1;
   }

}