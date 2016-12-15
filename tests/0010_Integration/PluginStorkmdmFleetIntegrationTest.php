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

class PluginStorkmdmFleetIntegrationTest extends RegisteredUserTestCase {

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

   public function testDeleteDefaultFleet() {
      $fleet = new PluginStorkmdmFleet();
      $entityId = $_SESSION['glpiactive_entity'];
      $this->assertTrue($fleet->getFromDBByQuery("WHERE `is_default`='1' AND `entities_id`='$entityId' LIMIT 1"));

      $this->assertFalse($fleet->delete(['id' => $fleet->getID()]));
   }

   public function testAddFleet() {
      // The API automatically sets entites_id
      $input = [
            'entities_id'     => $_SESSION['glpiactive_entity'],
            'name'            => 'a fleet'
      ];

      $fleet = new PluginStorkmdmFleet();
      $this->assertGreaterThan(0, $fleet->add($input));
      return $fleet;
   }

   /**
    * @depends testAddFleet
    * @depends testInitEnrollAgent
    */
   public function testAddAgentToFleet(PluginStorkmdmFleet $fleet, PluginStorkmdmAgent $agent) {
      $updateSuccess = $agent->update([
            'id'                          => $agent->getID(),
            'plugin_storkmdm_fleets_id'   => $fleet->getID()
      ]);
      $this->assertTrue($updateSuccess);

      return $fleet;
   }

   /**
    * @depends testAddFleet
    * @depends testInitEnrollAgent
    */
   public function testApplyPolicyToFleet(PluginStorkmdmFleet $fleet) {
      $policyData = new PluginStorkmdmPolicy();
      $policyData->getFromDBBySymbol('disableGPS');
      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $fleet_policy->add([
            'plugin_storkmdm_policies_id'    => $policyData->getID(),
            'plugin_storkmdm_fleets_id'      => $fleet->getID(),
            'value'                          => '0'
      ]);

      $this->assertFalse($fleet_policy->isNewItem());
   }

   /**
    * @depends testAddAgentToFleet
    * @depends testApplyPolicyToFleet
    */
   public function testPurgeFleet(PluginStorkmdmFleet $fleet) {
      $deleteSuccess = $fleet->delete([
            'id'     => $fleet->getID()
      ]);
      $this->assertTrue($deleteSuccess);

      return $fleet;
   }

   /**
    * @depends testPurgeFleet
    */
   public function testAgentUnlinkedAfterPurge(PluginStorkmdmFleet $fleet) {
      $entityId = $_SESSION['glpiactive_entity'];
      $fleetId = $fleet->getID();

      $agent = new PluginStorkmdmAgent();
      $rows = $agent->find("`entities_id`='$entityId' AND `plugin_storkmdm_fleets_id`='$fleetId'");

      // Should be no agent linked to the deleted fleet
      $this->assertEquals(0, count($rows));
   }

   /**
    * @depends testPurgeFleet
    */
   public function testPolicyUnlinkedAfterPurge(PluginStorkmdmFleet $fleet) {
      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $fleetId = $fleet->getID();
      $rows = $fleet_policy->find("`plugin_storkmdm_fleets_id`='$fleetId'");
      $this->assertEquals(0, count($rows));
   }

}
