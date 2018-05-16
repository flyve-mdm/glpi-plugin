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

class PluginFlyvemdmMosquittoAuth implements PluginFlyvemdmM2mAuthInterface {
   public function authenticate($input) {
      if (!isset($input['username']) || !isset($input['password'])) {
         // No credentials or credentials incomplete
         return 404;
      }

      $remoteIp = Toolbox::getRemoteIpAddress();
      $config = Config::getConfigurationValues('flyvemdm', ['mqtt_broker_internal_address']);
      if ($config['mqtt_broker_internal_address'] != $remoteIp) {
         return 403;
      }

      $mqttUser = new PluginFlyvemdmMqttUser();
      if (!$mqttUser->getByUser($input['username'])) {
         return 404;
      }
      $input['password'] = Toolbox::stripslashes_deep($input['password']);
      if ($mqttUser->comparePasswords($input['password'])) {
         return 200;
      }

      return 404;
   }
}
