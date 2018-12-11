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
 * @author    Domingo Oropeza <doropeza@teclib.com>
 * @copyright Copyright © 2018 Teclib
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace GlpiPlugin\Flyvemdm\Mqtt;

use GlpiPlugin\Flyvemdm\Interfaces\BrokerTransportInterface;
use GlpiPlugin\Flyvemdm\Broker\BrokerEnvelope;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class MqttTransport implements BrokerTransportInterface {

   private $connection;
   private $receiver;
   private $sender;

   public function __construct(MqttConnection $connection) {
      $this->connection = $connection;
   }

   /**
    * Receive some messages to the given handler.
    *
    * The handler will have, as argument, the received PluginFlyvemdmBrokerEnvelope containing the message.
    * Note that this envelope can be `null` if the timeout to receive something has expired.
    *
    * @param callable $handler
    * @return void
    */
   public function receive(callable $handler) {
      (isset($this->receiver) ? $this->receiver : $this->getReceiver())->receive($handler);
   }

   /**
    * Stop receiving some messages.
    * @return void
    */
   public function stop() {
      (isset($this->receiver) ? $this->receiver : $this->getReceiver())->stop();
   }

   /**
    * Sends the given envelope.
    *
    * @param BrokerEnvelope $envelope
    */
   public function send(BrokerEnvelope $envelope) {
      (isset($this->sender) ? $this->sender : $this->getSender())->send($envelope);
   }

   private function getReceiver() {
      return $this->receiver = new MqttReceiver($this->connection);
   }

   private function getSender() {
      return $this->sender = new MqttSender($this->connection);
   }
}