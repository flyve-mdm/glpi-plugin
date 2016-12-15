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

class PluginStorkmdmFleet_PolicyIntegrationTest extends RegisteredUserTestCase {

   public function testInitAddFleet() {
      $fleet = new PluginStorkmdmFleet();
      $fleet->add([
            'entities_id'     => $_SESSION['glpiactive_entity'],
            'name'            => 'a fleet'
      ]);
      $this->assertFalse($fleet->isNewItem());

      return $fleet;
   }

   public function testGetGuestEmail() {
      return 'guestuser0001@localhost.local';
   }

   /**
    * Create an invitation for enrollment tests
    * @depends testGetGuestEmail
    */
   public function testInitInvitationCreation($guestEmail) {
      $invitation = new PluginStorkmdmInvitation();
      $invitationId = $invitation->add([
         'entities_id'  => $_SESSION['glpiactive_entity'],
         '_useremails'  => $guestEmail,
      ]);
      $this->assertFalse($invitation->isNewItem());

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
            '_email'             => 'guestuser0001@localhost.local',
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
    * @depends testInitAddFleet
    */
   public function testApplyPolicy($fleet) {
      $fleet_Policy = new PluginStorkmdmFleet_Policy();
      $policy = new PluginStorkmdmPolicy();
      $policy->getFromDBByQuery("WHERE `symbol`='storageEncryption'");
      $groupName = $policy->getField('group');
      $this->assertGreaterThan(0, $policy->getID(), "Could not find the test policy");

      // Prepare subscriber
      $mqttSubscriber = new MqttClientHandler();
      $publishedMessage = null;

      $addSuccess = null;

      $cronTask = new CronTask();
      $cronTask->getFromDBbyName("PluginStorkmdmMqttupdatequeue", "UpdateTopics");
      $cronTask->update(['id' => $cronTask->getID(), 'lastrun' => null]);

      $cronTask = new CronTask();
      $cronTask->getFromDBbyName("PluginStorkmdmMqttupdatequeue", "UpdateTopics");
      $cronTask->update(['id' => $cronTask->getID(), 'lastrun' => null]);

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$fleet, &$fleet_Policy, &$policy, &$addSuccess) {
         $addSuccess = $fleet_Policy->add([
               'plugin_storkmdm_fleets_id'   => $fleet->getID(),
               'plugin_storkmdm_policies_id' => $policy->getID(),
               'value'                       => '0'
         ]);
         PluginStorkmdmMqttupdatequeue::setDelay("PT0S");
         CronTask::launch(CronTask::MODE_EXTERNAL, 1, 'UpdateTopics');
      };

      // Callback each time the mqtt broker sends a pingresp
      $callback = function () use (&$publishedMessage, &$mqttSubscriber) {
         $publishedMessage = $mqttSubscriber->getPublishedMessage();
      };

      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($callback);
      $topic = $fleet->getTopic();
      $mqttSubscriber->subscribe("$topic/$groupName");
      $this->assertGreaterThan(0, $addSuccess, "Failed to apply the policy");
      $this->assertInstanceOf('\sskaje\mqtt\Message\PUBLISH', $publishedMessage);

      return $fleet_Policy;
   }

   /**
    * @depends testInitAddFleet
    * @depends testApplyPolicy
    */
   public function testApplyUniquePolicyTwice($fleet) {
      $fleet_Policy = new PluginStorkmdmFleet_Policy();
      $policy = new PluginStorkmdmPolicy();
      $policy->getFromDBByQuery("WHERE `symbol`='storageEncryption'");
      $this->assertGreaterThan(0, $policy->getID(), "Could not find the test policy");

      $fleet_PolicyId = $fleet_Policy->add([
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
            'plugin_storkmdm_policies_id' => $policy->getID(),
            'value'                       => '0'
      ]);
      $this->assertFalse($fleet_PolicyId);
   }

   /**
    * @depends testApplyPolicy
    */
   public function testChangePolicyProperty($fleet_Policy) {
      $this->assertTrue($fleet_Policy->update([
            'id'     => $fleet_Policy->getID(),
            'value'  => '1',
      ]), $_SESSION['MESSAGE_AFTER_REDIRECT']);
   }

}