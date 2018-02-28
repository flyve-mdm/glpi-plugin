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
 * @since 0.1.0
 */
class PluginFlyvemdmMqttHandler extends \sskaje\mqtt\MessageHandler {

   /**
    * @var PluginFlyvemdmMqttlog $log mqtt messages logger
    */
   protected $log;

   /**
    * @var integer $starttime Time when the subscribtion started
    */
   protected $startTime;

   protected $flyveManifestMissing = true;
   protected $publishedVersion = null;
   protected static $instance = null;

   /**
    * PluginFlyvemdmMqttHandler constructor.
    */
   protected function __construct() {
      $this->log = new \PluginFlyvemdmMqttlog();
      $this->startTime = time();
   }

   /**
    * Gets the instance of the PluginFlyvemdmMqttHandler
    * @return the instance of this class
    */
   public static function getInstance() {
      if (self::$instance === null) {
         self::$instance = new static();
      }
      return self::$instance;
   }

   /**
    * Maintains a MQTT topic to publish the current version of the backend
    *
    * @param \sskaje\mqtt\MQTT $mqtt
    */
   protected function publishManifest() {
      // Don't use version from the constant in setup.php because the backend may upgrade while this script is running
      // thus keep in RAM in an older version
      $config = Config::getConfigurationValues('flyvemdm', ['version']);
      $version = $config['version'];

      if ($this->flyveManifestMissing) {
         if (preg_match(\PluginFlyvemdmCommon::SEMVER_VERSION_REGEX, $version) == 1) {
            $mqtt = \PluginFlyvemdmMqttclient::getInstance();
            $mqtt->publish("/FlyvemdmManifest/Status/Version", json_encode(['version' => $version]), 0, 1);
            $this->flyveManifestMissing = false;
         }
      }
   }

   /**
    * Handle MQTT Ping response
    * @param sskaje\mqtt\MQTT $mqtt
    * @param sskaje\mqtt\Message\PINGRESP $pingresp_object
    */
   public function pingresp(\sskaje\mqtt\MQTT $mqtt, \sskaje\mqtt\Message\PINGRESP $pingresp_object) {
      global $DB;

      if (time() - $this->startTime > PluginFlyvemdmMqttclient::MQTT_MAXIMUM_DURATION) {
         $mqtt->unsubscribe(['#']);
      } else {
         // Reconnect to DB to avoid timeouts
         $DB->connect();

         $this->publishManifest($mqtt);
      }
   }

   /**
    * Handle MQTT publish messages
    * @see \sskaje\mqtt\MessageHandler::publish()
    */
   public function publish(\sskaje\mqtt\MQTT $mqtt, \sskaje\mqtt\Message\PUBLISH $publish_object) {
      $topic = $publish_object->getTopic();
      $message = $publish_object->getMessage();
      $this->log->saveIngoingMqttMessage($topic, $message);

      $mqttPath = explode('/', $topic, 4);
      if (isset($mqttPath[3])) {
         if ($mqttPath[3] == "Status/Ping") {
            $this->updateLastContact($topic, $message);
         } else if ($mqttPath[3] == "Status/Geolocation"  && $message != "?") {
            $this->saveGeolocationPosition($topic, $message);
         } else if ($mqttPath[3] == "Status/Unenroll") {
            $this->deleteAgent($topic, $message);
         } else if ($mqttPath[3] == "Status/Inventory") {
            $this->updateInventory($topic, $message);
         } else if ($mqttPath[3] == "Status/Online") {
            $this->updateOnlineStatus($topic, $message);
         } else if (PluginFlyvemdmCommon::startsWith($mqttPath[3], "Status/Task")) {
            $this->updateTaskStatus($topic, $message);
         } else if ($mqttPath[3] == "FlyvemdmManifest/Status/Version") {
            $this->updateAgentVersion($topic, $message);
         } else if (strpos($topic, "/FlyvemdmManifest") === 0) {
            if ($topic == '/FlyvemdmManifest/Status/Version') {
               $this->publishFlyveManifest($message);
            }
         }
      }
   }

   /**
    * Update the version of an agent
    * @param string $topic
    * @param string $message
    */
   protected function updateAgentVersion($topic, $message) {
      $agent = new \PluginFlyvemdmAgent();
      if ($agent->getByTopic($topic) !== false) {
         $sanitized = null;
         preg_match("#^[\d.]+$#", $message, $sanitized);
         if (!empty($sanitized[0])) {
            $agent->update([
                  'id'        => $agent->getID(),
                  'version'   => $sanitized[0],
            ]);
         }
      }
   }

   /**
    * Publishes the current version of Flyve
    * @return mixed $mqtt the current version
    */
   protected function publishFlyveManifest($message) {
      // Don't use version from the constant in setup.php because the backend may upgrade while this script is running
      // thus keep in RAM in an older version
      $config = Config::getConfigurationValues('flyvemdm', ['version']);
      $version = $config['version'];

      // TODO: check for $message & $mqtt values, both are undefined.
      $matches = null;
      preg_match('/^([\d\.]+)/', $version, $matches);
      if (!isset($matches[1]) || ($matches[1] != $message)) {
         $this->flyveManifestMissing = true;
         $this->publishManifest(PluginFlyvemdmMqttclient::getInstance());
      }
   }

