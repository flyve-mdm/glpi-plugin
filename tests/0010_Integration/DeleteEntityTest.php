<?php
/*
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
 @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

use Flyvemdm\Test\ApiRestTestCase;

class DeleteEntityTest extends ApiRestTestCase {

   protected static $sessionToken;

   protected static $entityId;

   protected static $entity;

   protected static $invitation;

   protected static $guestEmail;

   protected static $guestUser;

   protected static $agent;

   protected static $defaultFleet;

   protected static $fleet;

   protected static $package;

   protected static $file;

   public static function setUpBeforeClass() {
      global $DB;

      parent::setupBeforeClass();

      self::login('glpi', 'glpi', true);
      self::$entity = new Entity();
      self::$entity->add([
            'name'   => "to be deleted",
      ]);
      $entityId = self::$entity->getID();

      self::$guestEmail = 'a.user@localhost.local';

      // create invitation
      self::$invitation = new PluginFlyvemdmInvitation();
      self::$invitation->add([
            'entities_id'  => $entityId,
            '_useremails'  => self::$guestEmail,
      ]);

      self::$guestUser = new User();
      self::$guestUser->getFromDB(self::$invitation->getField('users_id'));

      Session::destroy();
      self::setupGLPIFramework();

      // Login as guest user
      $_REQUEST['user_token'] = User::getPersonalToken(self::$invitation->getField('users_id'));
      self::login('', '', false);
      unset($_REQUEST['user_token']);

      // enroll an agent
      self::$agent = new PluginFlyvemdmAgent();
      self::$agent->add([
            'entities_id'        => $entityId,
            '_email'             => self::$guestEmail,
            '_invitation_token'  => self::$invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);

      Session::destroy();
      self::setupGLPIFramework();

      // login as super admin
      self::login('glpi', 'glpi', true);

      //find default fleet
      self::$defaultFleet = new PluginFlyvemdmFleet();
      self::$defaultFleet->getFromDBByQuery("WHERE `entities_id` = '$entityId' AND `is_default` <> '0'");

      // create a fleet
      self::$fleet = new PluginFlyvemdmFleet();
      self::$fleet->add([
            'name'         => 'a fleet',
            'entities_id'  => $entityId,
      ]);

      self::$package = new PluginFlyvemdmPackage();
      // Create an application (directly in DB) because we are not uploading any file
      $packageName = 'com.domain.author.application';
      $packageTable = PluginFlyvemdmPackage::getTable();
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
      self::$package->getFromDBByQuery("WHERE `name`='$packageName'");

      // Create an file (directly in DB)
      self::$file = new PluginFlyvemdmFile();
      $fileName = 'flyve-user-manual.pdf';
      $fileTable = PluginFlyvemdmFile::getTable();
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
      self::$file->getFromDBByQuery("WHERE `name`='$fileName'");

      Session::destroy();
   }

   /**
    *
    */
   public function testInitGetSessionToken() {
      $this->initSessionByCredentials('glpi', 'glpi');
      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      self::$sessionToken = $this->restResponse['session_token'];
      self::$entityId = $_SESSION['glpiactive_entity'];
   }

   /**
    *
    */
   public function testDeleteEntity() {
      $entityId = self::$entity->getID();

      $body = json_encode([
            'input' => [
                  'id'  => $entityId,
            ],
            'force_purge' => true,
      ]);
      $this->entity("delete", self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Check invitations were purged
      $this->assertFalse(self::$invitation->isNewItem());
      self::$invitation = new PluginFlyvemdmInvitation();
      self::$invitation->getFromDB(self::$invitation->getID());
      $this->assertTrue(self::$invitation->isNewItem());

      // check no invitation exist in the purged entity
      $invitation = new PluginFlyvemdmInvitation();
      $rows = $invitation->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);

      // check fleets were purged
      $this->assertFalse(self::$defaultFleet->isNewItem());
      self::$defaultFleet = new PluginFlyvemdmFleet();
      self::$defaultFleet->getFromDB(self::$defaultFleet->getID());
      $this->assertTrue(self::$defaultFleet->isNewItem());

      $this->assertFalse(self::$fleet->isNewItem());
      self::$fleet = new PluginFlyvemdmFleet();
      self::$fleet->getFromDB(self::$fleet->getID());
      $this->assertTrue(self::$fleet->isNewItem());

      // check no fleet exists in the purged entity
      $fleet = new PluginFlyvemdmFleet();
      $rows = $fleet->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);

      // check agents were purged
      $this->assertFalse(self::$agent->isNewItem());
      self::$agent = new PluginFlyvemdmAgent();
      self::$agent->getFromDB(self::$agent->getID());
      $this->assertTrue(self::$agent->isNewItem());

      // check no agent exists in the purged entity
      $agent = new PluginFlyvemdmAgent();
      $rows = $agent->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);

      // check applications were purged
      $this->assertFalse(self::$package->isNewItem());
      self::$package = new PluginFlyvemdmPackage();
      self::$package->getFromDB(self::$package->getID());
      $this->assertTrue(self::$package->isNewItem());

      // check no application exists in the purged entity
      $application = new PluginFlyvemdmPackage();
      $rows = $application->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);

      // check files were purged
      $this->assertFalse(self::$file->isNewItem());
      self::$file = new PluginFlyvemdmFile();
      self::$file->getFromDB(self::$file->getID());
      $this->assertTrue(self::$file->isNewItem());

      // check no file exists in the purged entity
      $file = new PluginFlyvemdmFile();
      $rows = $file->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);

      // check no entityConfig exists for the purged entity
      $entityConfig = new PluginFlyvemdmEntityconfig();
      $rows = $entityConfig->find("`entities_id` = '$entityId'");
      $this->assertCount(0, $rows);
   }
}