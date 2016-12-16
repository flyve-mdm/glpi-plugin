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

class RegisteredUserProfileIntegrationTest extends RegisteredUserTestCase
{

   public static function setupBeforeClass() {
      parent::setupBeforeClass();
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
                  PluginStorkmdmInvitationlog::$rightname,
                  Config::$rightname,
                  User::$rightname,
                  Profile::$rightname,
                  Entity::$rightname,
                  Computer::$rightname,
                  Software::$rightname,
                  NetworkPort::$rightname,
                  CommonDropdown::$rightname,
            )
      );
      $this->assertGreaterThan(0, count($rights));
      return $rights;
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileAgentRight($rights) {
      $this->assertEquals(READ | UPDATE | DELETE | PURGE | READNOTE | UPDATENOTE, $rights[PluginStorkmdmAgent::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileFleetRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginStorkmdmFleet::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfilePackageRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginStorkmdmPackage::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileFileRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT | READNOTE | UPDATENOTE, $rights[PluginStorkmdmFile::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileGeolocationRight($rights) {
      $this->assertEquals(READ | PURGE, $rights[PluginStorkmdmGeolocation::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileWellknownpathRight($rights) {
      $this->assertEquals(READ, $rights[PluginStorkmdmWellknownpath::$rightname]);
   }
   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUsernProfilePolicyRight($rights) {
      $this->assertEquals(READ, $rights[PluginStorkmdmPolicy::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfilePolicyCategoryRight($rights) {
      $this->assertEquals(READ, $rights[PluginStorkmdmPolicyCategory::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfilePluginProfileRight($rights) {
      $this->assertEquals(PluginStorkmdmProfile::RIGHT_STORKMDM_USE, $rights[PluginStorkmdmProfile::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileUserRight($rights) {
      $this->assertEquals(ALLSTANDARDRIGHT, $rights[User::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileProfileRight($rights) {
      $this->assertEquals(CREATE, $rights[Profile::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileComputerRight($rights) {
      $this->assertEquals(READ, $rights[Computer::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileSoftwareRight($rights) {
      $this->assertEquals(READ, $rights[Software::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileEntityconfigRight($rights) {
      $this->assertEquals(
            READ
                  | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_APP_DOWNLOAD_URL
                  | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_INVITATION_TOKEN_LIFE,
            $rights[PluginStorkmdmEntityconfig::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileDropdownRight($rights) {
      $this->assertEquals(READ, $rights[CommonDropdown::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileInvitatioinLogRight($rights) {
      $this->assertEquals(READ, $rights[PluginStorkmdmInvitationlog::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileEntityRight($rights) {
      $this->assertEquals(CREATE, $rights[Entity::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileNetworkPortRight($rights) {
      $this->assertEquals(READ, $rights[NetworkPort::$rightname]);
   }

   /**
    * @depends testGetRights
    * @param array $rights
    */
   public function testRegisteredUserProfileConfigRight($rights) {
      $this->assertEquals(READ, $rights[Config::$rightname]);
   }

}
