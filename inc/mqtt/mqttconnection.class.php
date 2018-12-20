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

use Config;
use Exception;
use PluginFlyvemdmMqttlog;
use sskaje\mqtt\Exception as MqttException;
use sskaje\mqtt\MessageHandler;
use sskaje\mqtt\MQTT;
use Toolbox;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class MqttConnection {


   const MQTT_MAXIMUM_DURATION = 86400; // 24h

   /**
    * @var integer time of the beginning of subscription when used as a MQTT subscriver
    */
   protected $beginTimestamp;

   /**
    * @var MQTT instance of MQTT
    */
   protected static $mqtt;

   protected $disconnect = false;

   protected $duration = self::MQTT_MAXIMUM_DURATION;

   /**
    * instance of this class (singleton)
    * @var $this
    */
   private static $instance = null;

   /**
    * $this constructor.
    */
   private function __construct() {
      self::$mqtt = $this->getMQTTConnection();
   }

   /**
    * Get the unique instance of this class (singleton)
    * @return $this
    */
   public static function getInstance() {
      if (self::$instance === null) {
         self::$instance = new static();
      }

      return self::$instance;
   }

   /**
    * Sets the MQTT handler
    * @param MessageHandler $mqttHandler
    */
   public function setHandler($mqttHandler) {
      self::$mqtt->setHandler($mqttHandler);
   }

   public function getMQTT() {
      return self::$mqtt;
   }

   /**
    * @param array|string $topic
    * @param string $message
    * @param integer $qos
    * @param integer $retain
    * @return true if success, false otherwise
    */
   public function publish($topic, $message, $qos = 0, $retain = 0) {
      try {
         if (self::$mqtt === false) {
            throw new Exception("Cannot connect to broker");
         }
         if (self::$mqtt->publish_sync($topic, $message, $qos, $retain)) {
            $log = new PluginFlyvemdmMqttlog();
            $log->saveOutgoingMqttMessage($topic, $message);
            return true;
         }
      } catch (Exception $e) {
         $error = "Exception while puslishing on $topic : '$message'\n" . $e->getMessage();
         $trace = $e->getTraceAsString();

         Toolbox::logInFile("mqtt", "$error\n$trace\n\n");
      }
      return false;
   }

   /**
    * Disconnects the MQTT client
    */
   public function disconnect() {
      return $this->disconnect = true;
   }

   /**
    * Sets when it must disconnect the MQTT client
    */
   public function mustDisconnect() {
      if ((time() - $this->beginTimestamp) > $this->duration) {
         return true;
      }
      return $this->disconnect;
   }

   /**
    * Send a test message to the MQTT broker
    * @param string $address
    * @param integer $port
    * @param boolean $isTls
    * @param string $sslCipher
    * @return bool test succeeded (true) or failed (false)
    */
   public function sendTestMessage($address, $port, $isTls, $sslCipher) {
      // Sanity check
      $port = intval($port);

      $config = Config::getConfigurationValues('flyvemdm', ['mqtt_user', 'mqtt_passwd']);
      if (empty($config['mqtt_user']) || empty($config['mqtt_passwd'])) {
         return false;
      }

      try {
         Toolbox::logInFile("mqtt", "mqtt testing with param: $address on port $port. Tls: $isTls");
         $mqtt = $this->buildMqtt($address, $port, $isTls, $sslCipher);
         $mqtt->setAuth($config['mqtt_user'], $config['mqtt_passwd']);
         if ($mqtt->connect()) {
            $log = new PluginFlyvemdmMqttlog();
            $topic = "/testtopic";
            $message = "Hello, MQTT Broker !";
            $mqtt->publish_sync($topic, $message, 0, 0);
            $log->saveOutgoingMqttMessage($topic, $message);
            return true;
         }
      } catch (Exception $e) {
         $error = "Exception while connecting to the mqtt broker : " . $e->getMessage();
         $trace = $e->getTraceAsString();
         Toolbox::logInFile("mqtt", "$error\n$trace\n\n");
         return false;
      }

      return false;
   }

   /**
    * get an instance of sskaje/mqtt/MQTT or false on error
    * @return MQTT|false MQTT object or false on error
    */
   protected function getMQTTConnection() {
      $config = Config::getConfigurationValues('flyvemdm', [
         'mqtt_broker_internal_address',
         'mqtt_broker_port_backend',
         'mqtt_broker_tls_port_backend',
         'mqtt_tls_for_backend',
         'mqtt_broker_tls_ciphers',
         'mqtt_user',
         'mqtt_passwd',
      ]);
      if (!isset($config['mqtt_broker_internal_address'])
         || !isset($config['mqtt_broker_port_backend']) || !isset($config['mqtt_broker_tls_port_backend'])
         || (!isset($config['mqtt_tls_for_backend']))) {
         Toolbox::logInFile('mqtt', 'at least one MQTT configuration setting is missing');
         return false;
      } else {
         $mqttBrokerAddress = $config['mqtt_broker_internal_address'];
         $mqttBrokerPort = $config['mqtt_broker_port_backend'];
         $isTls = $config['mqtt_tls_for_backend'] != '0';
         if ($isTls) {
            $mqttBrokerPort = $config['mqtt_broker_tls_port_backend'];
         }
         $sslCiphers = $config['mqtt_broker_tls_ciphers'];
         $mqtt = $this->buildMqtt($mqttBrokerAddress, $mqttBrokerPort, $isTls, $sslCiphers);
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
    * Builds a MQTT
    * @param string $socketAddress
    * @param integer $port
    * @param boolean $isTls
    * @param string $sslCipher
    * @return MQTT an instance of a MQTT client
    */
   protected function buildMqtt($socketAddress, $port, $isTls, $sslCipher) {
      $protocol = $isTls ? "ssl://" : "tcp://";
      try {
         $mqtt = new MQTT("$protocol$socketAddress:$port");
      } catch (MqttException $e) {
         Toolbox::logInFile("mqtt", "problem creating MQTT client, " . $e->getMessage() . PHP_EOL);
      }
      if ($isTls) {
         Toolbox::logInFile("mqtt", "setting context ssl with $sslCipher");
         $mqtt->setSocketContext(stream_context_create([
               'ssl' => [
                  'cafile'              => FLYVEMDM_CONFIG_CACERTMQTT,
                  'verify_peer'         => false,
                  'verify_peer_name'    => false,
                  'disable_compression' => true,
                  'ciphers'             => $sslCipher,
                  'crypto_method'       => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
                  'SNI_enabled'         => true,
                  'allow_self_signed'   => true,
               ],
            ]
         ));
      }
      // The (keepalive / 2) delay must be lower than the mysql timeout delay
      // When the client receives a PINGRESP message, the DB conection
      // is re-established
      // here : 50 / 2 lower than 30s
      $mqtt->setKeepalive(50);

      return $mqtt;
   }

}