   /**
    * Updates the inventory
    * @param string $topic
    * @param string $message
    */
   protected function updateInventory($topic, $message) {
      global $DB;

      $mqttPath = explode('/', $topic);
      $entityId = $mqttPath[1];
      $serial = $DB->escape($mqttPath[3]);
      $computer = new Computer();
      $request = [
         'AND' => [
            Entity::getForeignKeyField() => $entityId,
            'serial' => $serial,
         ]
      ];
      if ($computer->getFromDBByCrit($request)) {
         $_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
         $inventoryXML = $message;
         $communication = new \PluginFusioninventoryCommunication();
         $communication->handleOCSCommunication('', $inventoryXML, 'glpi');
         if (count($_SESSION["MESSAGE_AFTER_REDIRECT"]) > 0) {
            foreach ($_SESSION["MESSAGE_AFTER_REDIRECT"][0] as $logMessage) {
               $logMessage = "Serial $serial : $logMessage\n";
               \Toolbox::logInFile('plugin_flyvemdm_inventory', $logMessage);
            }
         }

         $this->updateLastContact($topic, '!');
      }
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
      $agent = new \PluginFlyvemdmAgent();
      if ($agent->getByTopic($topic)) {

         $date = new \DateTime("now");
         $agent->update([
               'id'              => $agent->getID(),
               'last_contact'    => $date->format('Y-m-d H:i:s')
         ]);
      }
   }

   /**
    * Deletes the agent
    * @param string $topic
    * @param string $message
    */
   protected function deleteAgent($topic, $message) {
      $agent = new \PluginFlyvemdmAgent();
      $agent->getByTopic($topic);
      $agent->delete([
            'id'  => $agent->getID(),
      ]);
   }

   /**
    * Saves geolocation position
    * @param string $topic
    * @param string $message
    */
   protected function saveGeolocationPosition($topic, $message) {
      $agent = new \PluginFlyvemdmAgent();
      if ($agent->getByTopic($topic)) {
         $position = json_decode($message, true);
         if (isset($position['datetime'])) {
            // The datetime sent by the device is expected to be on UTC timezone
            $dateGeolocation = \DateTime::createFromFormat('U', $position['datetime'], new \DateTimeZone("UTC"));
            // Shift the datetime to the timezone of the server
            $dateGeolocation->setTimezone(date_default_timezone_get());
         } else {
            $dateGeolocation = false;
         }
         if (isset($position['latitude']) && isset($position['longitude'])) {
            if ($dateGeolocation !== false) {
               $geolocation = new \PluginFlyvemdmGeolocation();
               $geolocation->add([
                     'computers_id'       => $agent->getField('computers_id'),
                     'date'               => $dateGeolocation->format('Y-m-d H:i:s'),
                     'latitude'           => $position['latitude'],
                     'longitude'          => $position['longitude']
               ]);
            }
         } else if (isset($position['gps']) && strtolower($position['gps']) == 'off') {
            // No GPS geolocation available at this time, log it anyway
            if ($dateGeolocation !== false) {
               $geolocation = new PluginFlyvemdmGeolocation();
               $geolocation->add([
                     'computers_id'       => $agent->getField('computers_id'),
                     'date'               => $dateGeolocation->format('Y-m-d H:i:s'),
                     'latitude'           => 'na',
                     'longitude'          => 'na'
               ]);
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
   protected function updateTaskStatus($topic, $message) {
      $agent = new PluginFlyvemdmAgent();
      if ($agent->getByTopic($topic)) {
         $agentId = $agent->getID();
         $feedback = json_decode($message, true);
         if (!isset($feedback['status'])) {
            return;
         }

         // Find the task the device wants to update
         $taskId = (int) array_pop(explode('/', $topic));
         $task = new PluginFlyvemdmTask();
         if (!$task->getFromDB($taskId)) {
            return;
         }

         // Check the task belongs to the sender agent and the fleet of the agent
         if ($agent->getField('plugin_flyvemdm_fleets_id') != $task->getField('plugin_flyvemdm_fleets_id')) {
            return;
         }
         $taskStatus = new PluginFlyvemdmTaskstatus();
         $request = [
            'AND' => [
               PluginFlyvemdmAgent::getForeignKeyField() => $agentId,
               PluginFlyvemdmTask::getForeignKeyField() => $taskId
            ]
         ];
         $taskStatus->getFromDBByCrit($request);
         if ($taskStatus->isNewItem()) {
            return;
         }

         // Update the task
         $policyFactory = new PluginFlyvemdmPolicyFactory();
         $policy = $policyFactory->createFromDBByID($task->getField('plugin_flyvemdm_policies_id'));
         $taskStatus->updateStatus($policy, $status);

            $this->updateLastContact($topic, '!');
         }
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
      if ($agent->getByTopic($topic)) {
         $feedback = json_decode($message, true);
         if (!isset($feedback['online'])) {
            return;
         }
         if ($feedback['online'] == 'false') {
            $status = '0';
         } else if ($feedback['online'] == 'true') {
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
   }
}
