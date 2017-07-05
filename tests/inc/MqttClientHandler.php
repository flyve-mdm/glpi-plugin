<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2016 by the FusionInventory Development Team.
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
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

use Nette\Callback;

class MqttClientHandler extends \sskaje\mqtt\MessageHandler
{
   protected static  $mqtt;

   /**
    * @var boolean Maintain connection
    */
   protected $disconnect = false;

   /**
    * @var integer time of the beginning of subscription when used as a MQTT subscriver
    */
   protected         $startTime;

   /**
    * @var boolean Consumes persisted incoming mesages and ignores them
    */
   protected $consumePersisted = true;

   /**
    * @var boolean Last received mesage was persisted
    */
   protected $persistedReceived = false;

   /**
    * @var sskaje\mqtt\Message\PUBLISH published message
    */
   protected $published = null;

   /**
    * @var Callback Callaback for PingResp
    */
   protected $pingCallback = null;

   /**
    * @var Callback to get a MQTT message
    */
   protected $getMqttMessageCallback;

   /**
    * @var Callback to send a MQTT message
    */
   protected $sendMqttMessage = null;

   /**
    * @var auto disconnect after 1st incoming message
    */
   protected $autoDisconnect = true;

   protected $topics = array();

   public function __construct() {
      self::$mqtt = $this->getMQTTConnection();
   }

   public function setAutoDisconect($autoDisconnect) {
      $this->autoDisconnect = $autoDisconnect;
   }

   /**
    * get an instance of sskaje/mqtt/MQTT
    * @return sskaje\mqtt\MQTT|false MQTT object
    */
   protected function getMQTTConnection() {
      $config = Config::getConfigurationValues('flyvemdm', array(
            'mqtt_broker_internal_address',
            'mqtt_broker_port',
            'mqtt_broker_tls',
            'mqtt_broker_tls_ciphers',
            'mqtt_user',
            'mqtt_passwd'
      ));
      if (empty($config['mqtt_broker_internal_address'])
            ||empty($config['mqtt_broker_port'])
            ||(!isset($config['mqtt_broker_tls']))) {
         return false;
      } else {

         $mqttBrokerAddress = $config['mqtt_broker_internal_address'];
         $mqttBrokerPort = $config['mqtt_broker_port'];

         if ($config['mqtt_broker_tls'] != '0') {

            // Establish TLS connection (this is not SSL, and SSL is weak nowadays)
            $socketAddress = "tls://$mqttBrokerAddress:$mqttBrokerPort";
            $mqtt = new sskaje\mqtt\MQTT($socketAddress);
            $mqtt->setSocketContext(stream_context_create([
                  'ssl' => [
                        // TODO : Enable commented out parameters to enhance security. Check the documentation
                        'cafile'                => FLYVEMDM_CONFIG_CACERTMQTT,
                        'verify_depth'          => 5,
                        'verify_peer'           => true,
                        //'verify_peer_name'      => true,
                        //'peer_name'             => 'sub.domain.com',
                        'disable_compression'   => true,
                        'SNI_enabled'           => true,
                        'ciphers'               => $config['mqtt_broker_tls_ciphers'],

                        // for testing purpose
                        //'allow_self_signed'     => true,
                        'verify_peer'           => false,
                        'verify_peer_name'      => false,

                  ]
            ]));

         } else {

            // Establish clear connection
            $socketAddress = "tcp://$mqttBrokerAddress:$mqttBrokerPort";
            $mqtt = new sskaje\mqtt\MQTT($socketAddress);

         }
         $mqtt->setAuth($config['mqtt_user'], $config['mqtt_passwd']);
         try {
            if (!$mqtt->connect()) {

               return false;

            }
         } catch (Exception $e) {

            $error = "Exception while connecting to the mqtt broker : " . $e->getMessage();
            $trace = $e->getTraceAsString();
            Toolbox::logInFile("mqtt", "$error\n$trace\n\n");
            return false;

         }

      }
      return $mqtt;

   }

   /**
    * @see PluginFlyvemdmMqttclient::subscribe()
    */
   public function subscribe($topic = "#", $qos = 0) {
      $this->startTime = time();

      //$mqtt = $this->getMQTTConnection();
      if (self::$mqtt === false) {
         return;
      }

      $this->topics[$topic] = $qos;
      self::$mqtt->setHandler($this);
      self::$mqtt->setKeepalive(2); // MQTT client has odd behavior if set to 1
      self::$mqtt->subscribe($this->topics);

      while (!$this->disconnect) {

         try {
            self::$mqtt->loop();
         } catch (Exception $e) {

            $error = "Exception while listening MQTT messages : \n" . $e->getMessage();
            $trace = $e->getTraceAsString();

            Toolbox::logInFile("mqtt", "$error\n$trace\n\n");
            self::$mqtt->reconnect();

         }

      }
      self::$mqtt->disconnect();
   }

   /**
    * @param sskaje\mqtt\MQTT $mqtt
    */
   protected function unsubscribeAll(sskaje\mqtt\MQTT $mqtt) {
      $this->disconnect = true;
      $mqtt->unsubscribe($this->topics);
   }

   /**
    * @param sskaje\mqtt\MQTT $mqtt
    * @param sskaje\mqtt\Message\PINGRESP $pingresp_object
    */
   public function pingresp(sskaje\mqtt\MQTT $mqtt, sskaje\mqtt\Message\PINGRESP $pingresp_object) {
      if ($this->persistedReceived === false && $this->consumePersisted === true) {
         $this->consumePersisted = false;
         // Ask to send a mqtt message
         if (is_callable($this->sendMqttMessage)) {
            call_user_func($this->sendMqttMessage);
         }
      }

      $this->persistedReceived = false;
      if (is_callable($this->pingCallback)) {
         call_user_func($this->pingCallback);
      }

      // Discoonnect on timeout or incoming message
      if (time() - $this->startTime > 20 || ($this->autoDisconnect && $this->published !== null)) {
         $this->unsubscribeAll($mqtt);
      }
   }

   /**
    * @see \sskaje\mqtt\MessageHandler::publish()
    */
   public function publish(sskaje\mqtt\MQTT $mqtt, sskaje\mqtt\Message\PUBLISH $publish_object) {
      if ($this->consumePersisted) {
         $this->persistedReceived = true;
      } else {
         $this->published = $publish_object;
         if (! $this->autoDisconnect) {
            $this->startTime = time();
            if (is_callable($this->getMqttMessageCallback)) {
               call_user_func($this->getMqttMessageCallback, $publish_object);
            }
         }
      }
   }

   public function setPingCallback($callback) {
      $this->pingCallback = $callback;
   }

   public function setSendMqttMessageCallback($callback) {
      $this->sendMqttMessage = $callback;
   }

   public function setGetMqttMessageCallback($callback) {
      $this->getMqttMessageCallback = $callback;
   }

   /**
    * @sskaje\mqtt\Message\PUBLISH message
    */
   public function getPublishedMessage() {
      return $this->published;
   }

}