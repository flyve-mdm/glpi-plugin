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

use sskaje\mqtt\MQTT;

class PluginFlyvemdmAgentIntegrationTest extends RegisteredUserTestCase {

   /**
    * Create an invitation for enrollment tests
    */
   public function testInvitationCreation() {
      self::$fixture['guestEmail'] = 'guestuser0001@localhost.local';

      $invitation = new PluginFlyvemdmInvitation();
      $invitationId = $invitation->add([
         'entities_id'  => $_SESSION['glpiactive_entity'],
         '_useremails'  => self::$fixture['guestEmail'],
      ]);
      $this->assertGreaterThan(0, $invitationId);

      return $invitation;
   }

   /**
    * Enrolls an agent as guest user
    * @depends testInvitationCreation
    */
   public function testEnrollAgent($invitation) {
      // Login as guest user
      $_REQUEST['user_token'] = User::getToken($invitation->getField('users_id'), 'api_token');
      Session::destroy();
      $this->assertTrue(self::login('', '', false));
      unset($_REQUEST['user_token']);

      // Find email of the guest user
      $userId = $invitation->getField('users_id');
      $userEmail = new UserEmail();
      $userEmail->getFromDBByQuery("WHERE `users_id`='$userId' AND `is_default` <> '0'");
      $this->assertFalse($userEmail->isNewItem());
      $guestEmail = $userEmail->getField('email');

      $agent = new PluginFlyvemdmAgent();
      $agentId = $agent ->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => $guestEmail,
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => $this->getUniqueString(),
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);
      $this->assertFalse($agent->isNewItem(), $_SESSION['MESSAGE_AFTER_REDIRECT']);

