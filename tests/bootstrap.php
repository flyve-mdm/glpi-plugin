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

// fix empty CFG_GLPI on boostrap; see https://github.com/sebastianbergmann/phpunit/issues/325
global $CFG_GLPI, $PLUGIN_HOOKS, $AJAX_INCLUDE, $PLUGINS_INCLUDED;

require_once __DIR__ . '/../vendor/autoload.php';

define('TEST_GLPI_URI', 'http://localhost:8088');
define('TEST_PLUGIN_NAME', 'flyvemdm');

// glpi/inc/oolbox.class.php tests TU_USER to decide if it warns or not about mcrypt extension
define('TU_USER', '_test_user');
define('TU_PASS', 'PhpUnit_4');


define('GLPI_ROOT', dirname(dirname(dirname(__DIR__))));
define('GLPI_CONFIG_DIR', GLPI_ROOT . '/tests');
if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
   echo "config_db.php missing. Did GLPI successfully initialized ?\n";
   exit(1);
}
define('GLPI_LOG_DIR', __DIR__ . '/logs');
@mkdir(GLPI_LOG_DIR);

// Giving --debug argument to atoum will be detected by GLPI too
// the error handler in Toolbox may output to stdout a message and break process communication
// in atoum
$key = array_search('--debug', $_SERVER['argv']);
if ($key) {
   unset($_SERVER['argv'][$key]);
}

include (GLPI_ROOT . '/inc/includes.php');

// need to set theses in DB, because tests for API use http call and this bootstrap file is not called
Config::setConfigurationValues('core', [
      'url_base'     => TEST_GLPI_URI,
      'url_base_api' => TEST_GLPI_URI . '/apirest.php'
]);
$CFG_GLPI['url_base']      = TEST_GLPI_URI;
$CFG_GLPI['url_base_api']  = TEST_GLPI_URI . '/apirest.php';

// Mock PluginFlyvemdmMqttClient
include __DIR__ . "/inc/MqttClient.php";
