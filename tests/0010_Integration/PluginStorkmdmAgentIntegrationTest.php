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

use sskaje\mqtt\MQTT;

class PluginStorkmdmAgentIntegrationTest extends RegisteredUserTestCase {

   /**
    * Create an invitation for enrollment tests
    */
   public function testInvitationCreation() {
      self::$fixture['guestEmail'] = 'guestuser0001@localhost.local';

      $invitation = new PluginStorkmdmInvitation();
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
      $agentId = $agent ->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => $guestEmail,
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe'
      ]);
      $this->assertFalse($agent->isNewItem(), $_SESSION['MESSAGE_AFTER_REDIRECT']);

      return $agent;
   }

   /**
    * @depends testInvitationCreation
    * @depends testEnrollAgent
    */
   public function testInvitationUpdate(PluginStorkmdmInvitation $invitation) {
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
      $_REQUEST['user_token'] = User::getPersonalToken($invitation->getField('users_id'));
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
      $fleet = new PluginStorkmdmFleet();
      $fleet->add([
            'entities_id'  => $_SESSION['glpiactive_entity'],
            'name'         => 'fleet A'
      ]);
      $this->assertFalse($fleet->isNewItem(), "Could not create a fixture fleet");

      // Prepare subscriber
      $mqttSubscriber = new MqttClientHandler();
      $publishedMessage = null;

      $updateSuccess = null;

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent, &$fleet, &$updateSuccess) {
         $updateSuccess = $agent->update([
               'id'                          => $agent->getID(),
               'plugin_storkmdm_fleets_id'   => $fleet->getID()
         ]);
      };

      // Callback each time the mqtt broker sends a pingresp
      $callback = function () use (&$publishedMessage, &$mqttSubscriber) {
         $publishedMessage = $mqttSubscriber->getPublishedMessage();
      };

      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($callback);
      $topic = $agent->getTopic();
      $mqttSubscriber->subscribe("$topic/Command/Subscribe");
      $this->assertTrue($updateSuccess, "Failed to update the agent");
      $this->assertInstanceOf('\sskaje\mqtt\Message\PUBLISH', $publishedMessage);

      return $publishedMessage;
   }

   /**
    * @depends testChangeFleet
    */
   public function testchangeFleetMessageIsValid($publishedMessage)
   {
      $json = $publishedMessage->getMessage();
      $this->assertJson($json);

      $array = json_decode($json, true);
      $this->assertArrayHasKey('subscribe', $array);
   }

   /**
    * @depends testChangeFleet
    */
   public function testPurgeEnroledAgent() {
      // Create invitation for an enroled agent to be purged
      $name = 'topurge@localhost.local';
      $invitation = new PluginStorkmdmInvitation();
      $this->assertGreaterThan(0, $invitation->add([
            'entities_id'  => $_SESSION['glpiactive_entity'],
            '_useremails'  => $name,
      ]), 'Could not create the enroled agent to purge');

      // Switch to the guest user
      $_REQUEST['user_token'] = User::getPersonalToken($invitation->getField('users_id'));
      Session::destroy();
      $this->assertTrue(self::login('', '', false));
      unset($_REQUEST['user_token']);

      // Enroll the agent
      $agent = new PluginStorkmdmAgent();
      $agentId = $agent->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => $name,
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'UIOP',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe'
      ]);
      $this->assertGreaterThan(0, $agentId, "Could not create an agent to enroll then purge");

      // Get enrolment data to enable the agent's MQTT account
      $agent = new PluginStorkmdmAgent();
      $this->assertTrue($agent->getFromDB($agentId));

      // Switch back to registered user
      Session::destroy();
      $this->assertTrue(self::login('registereduser@localhost.local', 'password'));

      $computerId = $agent->getField('computers_id');
      $mqttUser = new PluginStorkmdmMqttuser();
      $this->assertTrue($mqttUser->getByUser('UIOP'), "mqtt user has not been created");

      $this->assertTrue($agent->delete(['id' => $agentId], 1));

      $this->assertFalse($mqttUser->getByUser('UIOP'));
      $computer = new Computer();
      $this->assertFalse($computer->getFromDB($computerId));
   }

   public function testPurgeAgent() {
      // Create invitation for an enroled agent to be purged
      $name = 'topurgebeforeenrolment@localhost.local';
      $invitation = new PluginStorkmdmInvitation();
      $this->assertGreaterThan(0, $invitation->add([
            'entities_id'  => $_SESSION['glpiactive_entity'],
            '_useremails'  => $name,
      ]), 'Could not create the enroled agent to purge');

      // Switch to the guest user
      $_REQUEST['user_token'] = User::getPersonalToken($invitation->getField('users_id'));
      Session::destroy();
      $this->assertTrue(self::login('', '', false));
      unset($_REQUEST['user_token']);

      // Enroll the agent
      $agent = new PluginStorkmdmAgent();
      $agentId = $agent->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => $name,
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'UIOP',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe'
      ]);
      $this->assertGreaterThan(0, $agentId, "Could not create an agent to enroll then purge");

      // Get enrolment data to enable the agent's MQTT account
      $agent = new PluginStorkmdmAgent();
      $this->assertTrue($agent->getFromDB($agentId));

      // Get the userId of the owner of the device
      $computer = new Computer();
      $computerId = $computer->getID();
      $userId = $computer->getField('users_id');

      // Switch back to registered user
      Session::destroy();
      $this->assertTrue(self::login('registereduser@localhost.local', 'password'));

      // Delete shall succeed
      $this->assertTrue($agent->delete(['id' => $agentId]));

      return $userId;
   }

   /**
    * @depends testPurgeAgent
    */
   public function testUserIsDeleted($userId) {
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
      $agent = new PluginStorkmdmAgent();
      $agentId = $agent->add($input);
      $this->assertFalse($agentId);
   }

   /**
    * @depends testEnrollAgent
    */
   public function testPingRequest($agent) {
      // Prepare subscriber
      $mqttSubscriber = new MqttClientHandler();
      $publishedMessage = null;

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent) {
         $agent->update([
               'id'           => $agent->getID(),
               '_ping'        => ""
         ]);
      };

      // Callback each time the mqtt broker sends a pingresp
      $callback = function () use (&$publishedMessage, &$mqttSubscriber) {
         $publishedMessage = $mqttSubscriber->getPublishedMessage();
      };
      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($callback);
      $topic = $agent->getTopic();
      $mqttSubscriber->subscribe("$topic/Command/Ping");
      $this->assertInstanceOf('\sskaje\mqtt\Message\PUBLISH', $publishedMessage);
      return $publishedMessage;
   }

   /**
    * @depends testPingRequest
    */
   public function testPingRequestValid($publishedMessage) {
      $this->assertEquals('{"query":"Ping"}', $publishedMessage->getMessage());
   }

   /**
    * @depends testEnrollAgent
    */
   public function testGeolocateRequest($agent) {
      // Prepare subscriber
      $mqttSubscriber = new MqttClientHandler();
      $publishedMessage = null;

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent) {
         $agent->update([
               'id'           => $agent->getID(),
               '_geolocate'   => ""
         ]);
      };

      // Callback each time the mqtt broker sends a pingresp
      $callback = function () use (&$publishedMessage, &$mqttSubscriber) {
         $publishedMessage = $mqttSubscriber->getPublishedMessage();
      };
      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($callback);
      $topic = $agent->getTopic();
      $mqttSubscriber->subscribe("$topic/Command/Geolocate");
      $this->assertInstanceOf('\sskaje\mqtt\Message\PUBLISH', $publishedMessage);
      return $publishedMessage;
   }

   /**
    * @depends testGeolocateRequest
    */
   public function testGeolocateRequestValid($publishedMessage) {
      $this->assertEquals('{"query":"Geolocate"}', $publishedMessage->getMessage());
   }

   /**
    * @depends testEnrollAgent
    */
   public function testInventoryRequest($agent) {
      // Prepare subscriber
      $mqttSubscriber = new MqttClientHandler();
      $publishedMessage = null;

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent) {
         $agent->update([
               'id' => $agent->getID(),
               '_inventory' => ""
         ]);
      };

      // Callback each time the mqtt broker sends a pingresp
      $callback = function () use (&$publishedMessage, &$mqttSubscriber) {
         $publishedMessage = $mqttSubscriber->getPublishedMessage();
      };
      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($callback);
      $topic = $agent->getTopic();
      $mqttSubscriber->subscribe("$topic/Command/Inventory");
      $this->assertInstanceOf('\sskaje\mqtt\Message\PUBLISH', $publishedMessage);
      return $publishedMessage;
   }

   /**
    * @depends testInventoryRequest
    */
   public function testInventoryRequestValid($publishedMessage) {
      $this->assertEquals('{"query":"Inventory"}', $publishedMessage->getMessage());
   }

   /**
    * @depends testEnrollAgent
    * @param PluginStorkmdmAgent $agent
    */
   public function testLockStatusUnset(PluginStorkmdmAgent $agent) {
      // sync agent's state in memory against DB
      $agent->getFromDB($agent->getID());
      $this->assertEquals(0, $agent->getField('lock'));
   }

   /**
    * @depends testEnrollAgent
    * @testLockStatusUnset
    * @param PluginStorkmdmAgent $agent
    */
   public function testLockRequest($agent) {
      // Prepare subscriber
      $mqttSubscriber = new MqttClientHandler();
      $publishedMessage = null;

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent) {
         $agent->update([
               'id'        => $agent->getID(),
               'lock'      => "1"
         ]);
      };

      // Callback each time the mqtt broker sends a pingresp
      $callback = function () use (&$publishedMessage, &$mqttSubscriber) {
         $publishedMessage = $mqttSubscriber->getPublishedMessage();
      };
      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($callback);
      $topic = $agent->getTopic();
      $mqttSubscriber->subscribe("$topic/Command/Lock");
      $this->assertInstanceOf('\sskaje\mqtt\Message\PUBLISH', $publishedMessage);
      return $publishedMessage;
   }

   /**
    * @depends testLockRequest
    */
   public function testLockRequestValid($publishedMessage) {
      $this->assertEquals('{"lock":"now"}', $publishedMessage->getMessage());
   }

   /**
    * @depends testEnrollAgent
    * @depends testLockRequest
    */
   public function testLockStateSaved(PluginStorkmdmAgent $agent) {
      // sync agent's state in memory against DB
      $agent->getFromDB($agent->getID());
      $this->assertEquals(1, $agent->getField('lock'));
   }

   /**
    * Run wipe tests only after lock tests
    *
    * @depends testEnrollAgent
    * @depends testLockStateSaved
    * @param PluginStorkmdmAgent $agent
    */
   public function testWipeStatusUnset(PluginStorkmdmAgent $agent) {
      // sync agent's state in memory against DB
      $agent->getFromDB($agent->getID());
      $this->assertEquals(0, $agent->getField('wipe'));
   }

   /**
    * @depends testEnrollAgent
    * @depends testWipeStatusUnset
    */
   public function testWipeRequest($agent) {
      // Prepare subscriber
      $mqttSubscriber = new MqttClientHandler();
      $publishedMessage = null;

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent) {
         $agent->update([
               'id'        => $agent->getID(),
               'wipe'      => "1"
         ]);
      };

      // Callback each time the mqtt broker sends a pingresp
      $callback = function () use (&$publishedMessage, &$mqttSubscriber) {
         $publishedMessage = $mqttSubscriber->getPublishedMessage();
      };
      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($callback);
      $topic = $agent->getTopic();
      $mqttSubscriber->subscribe("$topic/Command/Wipe");
      $this->assertInstanceOf('\sskaje\mqtt\Message\PUBLISH', $publishedMessage);
      return $publishedMessage;
   }

   /**
    * @depends testWipeRequest
    */
   public function testWipeRequestValid($publishedMessage) {
      $this->assertEquals('{"wipe":"now"}', $publishedMessage->getMessage());
   }

   /**
    * @depends testEnrollAgent
    * @depends testWipeRequest
    */
   public function testWipeStateSaved(PluginStorkmdmAgent $agent) {
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
   public function testWipeOverridesLockResetLock(PluginStorkmdmAgent $agent) {
      $this->assertTrue($agent->update([
            'id'     => $agent->getID(),
            'lock'   => '0'
      ]));
      return $agent;
   }

   /**
    * @depends testWipeOverridesLockResetLock
    * @param PluginStorkmdmAgent $agent
    */
   public function testWipeOverridesLockEnableWipe(PluginStorkmdmAgent $agent) {
      // Prepare subscriber
      $mqttSubscriber = new MqttClientHandler();
      $publishedMessage = null;

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent) {
         $agent->update([
               'id'        => $agent->getID(),
               'lock'      => "1"
         ]);
      };

      // Callback each time the mqtt broker sends a pingresp
      $callback = function () use (&$publishedMessage, &$mqttSubscriber) {
         $publishedMessage = $mqttSubscriber->getPublishedMessage();
      };
      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($callback);
      $topic = $agent->getTopic();
      $mqttSubscriber->subscribe("$topic/Command/Lock");
      $this->assertNull($publishedMessage);
      return $publishedMessage;
   }

   /**
    * @depends testWipeOverridesLockResetLock
    * @depends testWipeOverridesLockEnableWipe
    * @param PluginStorkmdmAgent $agent
    */
   public function testWipeOverridesLockFinalState(PluginStorkmdmAgent $agent) {
      // Reload agent to sync with DB
      $agent->getFromDB($agent->getID());
      $this->assertTrue($agent->getField('wipe') == '1' && $agent->getField('lock') == '1');
   }

   // =====================================================

   public function testRegisteredUserAddsOwnDevice() {
      $invitation = new PluginStorkmdmInvitation();
      $invitationId = $invitation->add([
         'entities_id'  => $_SESSION['glpiactive_entity'],
         '_useremails'  => self::$fixture['registeredUserEmail'],
      ]);
      $this->assertGreaterThan(0, $invitationId);

      return $invitation;
   }

   /**
    * @depends testRegisteredUserAddsOwnDevice
    * @param array $invitation
    */
   public function testRegisteredUserHasGuestProfile($invitation) {
      $config = Config::getConfigurationValues("storkmdm", array('guest_profiles_id'));
      $user = new User();
      $user->getFromDBbyEmail(self::$fixture['registeredUserEmail'], '');
      $profile = new Profile();
      $profile->getFromDB($config['guest_profiles_id']);
      $profile_User = new Profile_User();
      $profile_UserId = $profile_User->getFromDBForItems($user, $profile);
      $this->assertTrue($profile_UserId);

      return $invitation;
   }

   /**
    * @depends testRegisteredUserHasGuestProfile
    */
   public function testRegisteredUserMayHaveAnOtherDevice() {
      // Simply replay test for first device
      $invitation = new PluginStorkmdmInvitation();
      $invitationId = $invitation->add([
         'entities_id'  => $_SESSION['glpiactive_entity'],
         '_useremails'  => self::$fixture['registeredUserEmail'],
      ]);
      $this->assertGreaterThan(0, $invitationId);

      return $invitation;
   }

   /**
    * @depends testRegisteredUserMayHaveAnOtherDevice
    */
   public function testRegisteredUserEnrollsFirstDevice($invitation) {
      // Switch to guest profile
      $config = Config::getConfigurationValues("storkmdm", array('registered_profiles_id', 'guest_profiles_id'));
      $guestProfileId  = $config['guest_profiles_id'];
      $registeredProfileId = $config['registered_profiles_id'];

      $this->assertTrue(isset($_SESSION['glpiprofiles'][$guestProfileId]));
      if (isset($_SESSION['glpiprofiles'][$guestProfileId])) {
         Session::changeProfile($guestProfileId);
         $agent = new PluginStorkmdmAgent();
         $agentId = $agent->add([
               'entities_id'        => $_SESSION['glpiactive_entity'],
               '_email'             => self::$fixture['registeredUserEmail'],
               '_invitation_token'  => $invitation->getField('invitation_token'),
               '_serial'            => 'GHJK',
               'csr'                => '',
               'firstname'          => 'Registered',
               'lastname'           => 'user'
         ]);
      }
      $this->assertGreaterThan(0, $agentId, $_SESSION['MESSAGE_AFTER_REDIRECT']);
      $agent->getFromDB($agentId);

      return $agent;
   }

   /**
    * @depends testRegisteredUserAddsOwnDevice
    * @depends testRegisteredUserMayHaveAnOtherDevice
    * @param unknown $invitation
    */
   public function testRegisteredUserEnrollsSecondDevice($invitation) {
      // Switch to guest profile
      $config = Config::getConfigurationValues("storkmdm", array('registered_profiles_id', 'guest_profiles_id'));
      $guestProfileId  = $config['guest_profiles_id'];
      $registeredProfileId = $config['registered_profiles_id'];

      $this->assertTrue(isset($_SESSION['glpiprofiles'][$guestProfileId]));
      if (isset($_SESSION['glpiprofiles'][$guestProfileId])) {
         Session::changeProfile($guestProfileId);
         $agent = new PluginStorkmdmAgent();
         $agentId = $agent->add([
               'entities_id'        => $_SESSION['glpiactive_entity'],
               '_email'             => self::$fixture['registeredUserEmail'],
               '_invitation_token'  => $invitation->getField('invitation_token'),
               '_serial'            => 'WXCV',
               'csr'                => '',
               'firstname'          => 'Registered',
               'lastname'           => 'user'
         ]);
      }
      $this->assertGreaterThan(0, $agentId, $_SESSION['MESSAGE_AFTER_REDIRECT']);
      $agent->getFromDB($agentId);

      return $agent;
   }

   /**
    * @depends testRegisteredUserEnrollsFirstDevice
    * @depends testRegisteredUserEnrollsSecondDevice
    * @param unknown $agent
    */
   public function testRegisteredUserDeletesOneOfHisDevices($agent) {
      $this->assertTrue($agent->delete(['id' => $agent->getID()]));
   }

   /**
    * @depends testRegisteredUserDeletesOneOfHisDevices
    */
   public function testRegisteredUserStillHaveGuestProfile() {
      $config = Config::getConfigurationValues("storkmdm", array('guest_profiles_id'));
      $user = new User();
      $user->getFromDBbyEmail(self::$fixture['registeredUserEmail'], '');
      $profile = new Profile();
      $profile->getFromDB($config['guest_profiles_id']);
      $profile_User = new Profile_User();
      $profile_UserId = $profile_User->getFromDBForItems($user, $profile);
      $this->assertTrue($profile_UserId);
   }

   /**
    * @depends testRegisteredUserEnrollsSecondDevice
    * @depends testRegisteredUserStillHaveGuestProfile
    * @param unknown $agent
    */
   public function testRegisteredUserDeletesHisLastDevice($agent) {
      $this->assertTrue($agent->delete(['id' => $agent->getID()]));
   }

   /**
    * @depends testRegisteredUserDeletesHisLastDevice
    */
   public function testRegisteredUserNoLongerHasGuestProfile() {
      $config = Config::getConfigurationValues("storkmdm", array('guest_profiles_id'));
      $user = new User();
      $user->getFromDBbyEmail(self::$fixture['registeredUserEmail'], '');
      $profile = new Profile();
      $profile->getFromDB($config['guest_profiles_id']);
      $profile_User = new Profile_User();
      $profile_UserId = $profile_User->getFromDBForItems($user, $profile);
      $this->assertFalse($profile_UserId);
   }

}
