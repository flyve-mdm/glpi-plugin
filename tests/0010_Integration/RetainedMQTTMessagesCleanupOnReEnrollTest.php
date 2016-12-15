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

class RetainedMQTTMessagesCleanupOnReEnrollTest extends RegisteredUserTestCase {

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

      $agent = new PluginStorkmdmAgent();
      $agentId = $agent ->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => self::$fixture['guestEmail'],
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe'
      ]);
      $this->assertGreaterThan(0, $agentId, $_SESSION['MESSAGE_AFTER_REDIRECT']);

      return $agent;
   }

   /**
    * @depends testEnrollAgent
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
    * Create an invitation for enrollment tests
    *
    * @depends testWipeRequest
    */
   public function testSecondInvitationCreation() {
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
    * @depends testEnrollAgent
    * @depends testWipeRequest
    */
   public function testremoveComputer($agent) {
      $computerId = $agent->getField('computers_id');
      $computer = new Computer();
      $this->assertTrue($computer->delete(['id' => $computerId], 1));
   }

   /**
    * Enrolls an agent as guest user
    * @depends testSecondInvitationCreation
    * @depends testremoveComputer
    */
   public function testSecondEnrollAgent($invitation) {
      // Login as guest user
      $_REQUEST['user_token'] = User::getPersonalToken($invitation->getField('users_id'));
      Session::destroy();
      $this->assertTrue(self::login('', '', false));
      unset($_REQUEST['user_token']);

      $email = self::$fixture['guestEmail'];

      Config::setConfigurationValues('storkmdm', array('debug_enrolment' => '1'));

      // Prepare subscriber
      $mqttSubscriber = new MqttClientHandler();
      $mqttSubscriber->setAutoDisconect(false);

      $publishedMessages = array();

      $agent = new PluginStorkmdmAgent();
      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent, &$email, &$invitation) {
         $agentId = $agent->add([
               'entities_id'        => $_SESSION['glpiactive_entity'],
               '_email'             => $email,
               '_invitation_token'  => $invitation->getField('invitation_token'),
               '_serial'            => 'AZERTY',
               'csr'                => '',
               'firstname'          => 'John',
               'lastname'           => 'Doe'
         ]);
      };

      // Callback each time the mqtt broker publishes a message
      $getMessageCallback = function ($newMessage) use (&$publishedMessages, &$mqttSubscriber) {
         $publishedMessages[] = $newMessage;
      };

      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setGetMqttMessageCallback($getMessageCallback);

      $mqttSubscriber->subscribe("#");

      $this->assertGreaterThan(0, $agent->getID(), $_SESSION['MESSAGE_AFTER_REDIRECT']);

      // Search for a cleanup of wipe
      $messageFound = false;

      $regex = '#^' . $agent->getTopic() . '/([a-zA-Z-0-9/]+)$#';
      $topics = array_flip(PluginStorkmdmAgent::getTopicsToCleanup());
      foreach ($publishedMessages as $message) {
         if (preg_match($regex, $message->getTopic(), $matches)) {
            if (array_key_exists($matches[1], $topics)) {
               unset($topics[$matches[1]]);
            }
            $this->assertEmpty($message->getMessage(), $matches[0]);
         }
      }

      $this->assertCount(0, $topics);

      return $agent;
   }
}