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

class RegisteredUserEditEntityConfig extends RegisteredUserTestCase
{

   public function testEditDeviceLimit() {
      // getDefault limit
      $config = Config::getConfigurationValues('flyvemdm', array('default_device_limit'));

      // update the device limit
      $entityConfig = new PluginFlyvemdmEntityconfig();
      $entityConfig->getFromDB($_SESSION['glpiactive_entity']);

      $this->assertTrue($entityConfig->update([
            'id'              => $_SESSION['glpiactive_entity'],
            'device_limit'    => 999
      ]));

      // Check the limit has not changed
      $this->assertEquals($config['default_device_limit'], $entityConfig->getField('device_limit'));

   }

   public function testEditDownloadUrl() {
      // getDefault limit
      $config = Config::getConfigurationValues('flyvemdm', array('default_agent_url'));

      // update the device limit
      $entityConfig = new PluginFlyvemdmEntityconfig();
      $entityConfig->getFromDB($_SESSION['glpiactive_entity']);

      $this->assertTrue($entityConfig->update([
            'id'              => $_SESSION['glpiactive_entity'],
            'download_url'    => 'http://myserver.com/agent_v0123.apk'
      ]));

      // Check the limit has not changed
      $this->assertEquals('http://myserver.com/agent_v0123.apk', $entityConfig->getField('download_url'));
   }


   public function testEditInvitationTokenLife() {

      // update the device limit
      $entityConfig = new PluginFlyvemdmEntityconfig();
      $entityConfig->getFromDB($_SESSION['glpiactive_entity']);

      $this->assertTrue($entityConfig->update([
            'id'                 => $_SESSION['glpiactive_entity'],
            'agent_token_life'   => 'P99D'
      ]));

      // Check the limit has not changed
      $this->assertEquals('P99D', $entityConfig->getField('agent_token_life'));
   }
}