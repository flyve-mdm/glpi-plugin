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

class SuperAdminProfileIntegrationTest extends SuperAdminTestCase
{
   public function setUp() {
      parent::setUp();
      self::setupGLPIFramework();
      self::login('glpi', 'glpi', true);
   }

   /**
    * @return array Rights
    */
   public function testGetRights() {
      $profileId = $_SESSION['glpiactiveprofile']['id'];
      $rights = ProfileRight::getProfileRights(
            $profileId,
            array(
                  PluginStorkmdmAgent::$rightname,
                  PluginStorkmdmFleet::$rightname,
                  PluginStorkmdmPackage::$rightname,
                  PluginStorkmdmFile::$rightname,
                  PluginStorkmdmGeolocation::$rightname,
                  PluginStorkmdmWellknownpath::$rightname,
                  PluginStorkmdmPolicy::$rightname,
                  PluginStorkmdmPolicyCategory::$rightname,
                  PluginStorkmdmProfile::$rightname,
                  PluginStorkmdmEntityconfig::$rightname,
                  PluginStorkmdmInvitationLog::$rightname,
                  User::$rightname,
                  Profile::$rightname,
                  Computer::$rightname,
            )
      );
      $this->assertGreaterThan(0, count($rights));
      return $rights;
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfileAgentRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginStorkmdmAgent::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfileFleetRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginStorkmdmFleet::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfilePackageRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginStorkmdmPackage::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfileFileRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginStorkmdmFile::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfileGeolocationRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginStorkmdmGeolocation::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfileWellknownpathRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT, $rights[PluginStorkmdmWellknownpath::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfilePolicyRight($rights) {
      $this->assertEquals(READ, $rights[PluginStorkmdmPolicy::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfilePolicyCategoryRight($rights) {
      $this->assertEquals(READ, $rights[PluginStorkmdmPolicyCategory::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfilePluginProfileRight($rights) {
      $this->assertEquals(PluginStorkmdmProfile::RIGHT_STORKMDM_USE, $rights[PluginStorkmdmProfile::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfilePluginProfileEntityconfigRight($rights) {
      $this->assertEquals(
            READ
                  | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_DEVICE_COUNT_LIMIT
                  | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_APP_DOWNLOAD_URL
                  | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_INVITATION_TOKEN_LIFE,
            $rights[PluginStorkmdmEntityconfig::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testSuperAdminProfilePluginProfileInvitationLogRight($rights) {
      $this->assertEquals(READ, $rights[PluginStorkmdmInvitationLog::$rightname]);
   }
}
