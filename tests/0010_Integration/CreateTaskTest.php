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


class CreateTaskTest extends ApiRestTestCase
{

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
      $entityId = $_SESSION['glpiactive_entity'];

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

      // put the agent in a managed fleet
      self::$agent->update([
            'id'                          => self::$agent->getID(),
            'plugin_flyvemdm_fleets_id'   => self::$fleet->getID(),
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

   public function testApplyPolicies() {
      global $DB;

      $policyData = new PluginFlyvemdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('deployApp'));

      $value = new stdClass();
      $value->remove_on_delete = '1';

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_flyvemdm_fleets_id'   => self::$fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policyData->getID(),
            'value'                       => $value,
            'itemtype'                    => 'PluginFlyvemdmPackage',
            'items_id'                    => self::$package->getID(),
      ]);

      // update policies now
      $mqttUpdateQueueTable = PluginFlyvemdmMqttupdatequeue::getTable();
      $DB->query("UPDATE `$mqttUpdateQueueTable` SET `date` = DATE_SUB(`date`, INTERVAL 1 HOUR)");
      $task = new CronTask();
      PluginFlyvemdmMqttupdatequeue::cronUpdateTopics($task);
   }
}