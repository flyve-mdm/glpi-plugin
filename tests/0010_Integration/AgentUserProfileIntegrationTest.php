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

class AgentUserProfileIntegrationTest extends RegisteredUserTestCase
{


   public function testGetAgentProfileIdFromConfig() {
      $config = Config::getConfigurationValues('flyvemdm', ['agent_profiles_id']);
      $this->assertArrayHasKey('agent_profiles_id', $config);
      $this->assertGreaterThan(0, $config['agent_profiles_id']);

      return $config['agent_profiles_id'];
   }

   /**
    * @depends testGetAgentProfileIdFromConfig
    * @return array Rights
    */
   public function testGetRights($profileId) {
      $rights = ProfileRight::getProfileRights(
         $profileId,
         [
            PluginFlyvemdmAgent::$rightname,
            PluginFlyvemdmPackage::$rightname,
            PluginFlyvemdmFile::$rightname,
            PluginFlyvemdmEntityConfig::$rightname,
         ]
      );
      $this->assertGreaterThan(0, count($rights));
      $this->assertEquals(READ, $rights[PluginFlyvemdmAgent::$rightname]);
      $this->assertEquals(READ, $rights[PluginFlyvemdmFile::$rightname]);
      $this->assertEquals(READ, $rights[PluginFlyvemdmPackage::$rightname]);
      $this->assertEquals(READ, $rights[PluginFlyvemdmEntityConfig::$rightname]);
   }
}
