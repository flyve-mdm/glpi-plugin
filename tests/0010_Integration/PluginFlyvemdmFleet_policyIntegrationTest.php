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

class PluginFlyvemdmFleet_PolicyIntegrationTest extends RegisteredUserTestCase {

   public function testInitAddFleet() {
      $fleet = new PluginFlyvemdmFleet();
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
      $invitation = new PluginFlyvemdmInvitation();
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
      $_REQUEST['user_token'] = User::getToken($invitation->getField('users_id'), 'api_token');
      Session::destroy();
      $this->assertTrue(self::login('', '', false));
      unset($_REQUEST['user_token']);

      $agent = new PluginFlyvemdmAgent();
      $agentId = $agent->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => 'guestuser0001@localhost.local',
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);
      $this->assertGreaterThan(0, $agentId, $_SESSION['MESSAGE_AFTER_REDIRECT']);

      return $agent;
   }

   /**
    * @depends testInitAddFleet
    */
   public function testApplyPolicy($fleet) {
      global $DB;

      $policy = new PluginFlyvemdmPolicy();
      $policy->getFromDBByQuery("WHERE `symbol` = 'storageEncryption'");
      $groupName = $policy->getField('group');
      $this->assertGreaterThan(0, $policy->getID(), "Could not find the test policy");
      $fleetId = $fleet->getID();

      $table = PluginFlyvemdmMqttupdatequeue::getTable();
      $this->assertTrue($DB->query("TRUNCATE TABLE `$table`"));

      $fleet_Policy = new PluginFlyvemdmFleet_Policy();
      $addSuccess = $fleet_Policy->add([
            'plugin_flyvemdm_fleets_id'   => $fleetId,
            'plugin_flyvemdm_policies_id' => $policy->getID(),
            'value'                       => '0'
      ]);

      $mqttUpdateQueue = new PluginFlyvemdmMqttupdatequeue();
      $rows = $mqttUpdateQueue->find("`group` = '$groupName'
                                      AND `plugin_flyvemdm_fleets_id` = '$fleetId'
                                      AND `status` = 'queued'");
      $this->assertCount(1, $rows);

      return $fleet_Policy;
   }

   /**
    * @depends testInitAddFleet
    * @depends testApplyPolicy
    */
   public function testApplyUniquePolicyTwice($fleet) {
      $fleet_Policy = new PluginFlyvemdmFleet_Policy();
      $policy = new PluginFlyvemdmPolicy();
      $policy->getFromDBByQuery("WHERE `symbol`='storageEncryption'");
      $this->assertGreaterThan(0, $policy->getID(), "Could not find the test policy");

      $fleet_PolicyId = $fleet_Policy->add([
            'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policy->getID(),
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