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
 * @author    the flyvemdm plugin team
 * @copyright Copyright © 2018 Teclib
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmUpgradeTodev {
   /**
    * @param Migration $migration
    */
   function upgrade(Migration $migration) {
      global $DB;

      $migration->setVersion(PLUGIN_FLYVEMDM_VERSION);

      $config = Config::getConfigurationValues('flyvemdm');
      if (!isset($config['mqtt_broker_port_backend'])) {
         // Split port setting for client in one hand and backend in the other hand
         $config['mqtt_broker_tls_port_backend'] = $config['mqtt_broker_tls_port'];
         $config['mqtt_broker_port_backend'] = $config['mqtt_broker_port'];
         Config::setConfigurationValues('flyvemdm', $config);
      }

      // Merge new rights into guest profile
      $profileId = $config['guest_profiles_id'];
      $currentRights = ProfileRight::getProfileRights($profileId);
      $newRights = array_merge($currentRights, [
         PluginFlyvemdmAgent::$rightname      => CREATE| READ | UPDATE ,
      ]);
      $profileRight = new ProfileRight();
      $profileRight->updateProfileRights($profileId, $newRights);
   }
}
