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

class DeleteEntityTest extends RegisteredUserTestCase {

   static $entity;
   public static function setUpBeforeClass() {
      parent::setupBeforeClass();

      self::login('glpi', 'glpi', true);
      self::$entity = new Entity();
      self::$entity->add([
            'name'   => "to be deleted",
      ]);
      Session::destroy();
   }

   public function setUp() {
      parent::setUp();
      Session::changeActiveEntities(self::$entity->getID(), 0);
   }

   /**
    *
    * @return PluginStorkmdmInvitation
    */
   public function testInitCreateInvitation() {
      $invitation = new PluginStorkmdmInvitation();

      $invitation->add([
            'entities_id'  => $_SESSION['glpiactive_entity'],
            '_useremails'  => 'a.user@localhost.local',
      ]);

      $this->assertFalse($invitation->isNewItem());

      return $invitation;
   }

   /**
    * @depends testInitCreateInvitation
    * @return PluginStorkmdmAgent
    */
   public function testInitEnrollAgent(PluginStorkmdmInvitation $invitation) {
      // Login as guest user
      $_REQUEST['user_token'] = User::getPersonalToken($invitation->getField('users_id'));
      Session::destroy();
      $this->assertTrue(self::login('', '', false));
      unset($_REQUEST['user_token']);

      // Find email of the guest user
      $userId = $invitation->getField('users_id');
      $userEmail = new UserEmail();
      $userEmail->getFromDBByQuery("WHERE `users_id`='$userId' AND `is_default` <> '0'");
      $this->assertFalse($userEmail->isNewItem());
      $guestEmail = $userEmail->getField('email');

      $agent = new PluginStorkmdmAgent();
      $agentId = $agent->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => $guestEmail,
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);
      $this->assertFalse($agent->isNewItem(), $_SESSION['MESSAGE_AFTER_REDIRECT']);

      return $agent;
   }

   /**
    *
    * @return PluginStorkmdmFleet
    */
   public function testInitCreateFleet() {
      $fleet = new PluginStorkmdmFleet();
      $fleet->add([
            'name'         => 'a fleet',
            'entities_id'  => $_SESSION['glpiactive_entity'],
      ]);

      $this->assertFalse($fleet->isNewItem());

      return $fleet;
   }

   public function testInitCreateApplication() {
      global $DB;

      $package = new PluginStorkmdmPackage();
      // Create an application (directly in DB) because we are not uploading any file
      $packageName = 'com.domain.author.application';
      $packageTable = PluginStorkmdmPackage::getTable();
      $entityId = $_SESSION['glpiactive_entity'];
      $query = "INSERT INTO $packageTable (
         `name`,
         `alias`,
         `version`,
         `filename`,
         `filesize`,
         `entities_id`,
         `dl_filename`,
         `icon`
      )
      VALUES (
         '$packageName',
         'application',
         '1.0.5',
         '$entityId/123456789_application_105.apk',
         '1048576',
         '$entityId',
         'application_105.apk',
         ''
      )";
      $DB->query($query);
      $mysqlError = $DB->error();
      $package = new PluginStorkmdmPackage();
      $this->assertTrue($package->getFromDBByQuery("WHERE `name`='$packageName'"), $mysqlError);

      return $package;
   }

   public function testInitCreateFile() {
      global $DB;

      // Create an file (directly in DB)
      $fileName = 'flyve-user-manual.pdf';
      $fileTable = PluginStorkmdmFile::getTable();
      $entityId = $_SESSION['glpiactive_entity'];
      $query = "INSERT INTO $fileTable (
         `name`,
         `source`,
         `entities_id`
      )
      VALUES (
         '$fileName',
         '2/12345678_flyve-user-manual.pdf',
         '$entityId'
      )";
      $DB->query($query);
      $mysqlError = $DB->error();
      $file = new PluginStorkmdmFile();
      $this->assertTrue($file->getFromDBByQuery("WHERE `name`='$fileName'"), $mysqlError);

      return $file;
   }

   /**
    * @depends testInitCreateInvitation
    * @depends testInitEnrollAgent
    * @depends testInitCreateFleet
    * @depends testInitCreateApplication
    * @depends testInitCreateFile
    */
   public function testDeleteEntity() {
      $entityId = $_SESSION['glpiactive_entity'];

      // Use a super admin account
      self::login('glpi', 'glpi', true);

      $entity = new Entity();
      $entity->delete(array('id' => $entityId), 1);

      $invitation = new PluginStorkmdmInvitation();
      $rows = $invitation->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);

      $agent = new PluginStorkmdmAgent();
      $rows = $agent->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);

      $fleet = new PluginStorkmdmFleet();
      $rows = $fleet->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);

      $application = new PluginStorkmdmPackage();
      $rows = $application->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);

      $file = new PluginStorkmdmFile();
      $rows = $file->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);

      $entityConfig = new PluginStorkmdmEntityconfig();
      $rows = $entityConfig->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);
   }
}