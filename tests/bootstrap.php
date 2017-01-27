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

// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI, $PLUGIN_HOOKS, $_CFG_GLPI;

class UnitTestAutoload
{

   public static function register() {
      spl_autoload_register(array('UnitTestAutoload', 'autoload'));
   }

   public static function autoload($className) {
      $file = __DIR__ . "/inc/$className.php";
      if (is_readable($file) && is_file($file)) {
         include_once(__DIR__ . "/inc/$className.php");
         return true;
      }
      return false;
   }

}

UnitTestAutoload::register();

define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
define("GLPI_LOG_DIR", __DIR__ . '/logs');

include (GLPI_ROOT . "/inc/includes.php");

// need to set theses in DB, because tests for API use http call and this bootstrap file is not called
Config::setConfigurationValues('core', [
      'url_base'     => GLPI_URI,
      'url_base_api' => GLPI_URI . '/apirest.php'
]);
$CFG_GLPI['url_base']      = GLPI_URI;
$CFG_GLPI['url_base_api']  = GLPI_URI . '/apirest.php';

// Mock PluginFlyvemdmMqttClient
include __DIR__ . "/inc/MqttClient.php";

