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
 * @author    Domingo Oropeza
 * @copyright Copyright © 2018 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Tests\CommonTestCase;

class PluginFlyvemdmMqttlog extends CommonTestCase {

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
      $instance->saveIngoingMqttMessage('topicIngoing', 'Incoming message');
      $this->array($instance->find("`direction`='I' AND `topic`='topicIngoing'"))
         ->size->isGreaterThanOrEqualTo(1);
   }

   /**
    * @tags testSaveOutgoingMqttMessage
    */
   public function testSaveOutgoingMqttMessage() {
      $instance = $this->createInstance();
      $instance->saveOutgoingMqttMessage('topicOutgoing', 'Outgoing message');
      $this->array($instance->find("`direction`='O' AND `topic`='topicOutgoing'"))
         ->size->isGreaterThanOrEqualTo(1);
   }
}