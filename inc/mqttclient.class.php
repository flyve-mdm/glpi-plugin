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
class PluginFlyvemdmMqttclient {

   const MQTT_MAXIMUM_DURATION = 86400; // 24h

   /**
    * @var integer time of the beginning of subscription when used as a MQTT subscriver
    */
   protected      $beginTimestamp;

   /**
    * @var sskaje\mqtt\MQTT instance of MQTT
    */
   protected static $mqtt;

   protected $disconnect = false;

   protected $duration = self::MQTT_MAXIMUM_DURATION;

   /**
    * @var PluginFlyvemdmMqttclient instance of this class (singleton)
    */
   private static $instance = null;

   /**
    * PluginFlyvemdmMqttclient constructor.
    */
   private function __construct() {
      self::$mqtt = $this->getMQTTConnection();
   }

   /**
    * Get the unique instance of PluginFlyvemdmMqttclient
    * @return PluginFlyvemdmMqttclient instance of this class (singleton)
    */
   public static function getInstance() {
      if (self::$instance === null) {
         self::$instance = new static();
      }

      return self::$instance;
   }

   /**
    * Sets the MQTT handler
    * @param sskaje\mqtt\MessageHandler $mqttHandler
    */
   public function setHandler($mqttHandler) {
      self::$mqtt->setHandler($mqttHandler);
   }

   /**
    * Sets the keep alive of the mqtt
    * @param integer $keepalive
    */
   public function setKeepalive($keepalive = 60) {
      if ($keepalive < 2) {
         $keepalive = 2;
      }
      self::$mqtt->setKeepalive($keepalive);
   }

   /**
    * Sets the maximun duration of the object
    * @param integer $duration
    */
   public function setMaxDuration($duration) {
      $this->duration = $duration;
   }

   /**
    * This method is used as a service running PHP-CLI only
    * @param string $topic
    * @param integer $qos
    */
   public function subscribe($topic = "#", $qos = 0) {
      $this->disconnect = false;
      $this->beginTimestamp = time();

      if (self::$mqtt === false) {
         exit(1);
      }
      $topics = [$topic => $qos];
      self::$mqtt->subscribe($topics);

      while (!$this->mustDisconnect()) {
         try {
            self::$mqtt->loop();
         } catch (Exception $e) {
            $error = "Exception while listening MQTT messages : \n" . $e->getMessage();
            $trace = $e->getTraceAsString();

            Toolbox::logInFile("mqtt", "$error\n$trace\n\n");
            self::$mqtt->reconnect(true);
            self::$mqtt->subscribe($topics);
         }
      }
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
         if (self::$mqtt !== false) {
            $log = new PluginFlyvemdmMqttlog();
            if (self::$mqtt->publish_sync($topic, $message, $qos, $retain)) {
               $log->saveOutgoingMqttMessage($topic, $message);
               return true;
            }
         } else {
            throw new Exception("Cannot connect to broker");
         }
      } catch (Exception $e) {
         $error = "Exception while puslishing on $topic : '$message'\n" . $e->getMessage();
         $trace = $e->getTraceAsString();

         Toolbox::logInFile("mqtt", "$error\n$trace\n\n");
      }
      return false;
   }

   /**
    * Breaks the infinite loop implemented in the MQTT client library using the ping response event
    */
   public function pingresp() {
      if ($this->disconnect) {
         self::$mqtt->disconnect();
      }
   }

   /**
    * Disconnects the MQTT client
    */
   public function disconnect() {
      $this->disconnect = true;
   }

   /**
    * Sets when it must disconnect the MQTT client
    */
   protected function mustDisconnect() {
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
            $message =  "Hello, MQTT Broker !";
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
    * @return sskaje\mqtt\MQTT|false MQTT object or false on error
    */
   protected function getMQTTConnection() {
      $config = Config::getConfigurationValues('flyvemdm', [
         'mqtt_broker_internal_address',
         'mqtt_broker_port',
         'mqtt_broker_tls_port',
         'mqtt_tls_for_backend',
         'mqtt_broker_tls_ciphers',
         'mqtt_user',
         'mqtt_passwd'
      ]);
      if (!isset($config['mqtt_broker_internal_address'])
          || !isset($config['mqtt_broker_port']) || !isset($config['mqtt_broker_tls_port'])
          || (!isset($config['mqtt_tls_for_backend']))) {
          Toolbox::logInFile('mqtt', 'at least one MQTT configuration setting is missing');
         return false;
      } else {
         $mqttBrokerAddress = $config['mqtt_broker_internal_address'];
         $mqttBrokerPort = $config['mqtt_broker_port'];
         $isTls = $config['mqtt_tls_for_backend'] != '0';
         if ($isTls) {
            $mqttBrokerPort = $config['mqtt_broker_tls_port'];
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
    * @return sskaje\mqtt\MQTT an instance of a MQTT client
    */
   protected function buildMqtt($socketAddress, $port, $isTls, $sslCipher) {
      $protocol = $isTls ? "ssl://" : "tcp://";
      $mqtt = new sskaje\mqtt\MQTT("$protocol$socketAddress:$port");
      if ($isTls) {
         Toolbox::logInFile("mqtt", "setting context ssl with $sslCipher");
         $mqtt->setSocketContext(stream_context_create([
               'ssl' => [
                   'cafile'                => FLYVEMDM_CONFIG_CACERTMQTT,
                   'verify_peer'           => false,
                   'verify_peer_name'      => false,
                   'disable_compression'   => true,
                   'ciphers'               => $sslCipher,
                   'crypto_method'         => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
                   'SNI_enabled'           => true,
                   'allow_self_signed'     => true
               ]
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
