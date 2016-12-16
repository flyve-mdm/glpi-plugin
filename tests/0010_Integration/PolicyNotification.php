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

class PolicyNotification extends RegisteredUserTestCase
{

   public function testInitCreateFleet() {
      $fleet = new PluginStorkmdmFleet();
      $fleet->add([
            'name'               => 'test fleet',
            'entities_id'        => $entityId
      ]);
      $this->assertFalse($fleet->isNewItem());

      return $fleet;
   }

   /**
    * @depends testInitCreateFleet
    */
   public function testApplyPolicy($fleet) {
      // Get a policy
      $policyData = new PluginStorkmdmPolicy();
      $this->assertTrue($policyData->getFromDBByQuery("WHERE `symbol`='passwordEnabled'"));
      $policyFactory = new PluginStorkmdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($policyData->getID());

      // Prepare subscriber
      $mqttSubscriber = new MqttClientHandler();
      $publishedMessage = null;

      // Contains true if the ploicy successfully applied to the fleet
      $fleetPolicyId = null;

      // function to trigger the mqtt message
      $sendMqttMessageCallback = function () use (&$fleetPolicy, &$fleetPolicyId, &$fleet, &$policyData) {
         // Apply the policy to a fleet
         $fleetPolicy = new PluginStorkmdmFleet_Policy();
         $fleetPolicyId = $fleetPolicy->add([
               'plugin_storkmdm_fleets_id'      => $fleet->getID(),
               'plugin_storkmdm_policies_id'    => $policyData->getID(),
               'value'                          => 'PASSWORD_NONE'
         ]);
      };

      // Callback each time the mqtt broker sends a pingresp
      $callback = function () use (&$publishedMessage, &$mqttSubscriber) {
         $publishedMessage = $mqttSubscriber->getPublishedMessage();
      };

      $mqttSubscriber->setSendMqttMessageCallback($sendMqttMessageCallback);
      $mqttSubscriber->setPingCallback($callback);
      $topic = $fleet->getTopic();
      $mqttSubscriber->subscribe("$topic/Command");

      $this->assertGreaterThan(0, $fleetPolicyId, "Failed to apply the policy");
      $this->assertInstanceOf('\sskaje\mqtt\Message\PUBLISH', $publishedMessage);

      $data = array();
      $data['publishedMessage'] = $publishedMessage;

      return $data;
   }

   /**
    * @depends testApplyPolicy
    * @param array $data
    */
   public function testPolicyApplyMessageIsValid($data)
   {
      $published = $data['publishedMessage'];
      $json = $published->getMessage();
      $this->assertJson($json);
   }

}