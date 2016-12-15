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

class CreateInvitationSendsAnEmail extends RegisteredUserTestCase {

   /**
    * Create an invitation for enrollment tests
    */
   public function testInvitationCreation() {
      global $CFG_GLPI;
      self::$fixture['guestEmail'] = 'guestuser0001@localhost.local';

      // enable email notifications mailing
      $CFG_GLPI["use_mailing"] = 1;

      $invitation = new PluginStorkmdmInvitation();
      $invitationId = $invitation->add([
         'entities_id'  => $_SESSION['glpiactive_entity'],
         '_useremails'  => self::$fixture['guestEmail'],
      ]);
      $this->assertGreaterThan(0, $invitationId);

      return $invitation;
   }

   /**
    * @depends testInvitationCreation
    */
   public function testUserExists($invitation) {
      $user = new User();
      $this->assertTrue($user->getFromDB($invitation->getField('users_id')));

      return $user;
   }

   /**
    * @depends testInvitationCreation
    * @depends testUserExists
    * @param unknown $invitation
    * @param unknown $user
    */
   public function testQueuedMail($invitation, $user) {
      $invitationId = $invitation->getID();
      $queuedMail = new QueuedMail();
      $this->assertTrue($queuedMail->getFromDBByQuery(" WHERE `itemtype`='PluginStorkmdmInvitation' AND `items_id`='$invitationId'" ));
   }

}