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

class DeviceCountLimit extends RegisteredUserTestCase {

   protected static $deviceLimit = 5;

   /**
    * Set the default device limit to a low valur for testing purpose
    * before creating the registered user account
    */
   public function testInitDeviceLimit() {
      $entityConfig = new PluginStorkmdmEntityConfig();
      $entityConfig->update(array(
         'id'           => $_SESSION['glpiactive_entity'],
         'device_limit' => self::$deviceLimit
      ));
   }

   public  function testCreateInvitations() {
      $data = array();
      for ($i = 0; $i <=  self::$deviceLimit; $i++) {
         $email = "guestuser$i@localhost.local";
         $invitation = new PluginStorkmdmInvitation();
         $invitationId = $invitation->add([
               'entities_id'  => $_SESSION['glpiactive_entity'],
               '_useremails'  => $email,
         ]);
          $data[] = array('invitation' => $invitation, 'email' => $email);
      }
      return $data;
   }

   /**
    * Enrolls an agent as guest user
    *
    * @depends testCreateInvitations
    */
   public function testEnrollAgent($enrollmentData) {

      for ($i = 0; $i < count($enrollmentData); $i++) {
         $invitation = $enrollmentData[$i]['invitation'];
         $email = $enrollmentData[$i]['email'];

         // Login as guest user
         $_REQUEST['user_token'] = User::getPersonalToken($invitation->getField('users_id'));
         Session::destroy();
         $this->assertTrue(self::login('', '', false));
         unset($_REQUEST['user_token']);

         $agent = new PluginStorkmdmAgent();
         $agentId = $agent ->add([
               'entities_id'        => $_SESSION['glpiactive_entity'],
               '_email'             => $email,
               '_invitation_token'  => $invitation->getField('invitation_token'),
               '_serial'            => 'AZERTY_' . $invitation->getID(),
               'csr'                => '',
               'firstname'          => 'John',
               'lastname'           => 'Doe'
         ]);
         if ($i < self::$deviceLimit) {
            // Agent creation should succeed
            $this->assertGreaterThan(0, $agentId, $_SESSION['MESSAGE_AFTER_REDIRECT']);
         } else {
            // Device limit reached : agent creation should fail
            $this->assertFalse($agentId);
         }
      }

      return $agent;
   }

   public function testRegisteredUserCannotChangeLimit() {
      $entityConfig = new PluginStorkmdmEntityconfig();
      $right = $entityConfig->canUpdate();
      $right = ($right === null || $right === false);
      $this->assertTrue($right);

   }
}