      return $agent;
   }

   /**
    * @depends testInvitationCreation
    * @depends testEnrollAgent
    */
   public function testInvitationUpdate(PluginFlyvemdmInvitation $invitation) {
      // Refresh the invitation from DB
      $invitation->getFromDB($invitation->getID());

      $this->assertEquals('done', $invitation->getField('status'));
   }

   /**
    * @depends testEnrollAgent
    * @depends testInvitationCreation
    */
   public function testGetEnrollData($agent, $invitation) {
      // Login as guest user
      $_REQUEST['user_token'] = User::getToken($invitation->getField('users_id'), 'api_token');
      Session::destroy();
      $this->assertTrue(self::login('', '', false));
      unset($_REQUEST['user_token']);

      // Get the agent from DB as gurst user
      // Triggers post_getFromDB()
      $this->assertTrue($agent->getFromDB($agent->getID()));

      return $agent;
   }


   /**
    * @depends testEnrollAgent
    * @depends testGetEnrollData
    */
   public function testChangeFleet($agent) {
      $fleet = new PluginFlyvemdmFleet();
      $fleet->add([
            'entities_id'  => $_SESSION['glpiactive_entity'],
            'name'         => 'fleet A'
      ]);
      $this->assertFalse($fleet->isNewItem(), "Could not create a fixture fleet");

      $mockAgent = $this->getMockForItemtype(PluginFlyvemdmAgent::class, ['notify']);

      $mockAgent->expects($this->once())
      ->method('notify')
      ->with(
            $this->equalTo($agent->getTopic() . "/Command/Subscribe"),
            $this->equalTo(json_encode(['subscribe' => [['topic' => $fleet->getTopic()]]], JSON_UNESCAPED_SLASHES)),
            $this->equalTo(0),
            $this->equalTo(1));

      $updateSuccess = $mockAgent->update([
            'id'                          => $agent->getID(),
            'plugin_flyvemdm_fleets_id'   => $fleet->getID()
      ]);
      $topic = $agent->getTopic();
      $this->assertTrue($updateSuccess, "Failed to update the agent");
   }

   /**
    * @depends testChangeFleet
    */
   public function testPurgeEnroledAgent() {
      // Create invitation for an enroled agent to be purged
      $name = 'topurge@localhost.local';
      $invitation = new PluginFlyvemdmInvitation();
      $this->assertGreaterThan(0, $invitation->add([
            'entities_id'  => $_SESSION['glpiactive_entity'],
            '_useremails'  => $name,
      ]), 'Could not create the enroled agent to purge');

      // Switch to the guest user
      $_REQUEST['user_token'] = User::getToken($invitation->getField('users_id'), 'api_token');
      Session::destroy();
      $this->assertTrue(self::login('', '', false));
      unset($_REQUEST['user_token']);

      // Enroll the agent
      $agent = new PluginFlyvemdmAgent();
      $serial = $this->getUniqueString();
      $agentId = $agent->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => $name,
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => $serial,
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);
      $this->assertGreaterThan(0, $agentId, "Could not create an agent to enroll then purge");

      // Get enrolment data to enable the agent's MQTT account
      $agent = new PluginFlyvemdmAgent();
      $this->assertTrue($agent->getFromDB($agentId));

      // Switch back to registered user
      Session::destroy();
      //$this->assertTrue(self::login('registereduser@localhost.local', 'password'));
      $this->assertTrue(self::login('glpi', 'glpi', true));

      $computerId = $agent->getField('computers_id');
      $mqttUser = new PluginFlyvemdmMqttuser();
      $this->assertTrue($mqttUser->getByUser($serial), "mqtt user has not been created");

      $this->assertTrue($agent->delete(['id' => $agentId], 1));

      $this->assertFalse($mqttUser->getByUser($serial));
      $computer = new Computer();
      $this->assertFalse($computer->getFromDB($computerId));
   }

   public function testPurgeAgent() {
      // Create invitation for an enroled agent to be purged
      $name = 'topurgebeforeenrolment@localhost.local';
      $invitation = new PluginFlyvemdmInvitation();
      $this->assertGreaterThan(0, $invitation->add([
            'entities_id'  => $_SESSION['glpiactive_entity'],
            '_useremails'  => $name,
      ]), 'Could not create the enroled agent to purge');

      // Switch to the guest user
      $_REQUEST['user_token'] = User::getToken($invitation->getField('users_id'), 'api_token');
      Session::destroy();
      $this->assertTrue(self::login('', '', false));
      unset($_REQUEST['user_token']);

      // Enroll the agent
      $agent = new PluginFlyvemdmAgent();
      $agentId = $agent->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => $name,
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => $this->getUniqueString(),
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);
      $this->assertGreaterThan(0, $agentId, json_encode($_SESSION['MESSAGE_AFTER_REDIRECT']));

      // Get enrolment data to enable the agent's MQTT account
      $agent = new PluginFlyvemdmAgent();
      $this->assertTrue($agent->getFromDB($agentId));

      // Get the userId of the owner of the device
      $computer = new Computer();
      $computerId = $computer->getID();
      $userId = $computer->getField('users_id');

      // Switch back to registered user
      Session::destroy();
      $this->assertTrue(self::login('glpi', 'glpi', true));
      //$this->assertTrue(self::login('registereduser@localhost.local', 'password'));

      // Delete shall succeed
      $this->assertTrue($agent->delete(['id' => $agentId]));

      // Test the agent user is deleted
      $agentUser = new User();
      $this->assertFalse($agentUser->getFromDB($agent->getField('users_id')));

      // Test the owner user is deleted
      $user = new User();
      $this->assertFalse($user->getFromDB($userId));
   }

   /**
    * These agents are invalid and shall not be added to the database
    */
   public function failingAgentProvider() {
      return [
            [["_useremails" => 'invalidemail_locahost.local']], // Invalid email address
            [['name'        => "valid@localhost.local"]], // No email address in _useremails
      ];
   }

   /**
    * @dataProvider failingAgentProvider
    */
   public function testFailingAddAgent($input) {
      $input['entities_id'] = $_SESSION['glpiactive_entity'];
      $agent = new PluginFlyvemdmAgent();
      $agentId = $agent->add($input);
      $this->assertFalse($agentId);
   }

   /**
    * @depends testEnrollAgent
    */
   public function testPingRequest($agent) {
      $mockAgent = $this->getMockForItemtype(PluginFlyvemdmAgent::class, ['notify']);

      $mockAgent->expects($this->once())
      ->method('notify')
      ->with(
            $this->equalTo($agent->getTopic() . "/Command/Ping"),
            $this->equalTo(json_encode(['query' => 'Ping'], JSON_UNESCAPED_SLASHES)),
            $this->equalTo(0),
            $this->equalTo(0));

      $mockAgent->update([
            'id'           => $agent->getID(),
            '_ping'        => ""
      ]);
   }

   /**
    * @depends testEnrollAgent
    */
   public function testGeolocateRequest($agent) {
      $mockAgent = $this->getMockForItemtype(PluginFlyvemdmAgent::class, ['notify']);

      $mockAgent->expects($this->once())
      ->method('notify')
      ->with(
            $this->equalTo($agent->getTopic() . "/Command/Geolocate"),
            $this->equalTo(json_encode(['query' => 'Geolocate'], JSON_UNESCAPED_SLASHES)),
            $this->equalTo(0),
            $this->equalTo(0));

      $mockAgent->update([
            'id'           => $agent->getID(),
            '_geolocate'   => ""
      ]);
   }

   /**
    * @depends testEnrollAgent
    */
   public function testInventoryRequest($agent) {
      $mockAgent = $this->getMockForItemtype(PluginFlyvemdmAgent::class, ['notify']);

      $mockAgent->expects($this->once())
      ->method('notify')
      ->with(
            $this->equalTo($agent->getTopic() . "/Command/Inventory"),
            $this->equalTo(json_encode(['query' => 'Inventory'], JSON_UNESCAPED_SLASHES)),
            $this->equalTo(0),
            $this->equalTo(0));

      $mockAgent->update([
            'id' => $agent->getID(),
            '_inventory' => ""
      ]);
   }

   /**
    * @depends testEnrollAgent
    * @param PluginFlyvemdmAgent $agent
    */
   public function testLockStatusUnset(PluginFlyvemdmAgent $agent) {
      // sync agent's state in memory against DB
      $agent->getFromDB($agent->getID());
      $this->assertEquals(0, $agent->getField('lock'));
   }

   /**
    * @depends testEnrollAgent
    * @testLockStatusUnset
    * @param PluginFlyvemdmAgent $agent
    */
   public function testLockRequest($agent) {
      $mockAgent = $this->getMockForItemtype(PluginFlyvemdmAgent::class, ['notify']);

      $mockAgent->expects($this->once())
      ->method('notify')
      ->with(
            $this->equalTo($agent->getTopic() . "/Command/Lock"),
            $this->equalTo(json_encode(['lock' => 'now'], JSON_UNESCAPED_SLASHES)),
            $this->equalTo(0),
            $this->equalTo(1));

      $mockAgent->update([
            'id'        => $agent->getID(),
            'lock'      => "1"
      ]);
   }

   /**
    * @depends testEnrollAgent
    * @depends testLockRequest
    */
   public function testLockStateSaved(PluginFlyvemdmAgent $agent) {
      // sync agent's state in memory against DB
      $agent->getFromDB($agent->getID());
      $this->assertEquals(1, $agent->getField('lock'));
   }

   /**
    * Run wipe tests only after lock tests
    *
    * @depends testEnrollAgent
    * @depends testLockStateSaved
    * @param PluginFlyvemdmAgent $agent
    */
   public function testWipeStatusUnset(PluginFlyvemdmAgent $agent) {
      // sync agent's state in memory against DB
      $agent->getFromDB($agent->getID());
      $this->assertEquals(0, $agent->getField('wipe'));
   }

   /**
    * @depends testEnrollAgent
    * @depends testWipeStatusUnset
    */
   public function testWipeRequest($agent) {
      $mockAgent = $this->getMockForItemtype(PluginFlyvemdmAgent::class, ['notify']);

      $mockAgent->expects($this->once())
      ->method('notify')
      ->with(
            $this->equalTo($agent->getTopic() . "/Command/Wipe"),
            $this->equalTo(json_encode(['wipe' => 'now'], JSON_UNESCAPED_SLASHES)),
            $this->equalTo(0),
            $this->equalTo(1));

      $mockAgent->update([
            'id'        => $agent->getID(),
            'wipe'      => "1"
      ]);
   }

   /**
    * @depends testEnrollAgent
    * @depends testWipeRequest
    */
   public function testWipeStateSaved(PluginFlyvemdmAgent $agent) {
      // sync agent's state in memory against DB
      $agent->getFromDB($agent->getID());
      $this->assertEquals(1, $agent->getField('wipe'));
   }

   /**
    * Test wipe overrides lock
    * @depends testEnrollAgent
    * @depends testWipeStateSaved
    * @depends testLockStateSaved
    */
   public function testWipeOverridesLockResetLock(PluginFlyvemdmAgent $agent) {
      $this->assertTrue($agent->update([
            'id'     => $agent->getID(),
            'lock'   => '0'
      ]));
      return $agent;
   }

   /**
    * @depends testWipeOverridesLockResetLock
    * @param PluginFlyvemdmAgent $agent
    */
   public function testWipeOverridesLockEnableWipe(PluginFlyvemdmAgent $agent) {
      $mockAgent = $this->getMockForItemtype(PluginFlyvemdmAgent::class, ['notify']);

      $mockAgent->expects($this->never())
      ->method('notify');

      $mockAgent->update([
            'id'        => $agent->getID(),
            'lock'      => "1"
      ]);
   }

   /**
    * @depends testWipeOverridesLockResetLock
    * @depends testWipeOverridesLockEnableWipe
    * @param PluginFlyvemdmAgent $agent
    */
   public function testWipeOverridesLockFinalState(PluginFlyvemdmAgent $agent) {
      // Reload agent to sync with DB
      $agent->getFromDB($agent->getID());
      $this->assertTrue($agent->getField('wipe') == '1' && $agent->getField('lock') == '1');
   }
}
