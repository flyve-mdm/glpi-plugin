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

class DeviceUnenrollmentTest extends RegisteredUserTestCase {

   /**
    * Create an invitation for enrollment tests
    */
   public function testInitInvitationCreation() {
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
    * @depends testInitInvitationCreation
    */
   public function testInitEnrollAgent($invitation) {
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
    * @depends testInitEnrollAgent
    */
   public function testUnenrollAgent(PluginStorkmdmAgent $agent) {
      // Prepare subscriber
      $mqttSubscriber = MqttHandlerForTests::getInstance();
      $publishedMessage = null;
      $updateSuccess = null;

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent, &$updateSuccess) {
         $updateSuccess = $agent->update([
               'id'           => $agent->getID(),
               '_unenroll'    => '',
         ]);
      };

      // Callback each time the mqtt broker sends a pingresp
      $pingCallback = function () use (&$publishedMessage, &$mqttSubscriber) {
         if (count($mqttSubscriber->getPublishedMessages()) == 1) {
            $mqttSubscriber->stopMqttClient();
            $publishedMessage = $mqttSubscriber->getPublishedMessages();
            $publishedMessage = array_shift($publishedMessage);
         }
      };

      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($pingCallback);
      $topic = $agent->getTopic();
      $mqttSubscriber->subscribe("$topic/Command/Unenroll");
      $this->assertTrue($updateSuccess, "Failed to update the agent");

      return $publishedMessage;
   }

   /**
    * @depends testUnenrollAgent
    */
   public function testUnenrollMessage($publishedMessage) {
      $json = $publishedMessage->getMessage();
      $this->assertJson($json);

      $array = json_decode($json, true);
      $this->assertArrayHasKey('unenroll', $array);
   }

   /**
    * @depends testInitEnrollAgent
    * @depends testUnenrollMessage
    */
   public function testUnenrollAck(PluginStorkmdmAgent $agent) {
      // Prepare subscriber
      $mqttSubscriber = MqttHandlerForTests::getInstance();
      $publishedMessages = null;
      $deleteSuccess = null;

      $topic = $agent->getTopic();
      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$agent, &$deleteSuccess) {
         $deleteSuccess = $agent->delete(array('id' => $agent->getID()));
      };

      // Callback each time the mqtt broker sends a pingresp
      $pingCallback = function () use (&$publishedMessages, &$mqttSubscriber) {
         if (count($mqttSubscriber->getPublishedMessages()) == count(PluginStorkmdmAgent::getTopicsToCleanup())) {
            $mqttSubscriber->stopMqttClient();
            $publishedMessages = $mqttSubscriber->getPublishedMessages();
         }
      };

      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($pingCallback);
      $mqttSubscriber->subscribe("$topic/#");
      $this->assertTrue($deleteSuccess);

      // Prepare expected topics
      $expectedTopics = array();
      PluginStorkmdmAgent::getTopicsToCleanup();
      foreach (PluginStorkmdmAgent::getTopicsToCleanup() as $expectedTopic) {
         $expectedTopics["$topic/$expectedTopic"] = '';
      }
      
      // Assertions
      $this->assertCount(count(PluginStorkmdmAgent::getTopicsToCleanup()), $publishedMessages);
      foreach ($publishedMessages as $message) {
         $this->assertEquals('', $message->getMessage());
         $this->assertTrue(array_key_exists($message->getTopic(), $expectedTopics));
         unset($expectedTopics[$message->getTopic()]);
      }
   }
}