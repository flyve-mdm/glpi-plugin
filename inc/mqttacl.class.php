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
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.19
 */
class PluginFlyvemdmMqttacl extends CommonDBTM {

   const MQTTACL_NONE = 0;
   const MQTTACL_READ = 1;
   const MQTTACL_WRITE = 2;
   const MQTTACL_READ_WRITE = 3;
   const MQTTACL_ALL = 3;

   /**
    * Delete all MQTT ACLs for the MQTT user
    * @param PluginFlyvemdmMQTTUser $mqttUser
    * @return bool
    */
   public function removeAllForUser(PluginFlyvemdmMQTTUser $mqttUser) {
      return $this->deleteByCriteria([
            'plugin_flyvemdm_mqttusers_id'   => $mqttUser->getID()
      ]);
   }

   /**
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      $input['access_level'] = $input['access_level'] & self::MQTTACL_ALL;

      return $input;
   }
}
