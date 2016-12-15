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

class PolicyNotificationOnMoveToDefaultFleet extends RegisteredUserTestCase
{

   protected $guestEmail;

   public function testInitCreateInvitation() {
      $invitation = new PluginStorkmdmInvitation();
      $invitation->add([
            '_useremails'        => 'guest@localhost.local',
            'entities_id'        => $_SESSION['glpiactive_entity']
      ]);
      $this->assertFalse($invitation->isNewItem());

      return $invitation;
    }

    /**
     * @depends testInitCreateInvitation
     */
    public function testInitEnrollDevice($invitation) {
      // Login as guest user
      $_REQUEST['user_token'] = User::getPersonalToken($invitation->getField('users_id'));
      Session::destroy();
      self::login('', '', false);
      unset($_REQUEST['user_token']);

      $agent = new PluginStorkmdmAgent();
      $agent->add([
            '_serial'            => 'AZERTY',
            '_email'             => 'guest@localhost.local',
            '_invitation_token'  => $invitation->getField('invitation_token'),
            'csr'                => '',
            'firstname'          => '',
            'lastname'           => '',
            'entities_id'        => $_SESSION['glpiactive_entity'],
            'version'            => '1.0.0'
      ]);
      $this->assertFalse($agent->isNewItem());

      return $agent;
    }

    public function testInitCreateFleet() {
       // Create a fleet
       $fleet = new PluginStorkmdmFleet();
       $fleet->add([
             'name'               => 'test fleet',
             'entities_id'        => $_SESSION['glpiactive_entity']
       ]);
       $this->assertFalse($fleet->isNewItem());

        return $fleet;
    }

    /**
     * @depends testInitEnrollDevice
     * @depends testInitCreateFleet
     * @param unknown $agent
     * @param unknown $fleet
     */
    public function testInitMoveAgentInFleet($agent, $fleet) {
      // add the agent in the fleet
      $this->assertTrue($agent->update([
            'id'                          => $agent->getID(),
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
      ]));

      return $agent;
    }

    public function testInitGetDefaultFleet() {
       // Find the default fleet
       $entityId = $_SESSION['glpiactive_entity'];
       $fleet = new PluginStorkmdmFleet();
       $this->assertTrue($fleet->getFromDBByQuery(" WHERE `is_default`='1' AND `entities_id`='$entityId'"));

      return $fleet;
    }

    /**
    * @depends testInitCreateInvitation
    * @depends testInitGetDefaultFleet
    */
   public function testPolicyNotification($agent, $defaultFleet) {
      $mockAgent = $this->getMockForItemtype(PluginStorkmdmAgent::class, ['notify']);

      $mockAgent->expects($this->never())
      ->method('notify');

      $updateSuccess = $mockAgent->update([
            'id'                          => $agent->getID(),
            'plugin_storkmdm_fleets_id'   => $defaultFleet->getID()
      ]);
   }
}