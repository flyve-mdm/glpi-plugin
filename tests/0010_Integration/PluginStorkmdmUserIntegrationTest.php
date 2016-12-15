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

class PluginStorkmdmUserIntegrationTest extends SelfRegistrationUserTestCase
{

   public function testSelfRegistration() {
      $validUser = array(
            "name"      => "registereduser@localhost.local",
            "password"  => "longpassword",
            "password2" => "longpassword"
      );

      // User creation with valid data
      $testUser = $validUser;
      $user = new PluginStorkmdmUser();
      $userId = $user->add($testUser);
      $this->assertNotFalse($userId, "Creation of self registered account with valid data failed");

      return $user;
   }

   /**
    * @depends testSelfRegistration
    */
   public function testSelfRegigstrationEntityExists($user) {
      $userName = $user->getField('name');
      $entity = new Entity();
      $this->assertTrue($entity->getFromDBByQuery("WHERE `name` = '$userName'"));
      return $entity;
   }

   /**
    * @depends testSelfRegigstrationEntityExists
    * @param unknown $entity
    */
   public function testSelfRegistrationEntityconfigExists($entity) {
      $entityconfig = new PluginStorkmdmEntityconfig();
      $this->assertTrue($entityconfig->getFromDB($entity->getID()));
      return $entityconfig;
   }

   /**
    * @depends testSelfRegistrationEntityconfigExists
    */
   public function testEntityconfigIsValid($entityconfig) {
      $this->assertEquals($entityconfig->getField('entities_id'), $entityconfig->getID());
   }

   public function testSelfRegistrationwithRejectedPassword() {
      $validUser = array(
            "name"      => "registereduser@localhost.local",
            "password"  => "short",
            "password2" => "short"
      );

      // User creation with valid data
      $testUser = $validUser;
      $user = new PluginStorkmdmUser();
      $userId = $user->add($testUser);
      $this->assertFalse($userId);

      return $user;
   }


   /**
    * @depends testSelfRegistration
    */
   public function testRegisteredUserHasProfile($user) {
      $config = Config::getConfigurationValues('storkmdm', array('registered_profiles_id'));

      $profile = new Profile();
      $profile->getFromDB($config['registered_profiles_id']);

      $profile_User = new Profile_User();
      $relationId = $profile_User->getFromDBForItems($user, $profile);
      $this->assertTrue($relationId);
   }

   public function testRegistrationWithInvalidEmail() {

      // User creation invalid : name is not an email address spl_autoload_functions()
      $testUser = array(
            "name"      => "invalidemail",
            "password"  => "test",
            "password2" => "test"
      );
      $user = new PluginStorkmdmUser();
      $userId = $user->add($testUser);
      $this->assertFalse($userId, "Creation of self registered account with invalid name must not succeeed");
   }

   public function testCreateUser() {
      $config = Config::getConfigurationValues('storkmdm', array('service_profiles_id'));

      $user = new User();
      $userId = $user->add(array(
            'name'            => 'rejecteduser',
            '_entities_id'    => 0,
            '_is_recursive'   => 0,
            '_profiles_id'    => $config['service_profiles_id'],

      ));
      $this->assertFalse($userId, "Creation of a user using the service profile shall fail");
   }

   public function testCreateEntity() {
      // Attempt to create an entity
      $entity = new Entity();
      $entityId = $entity->add(array(
            'name'            => 'rejected entity'
      ));
      $this->assertFalse($entityId, "Creation of an entity using the service profile shall fail");

   }

}