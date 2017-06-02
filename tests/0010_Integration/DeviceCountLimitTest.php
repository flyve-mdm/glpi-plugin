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

class DeviceCountLimitTest extends RegisteredUserTestCase {

   protected static $deviceLimit = 5;

   protected static $invitationData;

   public static function setUpBeforeClass() {
      parent::setUpBeforeClass();

      self::login('glpi', 'glpi', true);

      $entityConfig = new PluginFlyvemdmEntityConfig();
      $entityConfig->update(array(
            'id'           => $_SESSION['glpiactive_entity'],
            'device_limit' => self::$deviceLimit
      ));

      self::$invitationData = array();
      for ($i = 0; $i <=  self::$deviceLimit; $i++) {
         $email = "guestuser$i@localhost.local";
         $invitation = new PluginFlyvemdmInvitation();
         $invitationId = $invitation->add([
               'entities_id'  => $_SESSION['glpiactive_entity'],
               '_useremails'  => $email,
         ]);
         self::$invitationData[] = array('invitation' => $invitation, 'email' => $email);
      }

      Session::destroy();
   }

   /**
    * Enrolls an agent as guest user
    *
    */
   public function testEnrollAgent() {
      for ($i = 0; $i < count(self::$invitationData) - 1; $i++) {
         $invitation = self::$invitationData[$i]['invitation'];
         $email = self::$invitationData[$i]['email'];

         // Login as guest user
         if (version_compare(GLPI_VERSION, "9.2", "ge")) {
            $_REQUEST['user_token']= User::getToken($invitation->getField('users_id'), 'api_token');
         } else {
            $_REQUEST['user_token']= User::getPersonalToken($invitation->getField('users_id'));
         }
         Session::destroy();
         $this->assertTrue(self::login('', '', false));
         unset($_REQUEST['user_token']);

         $agent = new PluginFlyvemdmAgent();
         $agentId = $agent->add([
               'entities_id'        => $_SESSION['glpiactive_entity'],
               '_email'             => $email,
               '_invitation_token'  => $invitation->getField('invitation_token'),
               '_serial'            => 'AZERTY_' . $invitation->getID(),
               'csr'                => '',
               'firstname'          => 'John',
               'lastname'           => 'Doe',
               'version'            => '1.0.0',
         ]);
         // Agent creation should succeed
         $this->assertGreaterThan(0, $agentId, json_encode($_SESSION['MESSAGE_AFTER_REDIRECT']));
      }

      // One nore ienrollment
      $invitation = self::$invitationData[$i]['invitation'];
      $email = self::$invitationData[$i]['email'];

      // Login as guest user
      if (version_compare(GLPI_VERSION, "9.2", "ge")) {
         $_REQUEST['user_token']= User::getToken($invitation->getField('users_id'), 'api_token');
      } else {
         $_REQUEST['user_token']= User::getPersonalToken($invitation->getField('users_id'));
      }
      Session::destroy();
      $this->assertTrue(self::login('', '', false));
      unset($_REQUEST['user_token']);

      $agent = new PluginFlyvemdmAgent();
      $agentId = $agent->add([
            'entities_id'        => $_SESSION['glpiactive_entity'],
            '_email'             => $email,
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY_' . $invitation->getID(),
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);
      // Device limit reached : agent creation should fail
      $this->assertFalse($agentId);

      return $agent;
   }
}