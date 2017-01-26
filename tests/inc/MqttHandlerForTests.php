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

class MqttHandlerForTests extends PluginStorkmdmMqtthandler
{

   const STATE_CONSUME_PREVIOUS_MESSAGES = 1;
   const STATE_WAITING_FOR_MESSAGES = 2;
   const STATE_LISTEN_MESSAGES = 3;
   const STATE_STOP_CLIENT = 4;
   const STATE_EXIT = 5;

   protected $mqttClient;

   protected $receivedMessages = array();

   protected $sendMqttMessageCallback = null;

   /**
    * @var Callback to get a MQTT message
    */
   protected $getMqttMessageCallback = null;

   /**
    * @var Callback Callaback for PingResp
    */
   protected $pingCallback = null;

   /**
    * the state of the MQTT client
    * @param integer
    */
   protected $state;

   /**
    * @var string
    */
   protected $publishReceivedSinceLastPing = true;

   /**
    * @var integer time of the beginning of subscription when used as a MQTT subscriver
    */
   protected $startTime;

   /**
    *
    * @param array $callback
    */
   protected $topics = array();

   public function setPingCallback($callback) {
      $this->pingCallback = $callback;
   }

   public function setSendMqttMessageCallback($callback) {
      $this->sendMqttMessageCallback = $callback;
   }

   public function setGetMqttMessageCallback($callback) {
      $this->getMqttMessageCallback = $callback;
   }

   /**
    * @param string $topic
    * @param number $qos
    */
   public function subscribe($topic = '#', $qos = 0) {
      \sskaje\mqtt\Debug::SetLogPriority(\sskaje\mqtt\Debug::NONE);
      $this->state = self::STATE_CONSUME_PREVIOUS_MESSAGES;
      $this->topics = array($topic);
      $this->mqttClient = PluginStorkmdmMqttclient::getInstance();
      $this->mqttClient->setKeepalive(2); // MQTT client has odd behavior if set to 1
      $this->receivedMessages = array();
      $this->startTime = time();
      $this->mqttClient->setHandler($this);
      $this->mqttClient->subscribe($topic, $qos);
   }

   /**
    *
    * {@inheritDoc}
    * @see PluginStorkmdmMqtthandler::publish()
    */
   public function publish(sskaje\mqtt\MQTT $mqtt, sskaje\mqtt\Message\PUBLISH $publish_object) {
      $this->publishReceivedSinceLastPing = true;
      if ($this->state == self::STATE_LISTEN_MESSAGES) {
         $this->receivedMessages[] = $publish_object;
      }
   }

   /**
    *
    * @param sskaje\mqtt\MQTT $mqtt
    * @param sskaje\mqtt\Message\PINGRESP $pingresp_object
    */
   public function pingresp(sskaje\mqtt\MQTT $mqtt, sskaje\mqtt\Message\PINGRESP $pingresp_object) {
      switch ($this->state) {
         case self::STATE_CONSUME_PREVIOUS_MESSAGES:
            if (!$this->publishReceivedSinceLastPing) {
               $this->state = self::STATE_WAITING_FOR_MESSAGES;
            } else {
               $this->publishReceivedSinceLastPing = false;
            }
            $this->checkTimeout();
            break;
         case self::STATE_WAITING_FOR_MESSAGES:
            // Ask to send a mqtt message
            if (is_callable($this->sendMqttMessageCallback)) {
               call_user_func($this->sendMqttMessageCallback);
            }
            $this->state = self::STATE_LISTEN_MESSAGES;
            $this->checkTimeout();
            break;
         case self::STATE_LISTEN_MESSAGES:
            if (is_callable($this->pingCallback)) {
               call_user_func($this->pingCallback);
            }
            $this->checkTimeout();
            break;
         case self::STATE_STOP_CLIENT:
            $this->mqttClient->disconnect();
            $mqtt->unsubscribe($this->topics);
            $this->state = self::STATE_EXIT;
            break;
         case self::STATE_EXIT:
            break;
      }
   }

   protected function checkTimeout() {
      if (time() - $this->startTime > 10) {
         $this->state = self::STATE_STOP_CLIENT;
      }
   }

   /**
    * to stop mqtt client before timeout
    */
   public  function stopMqttClient() {
      $this->state = self::STATE_STOP_CLIENT;
   }

   public function getPublishedMessages() {
      return $this->receivedMessages;
   }

}