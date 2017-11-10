#!/usr/bin/php
<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/DaemonInterface.php';
require_once __DIR__ . '/Daemon.php';

class MqttClientDaemon extends Daemon {
   public function loop() {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      require_once __DIR__ . '/../vendor/autoload.php';
      require_once __DIR__ . '/../../../inc/includes.php';

      if ($this->getDebug()) {
         $logger = new Logger('stdout');
         $logger->pushHandler(new StreamHandler("php://stdout"));
      }

      include (__DIR__ . '/../../../inc/includes.php');

      $mqttClient = PluginFlyvemdmMqttclient::getInstance();
      $mqttClient->setHandler(PluginFlyvemdmMqtthandler::getInstance());
      $mqttClient->subscribe();
   }
}

$collectdDaemon = new MqttClientDaemon();
$collectdDaemon->main($argc, $argv);