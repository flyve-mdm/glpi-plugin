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

namespace tests\units\GlpiPlugin\Flyvemdm\Broker;

use Flyvemdm\Tests\CommonTestCase;
use Flyvemdm\Tests\DummyMessage;
use GlpiPlugin\Flyvemdm\Broker\BrokerEnvelope as RealBrokerEnvelope;
use GlpiPlugin\Flyvemdm\Broker\BrokerReceivedMessage;
use GlpiPlugin\Flyvemdm\Mqtt\MqttEnvelope;

class BrokerEnvelope extends CommonTestCase {

   /**
    * @tags testWrap
    */
   public function testWrap() {
      $message = new DummyMessage('dummy');
      $class = $this->testedClass->getClass();
      $this->object($wrapMessage = $class::wrap($message))->isInstanceOf(RealBrokerEnvelope::class);
      $this->object($class::wrap($wrapMessage))->isInstanceOf(RealBrokerEnvelope::class);
   }

   /**
    * @tags testWith
    */
   public function testWith() {
      $message = new DummyMessage('dummy');
      $class = $this->testedClass->getClass();
      $envelope = $class::wrap($message);
      $envelopeWith = $envelope->with(new BrokerReceivedMessage());
      $this->object($envelope)->isNotIdenticalTo($envelopeWith);
   }

   /**
    * @tags testGet
    */
   public function testGet() {
      $class = $this->testedClass->getClass();
      $receivedMessage = new BrokerReceivedMessage();
      $envelope = $class::wrap(new DummyMessage('dummy'))->with($receivedMessage);
      $this->object($receivedMessage)->isIdenticalTo($envelope->get(BrokerReceivedMessage::class));
      $this->variable($envelope->get('InvalidMessage'))->isNull();
   }

   /**
    * @tags testAll
    */
   public function testAll() {
      $class = $this->testedClass->getClass();
      $receivedMessage = new BrokerReceivedMessage();
      $receivedClassName = BrokerReceivedMessage::class;
      $MqttEnvelope = new MqttEnvelope(['topic' => 'lorem']);
      $mqttEnvelopeClassName = MqttEnvelope::class;

      $envelope = $class::wrap(new DummyMessage('dummy'))
         ->with($receivedMessage)->with($MqttEnvelope);

      $config = $envelope->all();
      $this->array($config)->hasKeys([$receivedClassName, $mqttEnvelopeClassName]);
      $this->object($receivedMessage)->isIdenticalTo($config[$receivedClassName]);
      $this->object($MqttEnvelope)->isIdenticalTo($config[$mqttEnvelopeClassName]);
   }

   /**
    * @tags testWithMessage
    */
   public function testWithMessage() {
      $class = $this->testedClass->getClass();
      $message = 'lorem';
      $envelope = $class::wrap(new DummyMessage('dummy'))->withMessage($message);
      $this->string($envelope->getMessage())->isEqualTo($message);
   }

   protected function providerConstructor() {
      return [
         ['lorem', []],
         ['lorem', [new BrokerReceivedMessage()]],
      ];
   }
   /**
    * @dataProvider providerConstructor
    * @tags testConstructor
    */
   public function testConstructor($message, $items) {
      $instance = $this->newTestedInstance($message, $items);
      $this->string($instance->getMessage())->isEqualTo($message);
      if (count($items) > 0) {
         $this->array($instance->all())->isNotEmpty()->values
            ->object[0]->isIdenticalTo($items[0]);
      } else {
         $this->array($instance->all())->isEmpty();
      }
   }

}
