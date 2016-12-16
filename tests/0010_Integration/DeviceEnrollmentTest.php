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
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
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
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);
      $this->assertFalse($agentId);

      $rows = $invitationLog->find("1");
      $this->assertEquals($logCount, count($rows));
   }

   public function testEnrollAgentWithoutVersion() {
      $invitationLog = new PluginStorkmdmInvitationlog();
      $rows = $invitationLog->find("1");
      $logCount = count($rows);

      $invitation = self::$fixture['invitation'];
      $agent = new PluginStorkmdmAgent();
      $agentId = $agent ->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => self::$fixture['guestEmail'],
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
      ]);
      $this->assertFalse($agentId);

      $rows = $invitationLog->find("1");
      $this->assertEquals($logCount + 1, count($rows));
   }

   public function testEnrollAgentWithBadVersion() {
      $invitationLog = new PluginStorkmdmInvitationlog();
      $rows = $invitationLog->find("1");
      $logCount = count($rows);

      $invitation = self::$fixture['invitation'];
      $agent = new PluginStorkmdmAgent();
      $agentId = $agent ->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => self::$fixture['guestEmail'],
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
            'version'            => 'bad version',
      ]);
      $this->assertFalse($agentId);

      $rows = $invitationLog->find("1");
      $this->assertEquals($logCount + 1, count($rows));
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
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);
      $this->assertFalse($agentId);

      $rows = $invitationLog->find("1");
      $this->assertEquals($logCount + 1, count($rows));
   }

   /**
    * @depends testEnrollAgentWithUnknownEmail
    * @depends testEnrollAgentWithBadToken
    * @depends testEnrollAgentWithEmptySerial
    * @depends testEnrollAgentWithoutVersion
    * @depends testEnrollAgentWithBadVersion
    */
   public function testEnrollAgent() {
      // Count invitation log entries
      $invitationLog = new PluginStorkmdmInvitationlog();
      $rows = $invitationLog->find("1");
      $logCount = count($rows);

      self::$fixture['serial'] = 'AZERTY';
      $invitation = self::$fixture['invitation'];
      $agent = new PluginStorkmdmAgent();

      // Prepare subscriber
      $mqttSubscriber = MqttHandlerForTests::getInstance();
      $publishedMessages = array();
      $agentId = null;

      $agentId = $agent->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => self::$fixture['guestEmail'],
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => self::$fixture['serial'],
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);

      $this->assertFalse($agent->isNewItem(), $_SESSION['MESSAGE_AFTER_REDIRECT']);

      // Test there is no new entry in the invitation log
      $rows = $invitationLog->find("1");
      $this->assertEquals($logCount, count($rows));

      // Test the agent has been enrolled
      $this->assertEquals('enrolled', $agent->getField('enroll_status'));

      // Test the invitation status is updated
      $invitation->getFromDB($invitation->getID());
      $this->assertEquals('done', $invitation->getField('status'));

      // Test a computer is associated to the agent
      $computer = new Computer();
      $this->assertTrue($computer->getFromDB($agent->getField('computers_id')));

      // Test the serial is saved
      $this->assertEquals(self::$fixture['serial'], $computer->getField('serial'));

      // Test the user of the computer is the user of the invitation
      $this->assertEquals($invitation->getField('users_id'), $computer->getField('users_id'));

      return $agent;
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
      // test disabled for now
      //$this->assertTrue(isset($agent->fields['android_bugcollecctor_url']));
      //$this->assertTrue(isset($agent->fields['android_bugcollector_login']));
      //$this->assertTrue(isset($agent->fields['android_bugcollector_passwd']));
      //$this->assertTrue(isset($agent->fields['version']));

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
