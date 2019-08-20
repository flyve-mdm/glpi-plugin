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

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmTask extends CommonTestCase {

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->login('glpi', 'glpi');
   }

   public function afterTestMethod($method) {
      parent::afterTestMethod($method);
      $this->terminateSession();
   }

   /**
    * @tags testApplyPolicyOnFleet
    */
   public function testApplyPolicyOnFleet() {
      // Create an agent
      $agent = $this->createAgent([
         'entities_id' => $_SESSION['glpiactive_entity'],
      ]);

      // Create a fleet
      $notifiableItem = $this->createFleet([
         'entities_id' => $_SESSION['glpiactive_entity'],
         'name'        => __FUNCTION__
      ]);

      // Move the agent to the fleet
      $this->boolean($agent->update([
         'id'                        => $agent->getID(),
         'plugin_flyvemdm_fleets_id' => $notifiableItem->getID(),
      ]))->isTrue();

      // Test apply policy
      list($task, $taskId, $taskStatus, $taskFk) = $this->checkNotifiableMqttMessage($notifiableItem);

      // Check task statuses are deleted
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`$taskFk` = '$taskId'";
      } else {
         $condition = [
            $taskFk => $taskId,
         ];
      }
      $rows = $taskStatus->find($condition);
      $this->integer(count($rows))->isEqualTo(0);

      // Test task status is created when an agent joins a fleet having policies
      // Create a 2nd fleet
      $fleet2 = $this->createFleet([
         'entities_id' => $_SESSION['glpiactive_entity'],
         'name'        => __FUNCTION__
      ]);

      // Apply a policy
      $policy = new \PluginFlyvemdmPolicy();
      $policy->getFromDBByCrit(['symbol' => 'disableWifi']);
      $this->boolean($policy->isNewItem())->isFalse("Could not find the test policy");
      //$fleetFk = \PluginFlyvemdmFleet::getForeignKeyField();
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();
      $task2 = $this->newTestedInstance();
      $taskId2 = $task2->add([
         'itemtype_applied'   => $fleet2->getType(),
         'items_id_applied'   => $fleet2->getID(),
         $policyFk => $policy->getID(),
         'value'   => '0',
      ]);
      $this->boolean($task2->isNewItem())
         ->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Join the 2nd fleet
      $this->boolean($agent->update([
         'id'                        => $agent->getID(),
         'plugin_flyvemdm_fleets_id' => $fleet2->getID(),
      ]))->isTrue();

      // Check a task status is created for the agent
      $taskStatus2 = new \PluginFlyvemdmTaskstatus();
      $taskFk = $task::getForeignKeyField();
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`$taskFk` = '$taskId2'";
      } else {
         $condition = [
            $taskFk => $taskId2,
         ];
      }
      $rows = $taskStatus2->find($condition);
      $this->integer(count($rows))->isEqualTo(1);
      foreach ($rows as $row) {
         $this->string($row['status'])->isEqualTo('pending');
      }

      // Create a 3rd fleet
      $fleet3 = $this->createFleet([
         'entities_id' => $_SESSION['glpiactive_entity'],
         'name'        => __CLASS__ . '::'. __FUNCTION__,
      ]);

      // Apply a policy
      $policy = new \PluginFlyvemdmPolicy();
      $policy->getFromDBByCrit(['symbol' => 'disableGps']);
      $this->boolean($policy->isNewItem())->isFalse("Could not find the test policy");
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();
      $taskId3 = $task->add([
         'itemtype_applied'   => $fleet3->getType(),
         'items_id_applied'   => $fleet3->getID(),
         $policyFk            => $policy->getID(),
         'value'              => '0',
      ]);

      // Join the 3rd fleet
      $this->boolean($agent->update([
         'id'                        => $agent->getID(),
         'plugin_flyvemdm_fleets_id' => $fleet3->getID(),
      ]))->isTrue();

      // Check a task status is created for the agent
      $taskStatus3 = new \PluginFlyvemdmTaskstatus();
      $taskFk = $task::getForeignKeyField();
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`$taskFk` = '$taskId3'";
      } else {
         $condition = [
            $taskFk => $taskId3,
         ];
      }
      $rows = $taskStatus3->find($condition);
      $this->integer(count($rows))->isEqualTo(1);
      foreach ($rows as $row) {
         $this->string($row['status'])->isEqualTo('pending');
      }

      // Check the old task status is canceled
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`$taskFk` = '$taskId2'";
      } else {
         $condition = [
            $taskFk => $taskId2,
         ];
      }
      $rows = $taskStatus->find($condition);
      $this->integer(count($rows))->isEqualTo(1);
      foreach ($rows as $row) {
         $this->string($row['status'])->isEqualTo('canceled');
      }
   }

   /**
    * @tags testApplyPolicyOnAgent
    */
   public function testApplyPolicyOnAgent() {
      // Create an agent
      $notifiableItem = $this->createAgent([
         'entities_id' => $_SESSION['glpiactive_entity'],
      ]);

      // Test apply policy
      list($task, $taskId, $taskStatus, $taskFk) = $this->checkNotifiableMqttMessage($notifiableItem);

      // Check task statuses are deleted
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`$taskFk` = '$taskId'";
      } else {
         $condition = [
            $taskFk => $taskId,
         ];
      }
      $rows = $taskStatus->find($condition);
      $this->array($rows)->size->isEqualTo(0);
   }

   /**
    * @param \PluginFlyvemdmNotifiableInterface $notifiableItem
    * @return array
    */
   private function checkNotifiableMqttMessage(\PluginFlyvemdmNotifiableInterface $notifiableItem) {
      $policy = new \PluginFlyvemdmPolicy();
      $policy->getFromDBByCrit(['symbol' => 'storageEncryption']);
      $this->boolean($policy->isNewItem())->isFalse("Could not find the test policy");
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();
      $task = $this->newTestedInstance();
      $taskId = $task->add([
         'itemtype_applied' => $notifiableItem->getType(),
         'items_id_applied' => $notifiableItem->getID(),
         $policyFk          => $policy->getID(),
         'value'            => '0',
      ]);
      $this->boolean($task->isNewItem())
         ->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check a task status is created for the agent
      $taskStatus = new \PluginFlyvemdmTaskstatus();
      $taskFk = $task::getForeignKeyField();
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`$taskFk` = '$taskId'";
      } else {
         $condition = [
            $taskFk => $taskId,
         ];
      }
      $rows = $taskStatus->find($condition);
      $this->integer(count($rows))->isEqualTo(1);
      foreach ($rows as $row) {
         $this->string($row['status'])->isEqualTo('pending');
      }

      // Check a MQTT message is sent
      sleep(2);

      // check the message
      $policyName = $policy->getField('symbol');
      $expectedTopic = "Policy/$policyName/Task/$taskId";
      $policyValue = $task->getField('value') == '0' ? 'false' : 'true';
      $receivedMqttMessage = ['storageEncryption' => $policyValue, 'taskId' => $taskId];
      $encodedMessage = json_encode($receivedMqttMessage, JSON_UNESCAPED_SLASHES);
      $log = new \PluginFlyvemdmMqttlog();
      $mqttLogId = $this->asserLastMqttlog($notifiableItem, $log, $expectedTopic, $encodedMessage);

      // Test apply a policy twice fails
      $task = $this->newTestedInstance();
      $task->add([
         'itemtype_applied' => $notifiableItem->getType(),
         'items_id_applied' => $notifiableItem->getID(),
         $policyFk          => $policy->getID(),
         'value'            => '0',
      ]);
      $this->boolean($task->isNewItem())->isTrue();

      // Test purge task
      $task->delete(['id' => $taskId], 1);

      // Check a mqtt message is sent to remove the applied policy from MQTT
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`id` > '$mqttLogId' AND `topic` = '$expectedTopic' AND `message`=''";
         $order = '`id` DESC';
      } else {
         $condition = [
            'id' => ['>', $mqttLogId],
            'topic' => $expectedTopic,
            'message' => '',
         ];
         $order = [
            'id DESC',
         ];
      }
      $rows = $log->find($condition, $order, '1');
      $this->array($rows)->size->isEqualTo(1);

      return [
         $task,
         $taskId,
         $taskStatus,
         $taskFk,
         $policyName,
         $log,
         $mqttLogId,
         $expectedTopic,
      ];
   }
}
