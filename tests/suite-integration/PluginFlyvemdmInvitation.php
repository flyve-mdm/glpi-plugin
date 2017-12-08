<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2017 by the FusionInventory Development Team.
 *
 * This file is part of Flyve MDM Plugin for GLPI.
 *
 * Flyve MDM Plugin for GLPI is a subproject of Flyve MDM. Flyve MDM is a mobile
 * device management software.
 *
 * Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 * ------------------------------------------------------------------------------
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Test\CommonTestCase;
use Document;
use User;
use QueuedNotification;

class PluginFlyvemdmInvitation extends CommonTestCase {

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->setupGLPIFramework();
      $this->login('glpi', 'glpi');
   }

   public function afterTestMethod($method) {
      parent::afterTestMethod($method);
      \Session::destroy();
   }

   /**
    * @tags testInvitationCreation
    */
   public function testInvitationCreation() {
      $email = $this->getUniqueEmail();
      $invitation = $this->newTestedInstance();

      // Test an invitation with an invalid email
      $invitation->add([
         'entities_id' => $_SESSION['glpiactive_entity'],
         '_useremails' => $email,
      ]);
      $this->boolean($invitation->isNewItem())->isFalse();

      // check the guest user exists
      $user = new User();
      $this->boolean($user->getFromDB($invitation->getField(User::getForeignKeyField())))->isTrue();

      // check a email was queued
      $invitationType = \PluginFlyvemdmInvitation::class;
      $invitationId = $invitation->getID();
      $queuedNotification = new QueuedNotification();
      $this->boolean($queuedNotification->getFromDBByQuery(
         "WHERE `itemtype`='$invitationType' AND `items_id`='$invitationId'")
      )->isTrue();

      // Check a QR code is created
      $document = new Document();
      $documentFk = Document::getForeignKeyField();
      $document->getFromDB($invitation->getField($documentFk));
      $this->boolean($document->isNewItem())->isFalse();

      // Check the pending email has the QR code as attachment
      $this->string(json_encode([$document->getID()]))
         ->isEqualTo($queuedNotification->getField('documents'));

      // Send an invitation to the same user
      $secondInvitation = $this->newTestedInstance();
      $secondInvitation->add([
         'entities_id' => $_SESSION['glpiactive_entity'],
         '_useremails' => $email,
      ]);
      $this->boolean($secondInvitation->isNewItem())->isFalse();

      // Check both invitations have the same user
      $userFk = User::getForeignKeyField();
      $this->integer((int) $invitation->getField($userFk))
         ->isEqualTo($secondInvitation->getField($userFk));
   }
}
