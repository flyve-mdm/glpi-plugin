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

class PluginFlyvemdmMqttlog extends CommonTestCase {

   /**
    * @param $method
    */
   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testShowMqttLogs':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    * @param $method
    */
   public function afterTestMethod($method) {
      switch ($method) {
         case 'testShowMqttLogs':
            parent::afterTestMethod($method);
            $this->terminateSession();
            break;
      }
   }

   /**
    * @return object
    */
   private function createInstance() {
      $this->newTestedInstance();
      return $this->testedInstance;
   }

   /**
    * @tags testClass
    */
   public function testClass() {
      $this->testedClass->hasConstant('MQTT_MAXIMUM_DURATION');
      $class = $this->testedClass->getClass();
      $this->given($class)->string($class::$rightname)->isEqualTo('flyvemdm:mqttlog');
   }

   /**
    * @tags testGetRights
    */
   public function testGetRights() {
      $instance = $this->newTestedInstance();
      $this->array($instance->getRights())->hasSize(1)->containsValues(['Read']);
   }

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $instance = $this->createInstance();
      $this->string($instance->getTypeName(1))->isEqualTo('MQTT subscriber')
         ->string($instance->getTypeName(3))->isEqualTo('MQTT subscribers');
   }

   /**
    * @tags testSaveIngoingMqttMessage
    */
   public function testSaveIngoingMqttMessage() {
      $instance = $this->createInstance();
      $message = $this->getUniqueString();
      $instance->saveIngoingMqttMessage('exec/topic/command/ingoing', $message);
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`direction`='I' AND `items_id`='0' AND `topic`='ingoing' AND `message`='$message'";
      } else {
         $condition = [
            'direction' => 'I',
            'items_id' => '0',
            'topic' =>'ingoing',
            'message' => $message,
         ];
      }
      $this->array($instance->find($condition))
         ->size->isEqualTo(1);
   }

   /**
    * @tags testSaveOutgoingMqttMessage
    */
   public function testSaveOutgoingMqttMessage() {
      $instance = $this->createInstance();
      $message = $this->getUniqueString();
      $instance->saveOutgoingMqttMessage('exec/topic/command/outgoing', $message);
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`direction`='O' AND `items_id`='0' AND `topic`='outgoing' AND `message`='$message'";
      } else {
         $condition = [
            'direction' => 'O',
            'items_id' => '0',
            'topic' =>'outgoing',
            'message' => $message,
         ];
      }
      $this->array($instance->find($condition))
         ->size->isEqualTo(1);
   }

   /**
    * @tags testShowMqttLogs
    */
   public function testShowMqttLogs() {
      ob_start();
      \PluginFlyvemdmMqttlog::showMqttLogs(new \PluginFlyvemdmFleet());
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)->contains('No item found');
   }
}