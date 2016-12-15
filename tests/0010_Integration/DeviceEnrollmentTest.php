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

class DeviceEnrollmentTest extends GuestUserTestCase {

   public function testEnrollAgentWithUnknownEmail() {
      $invitationLog = new PluginStorkmdmInvitationlog();
      $rows = $invitationLog->find("1");
      $logCount = count($rows);

      $agent = new PluginStorkmdmAgent();
      $invitation = self::$fixture['invitation'];
      $agentId = $agent ->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => 'nonexistent@localhost.local',
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe'
      ]);
      $this->assertFalse($agentId);

      $rows = $invitationLog->find("1");
      $this->assertEquals($logCount + 1, count($rows));
   }

   public function testEnrollAgentWithBadToken() {
      $invitationLog = new PluginStorkmdmInvitationlog();
      $rows = $invitationLog->find("1");
      $logCount = count($rows);

      $agent = new PluginStorkmdmAgent();
      $agentId = $agent ->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => self::$fixture['guestEmail'],
            '_invitation_token'  => 'bad token',
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe'
      ]);
      $this->assertFalse($agentId);

      $rows = $invitationLog->find("1");
      $this->assertEquals($logCount, count($rows));
   }

   public function testEnrollAgentWithEmptySerial() {
      $invitationLog = new PluginStorkmdmInvitationlog();
      $rows = $invitationLog->find("1");
      $logCount = count($rows);

      $invitation = self::$fixture['invitation'];
      $agent = new PluginStorkmdmAgent();
      $agentId = $agent ->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => self::$fixture['guestEmail'],
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => '',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe'
      ]);
      $this->assertFalse($agentId);

      $rows = $invitationLog->find("1");
      $this->assertEquals($logCount + 1, count($rows));
   }

   /**
    * @depends testEnrollAgentWithUnknownEmail
    * @depends testEnrollAgentWithBadToken
    * @depends testEnrollAgentWithEmptySerial
    */
   public function testEnrollAgent() {
      $invitationLog = new PluginStorkmdmInvitationlog();
      $rows = $invitationLog->find("1");
      $logCount = count($rows);

      self::$fixture['serial'] = 'AZERTY';
      $invitation = self::$fixture['invitation'];
      $agent = new PluginStorkmdmAgent();

      // Prepare subscriber
      $mqttSubscriber = MqttHandlerForTests::getInstance();
      $publishedMessages = null;
      $agentId = null;

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent, &$agentId, &$invitation) {
         $agentId = $agent->add([
               'entities_id'        => $_SESSION['glpiactive_entity'],
               '_email'             => self::$fixture['guestEmail'],
               '_invitation_token'  => $invitation->getField('invitation_token'),
               '_serial'            => self::$fixture['serial'],
               'csr'                => '',
               'firstname'          => 'John',
               'lastname'           => 'Doe'
         ]);
      };

      // Callback each time the mqtt broker sends a pingresp
      $pingCallback = function () use (&$publishedMessages, &$mqttSubscriber) {
         if (count($mqttSubscriber->getPublishedMessages()) == count(PluginStorkmdmAgent::getTopicsToCleanup())) {
            $mqttSubscriber->stopMqttClient();
            $publishedMessages = $mqttSubscriber->getPublishedMessages();
         }
      };

      $topic = "/" . $_SESSION['glpiactive_entity'] . "/agent/" . self::$fixture['serial'];
      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($pingCallback);
      $mqttSubscriber->subscribe("$topic/#");

      $this->assertFalse($agent->isNewItem(), $_SESSION['MESSAGE_AFTER_REDIRECT']);

      // Prepare expected topics
      $expectedTopics = array();
      PluginStorkmdmAgent::getTopicsToCleanup();
      foreach (PluginStorkmdmAgent::getTopicsToCleanup() as $expectedTopic) {
         $expectedTopics["$topic/$expectedTopic"] = '';
      }

      // Test topics cleanup
      $this->assertCount(count(PluginStorkmdmAgent::getTopicsToCleanup()), $publishedMessages);
      foreach ($publishedMessages as $message) {
         $this->assertEquals('', $message->getMessage());
         $this->assertTrue(array_key_exists($message->getTopic(), $expectedTopics));
         unset($expectedTopics[$message->getTopic()]);
      }

      // Test there is no new entry in the invitation log
      $rows = $invitationLog->find("1");
      $this->assertEquals($logCount, count($rows));

      // Test the agent has been enrolled
      $this->assertEquals('enrolled', $agent->getField('enroll_status'));

      return $agent;
   }

   /**
    * @depends testEnrollAgent
    */
   public function testComputerHasSerialAfterEnrollment($agent) {
      // Is the computer's serial saved ?
      $computer = new Computer();
      $this->assertTrue($computer->getFromDB($agent->getField('computers_id')));
      $this->assertEquals(self::$fixture['serial'], $computer->getField('serial'));
   }

   /**
    * @depends testEnrollAgent
    */
   public function testComputerHasUser($agent) {
      $invitation = new PluginStorkmdmInvitation();
      $invitation = self::$fixture['invitation'];
      $computer = new Computer();
      $this->assertTrue($computer->getFromDB($agent->getField('computers_id')));
      $this->assertEquals($invitation->getField('users_id'), $computer->getField('users_id'));
   }

   /**
    * @depends testEnrollAgent
    */
   public function testGetEnrollData($agent) {
      // Get the agent from DB as gurst user
      // Triggers post_getFromDB()
      $agent->getFromDB($agent->getID());
      $this->assertTrue(isset($agent->fields['certificate']));
      $this->assertTrue(isset($agent->fields['mqttpasswd']));
      $this->assertTrue(isset($agent->fields['topic']));
      $this->assertTrue(isset($agent->fields['broker']));
      $this->assertTrue(isset($agent->fields['port']));
      $this->assertTrue(isset($agent->fields['tls']));
      return $agent;
   }

   /**
    * @depends testGetEnrollData
    */
   public function testAgentHasMQTTUser($agent) {
      // Is the mqtt user created and enabled ?
      $serial = self::$fixture['serial'];
      $mqttUser = new PluginStorkmdmMqttuser();
      $this->assertTrue($mqttUser->getFromDBByQuery("WHERE `user`='$serial'"));
      return $mqttUser;
   }

   /**
    * @depends testAgentHasMQTTUser
    */
   public function testAgentMqttUserIsEnabled($mqttUser) {
      $this->assertEquals($mqttUser->getField('enabled'), '1');
   }

   /**
    * @depends testAgentHasMQTTUser
    */
   public function testAgentMqttUserHasACL(PluginStorkmdmMqttuser $mqttUser) {
      $mqttUserId = $mqttUser->getID();

      // Are the MQTT ACLs set ?
      $mqtACLs = $mqttUser->getACLs();
      $this->assertEquals(4, count($mqtACLs));
      return $mqtACLs;
   }

   /**
    * @depends testAgentMqttUserHasACL
    * @depends testGetEnrollData
    */
   public function testAgentHasMQTTRights($mqttACLs, $agent) {
      $serial = self::$fixture['serial'];
      $validated = 0;

      foreach ($mqttACLs as $acl) {
         if (preg_match("~/agent/$serial/Command/#$~", $acl->getField('topic')) == 1) {
            $this->assertEquals(PluginStorkmdmMqttacl::MQTTACL_READ, $acl->getField('access_level'));
            $validated++;
         } else if (preg_match("~/agent/$serial/Status/#$~", $acl->getField('topic')) == 1) {
            $this->assertEquals(PluginStorkmdmMqttacl::MQTTACL_WRITE, $acl->getField('access_level'));
            $validated++;
         } else if (preg_match("~^/FlyvemdmManifest/#$~", $acl->getField('topic')) == 1) {
            $this->assertEquals(PluginStorkmdmMqttacl::MQTTACL_READ, $acl->getField('access_level'));
            $validated++;
         } else if (preg_match("~/agent/$serial/FlyvemdmManifest/#$~", $acl->getField('topic')) == 1) {
            $this->assertEquals(PluginStorkmdmMqttacl::MQTTACL_WRITE, $acl->getField('access_level'));
            $validated++;
         }
      }

      // Ensure ACLs has been checked one time each
      $this->assertEquals(4, $validated);
   }

   /**
    * @depends testAgentHasMQTTRights
    */
   public function testInvitationExpirationAfterEnrollment() {
     $invitation = new PluginStorkmdmInvitation();
     $this->assertTrue($invitation->getFromDB(self::$fixture['invitation']->getID()));

      // Is the token expiry set ?
      $actual = new DateTime($invitation->getField('expiration_date'), new DateTimeZone('UTC'));
      $expected = new DateTime('now', new DateTimeZone('UTC'));
      $this->assertLessThanOrEqual($expected, $actual);
   }

   /**
    * @depends testInvitationExpirationAfterEnrollment
    */
   public function testEnrollAgentAgain() {
      self::$fixture['serial'] = 'AZERTY';
      $invitation = self::$fixture['invitation'];
      $agent = new PluginStorkmdmAgent();
      $agentId = $agent ->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => self::$fixture['guestEmail'],
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => self::$fixture['serial'],
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe'
      ]);
      $this->assertFalse($agentId);
      return array('agent' => $agent);
   }

}
