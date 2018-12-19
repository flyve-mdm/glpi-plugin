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
 * @copyright Copyright © 2018 Teclib
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace GlpiPlugin\Flyvemdm\Mqtt;

use PluginFlyvemdmAgent;
use PluginFlyvemdmCommon;
use PluginFlyvemdmFleet;
use PluginFlyvemdmGeolocation;
use PluginFlyvemdmMqttlog;
use PluginFlyvemdmPolicyFactory;
use PluginFlyvemdmTask;
use PluginFlyvemdmTaskstatus;
use sskaje\mqtt\Message\PUBLISH;
use sskaje\mqtt\MessageHandler;
use sskaje\mqtt\MQTT;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class MqttReceiveMessageHandler extends MessageHandler {

   private $log;

   public function __construct(PluginFlyvemdmMqttlog $mqttlog) {
      $this->log = $mqttlog;
   }

   public function __invoke(PUBLISH $publish_object) {
      $topic = $publish_object->getTopic();
      $message = $publish_object->getMessage();
      $this->log->saveIngoingMqttMessage($topic, $message);

      $mqttPath = explode('/', $topic, 4);
      if (!isset($mqttPath[3])) {
         return;
      }

      if ($mqttPath[3] == "Status/Ping") {
         $this->updateLastContact($topic, $message);
      } else if ($mqttPath[3] === "Status/Geolocation" && $message != "?") {
         $this->saveGeolocationPosition($topic, $message);
      } else if ($mqttPath[3] === "Status/Unenroll") {
         $this->deleteAgent($topic, $message);
      } else if ($mqttPath[3] === "Status/Inventory") {
         $this->updateInventory($topic, $message);
      } else if ($mqttPath[3] === "Status/Online") {
         $this->updateOnlineStatus($topic, $message);
      } else if (PluginFlyvemdmCommon::startsWith($mqttPath[3], "Status/Task")) {
         $this->updateTaskStatus($topic, $message);
      }
   }

   /**
    * This is a callback for the MQTT loop function when the client is subscribed to a topic.
    *
    * @param MQTT $mqtt
    * @param PUBLISH $publish_object
    */
   public function publish(MQTT $mqtt, PUBLISH $publish_object) {
      $this($publish_object); // call to __invoke();
   }

   /**
    * Updates the last contact date of the agent
    *
    * The data to update is a datetime
    *
    * @param string $topic
    * @param string $message
    */
   protected function updateLastContact($topic, $message) {
      if ($message !== '!') {
         return;
      }
      $agent = new PluginFlyvemdmAgent();
      if ($agent->getByTopic($topic)) {
         $date = new \DateTime("now");
         $agent->update([
            'id'           => $agent->getID(),
            'last_contact' => $date->format('Y-m-d H:i:s'),
         ]);
      }
   }

   /**
    * Saves geolocation position
    * @param string $topic
    * @param string $message
    */
   protected function saveGeolocationPosition($topic, $message) {
      $agent = new PluginFlyvemdmAgent();
      if (!$agent->getByTopic($topic)) {
         return;
      }
      $position = json_decode($message, true);
      $dateGeolocation = false;
      if (isset($position['datetime'])) {
         // The datetime sent by the device is expected to be on UTC timezone
         $dateGeolocation = \DateTime::createFromFormat('U', $position['datetime'],
            new \DateTimeZone("UTC"));
         // Shift the datetime to the timezone of the server
         $dateGeolocation->setTimezone(new \DateTimeZone(date_default_timezone_get()));
      }
      if (isset($position['latitude']) && isset($position['longitude'])) {
         if ($dateGeolocation !== false) {
            $geolocation = new PluginFlyvemdmGeolocation();
            $geolocation->add([
               'computers_id' => $agent->getField('computers_id'),
               'date'         => $dateGeolocation->format('Y-m-d H:i:s'),
               'latitude'     => $position['latitude'],
               'longitude'    => $position['longitude'],
            ]);
         }
      } else if (isset($position['gps']) && strtolower($position['gps']) == 'off') {
         // No GPS geolocation available at this time, log it anyway
         if ($dateGeolocation !== false) {
            $geolocation = new PluginFlyvemdmGeolocation();
            $geolocation->add([
               'computers_id' => $agent->getField('computers_id'),
               'date'         => $dateGeolocation->format('Y-m-d H:i:s'),
               'latitude'     => 'na',
               'longitude'    => 'na',
            ]);
         }
      }
      $this->updateLastContact($topic, '!');
   }

   /**
    * Deletes the agent
    * @param string $topic
    * @param string $message
    */
   protected function deleteAgent($topic, $message) {
      $agent = new PluginFlyvemdmAgent();
      $agent->getByTopic($topic);
      $agent->delete([
         'id' => $agent->getID(),
      ]);
   }

   /**
    * Updates the inventory
    * @param string $topic
    * @param string $message
    */
   protected function updateInventory($topic, $message) {
      $agent = new PluginFlyvemdmAgent();
      $agent->getByTopic($topic);
      if ($agent->getComputer()) {
         $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
         $inventoryXML = $message;
         $communication = new \PluginFusioninventoryCommunication();
         $communication->handleOCSCommunication('', $inventoryXML, 'glpi');
         if (count($_SESSION["MESSAGE_AFTER_REDIRECT"]) > 0) {
            foreach ($_SESSION["MESSAGE_AFTER_REDIRECT"][0] as $logMessage) {
               $logMessage = "Import message: $logMessage\n";
               \Toolbox::logInFile('plugin_flyvemdm_inventory', $logMessage);
            }
         }
         $this->updateLastContact($topic, '!');
      }
   }

   /**
    * Update the status of a task from a notification sent by a device
    *
    * @param string $topic
    * @param string $message
    */
   protected function updateOnlineStatus($topic, $message) {
      $agent = new PluginFlyvemdmAgent();
      if (!$agent->getByTopic($topic)) {
         return;
      }
      $feedback = json_decode($message, true);
      if (!isset($feedback['online'])) {
         return;
      }
      if ($feedback['online'] == false) {
         $status = '0';
      } else if ($feedback['online'] == true) {
         $status = '1';
      } else {
         // Invalid value
         return;
      }
      $agent->update([
         'id'        => $agent->getID(),
         'is_online' => $status,
      ]);

      $this->updateLastContact($topic, '!');
   }

   /**
    * Update the status of a task from a notification sent by a device
    *
    * @param string $topic
    * @param string $message
    */
   protected function updateTaskStatus($topic, $message) {
      $agent = new PluginFlyvemdmAgent();
      if (!$agent->getByTopic($topic)) {
         return;
      }

      $feedback = json_decode($message, true);
      if (!isset($feedback['status'])) {
         return;
      }

      // Find the task the device wants to update
      $taskId = (int)array_pop(explode('/', $topic));
      $task = new PluginFlyvemdmTask();
      if (!$task->getFromDB($taskId)) {
         return;
      }

      // Check the task matches the fleet of the agent or the agent itself
      if ($task->getField('itemtype_applied') === PluginFlyvemdmFleet::class) {
         if ($agent->getField('plugin_flyvemdm_fleets_id') != $task->getField('items_id_applied')) {
            return;
         }
      } else if ($task->getField('itemtype_applied') === PluginFlyvemdmAgent::class) {
         if ($agent->getID() != $task->getField('items_id_applied')) {
            return;
         }
      }

      // Get the current status of the task for the agent
      $taskStatus = new PluginFlyvemdmTaskStatus();
      $request = [
         'AND' => [
            PluginFlyvemdmAgent::getForeignKeyField() => $agent->getID(),
            PluginFlyvemdmTask::getForeignKeyField()  => $taskId,
         ],
      ];
      if (!$taskStatus->getFromDBByCrit($request)) {
         return;
      }

      // Update the task
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($task->getField('plugin_flyvemdm_policies_id'));
      $taskStatus->updateStatus($policy, $feedback['status']);

      $this->updateLastContact($topic, '!');
   }
}
