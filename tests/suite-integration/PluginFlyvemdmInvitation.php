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

class PluginFlyvemdmInvitation extends CommonTestCase
{
   public function setUp() {
      parent::setUp();
      $this->setupGLPIFramework();
   }

   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testInvitationCreation':
            $this->resetState();
            $this->setupGLPIFramework();
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    * @return object
    */
   public function createNewInstance() {
      $instance = $this->newTestedInstance();
      return $instance;
   }

   /**
    * @tags testClass
    */
   public function testClass() {
      $this->testedClass->hasConstant('DEFAULT_TOKEN_LIFETIME');
      $class = $this->testedClass->getClass();
      $this->given($class)->string($class::$rightname)->isEqualTo('flyvemdm:invitation');
   }

   /**
    * @tags testGetEnumInvitationStatus
    */
   public function testGetEnumInvitationStatus() {
      $instance = $this->createNewInstance();
      $this->array($result = $instance->getEnumInvitationStatus())
         ->hasKeys(['pending', 'done'])
         ->string($result['pending'])->isEqualTo('Pending')
         ->string($result['done'])->isEqualTo('Done');
   }

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $class = $this->testedClass->getClass();
      $this->given($class)
         // ->string($class::getTypeName())->isEqualTo('Invitation') // TODO: check why this is failing
         ->string($class::getTypeName(3))->isEqualTo('Invitations');
   }

   /**
    * @tags testGetMenuPicture
    */
   public function testGetMenuPicture() {
      $class = $this->testedClass->getClass();
      $this->given($class)
         ->string($class::getMenuPicture())->isEqualTo('fa-paper-plane');
   }

   /**
    * @tags testGetRights
    */
   public function testGetRights() {
      $instance = $this->createNewInstance();
      $this->array($result = $instance->getRights())->containsValues([
         'Create',
         'Read',
         'Update',
         ['short' => 'Purge', 'long' => 'Delete permanently'],
      ]);
   }

   /**
    * @tags testPrepareInputForAdd
    */
   public function testPrepareInputForAdd() {
      $this->login('glpi', 'glpi');
      $uniqueEmail = $this->getUniqueEmail();
      $sessionMessages = [
         'Email address is invalid',
         'Cannot create the user',
         'Document move succeeded.',
         'The user already exists and has been deleted. You must restore or purge him first.',
      ];
      $instance = $this->createNewInstance();

      // empty array
      $this->boolean($instance->prepareInputForAdd([]))->isFalse();
      $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][0][0])->isEqualTo($sessionMessages[0]);
      unset($_SESSION["MESSAGE_AFTER_REDIRECT"][0]);

      // invalid email
      $this->boolean($instance->prepareInputForAdd(['_useremails' => '']))->isFalse();
      $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][0][0])->isEqualTo($sessionMessages[0]);
      unset($_SESSION["MESSAGE_AFTER_REDIRECT"][0]);

      $input = [
         '_useremails' => $uniqueEmail,
         'entities_id' => $_SESSION['glpiactive_entity'],
      ];

      // TODO: error adding user
      /*$this->boolean($instance->prepareInputForAdd($input))->isFalse();
      $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][0][0])->isEqualTo($sessionMessages[1]);
      unset($_SESSION["MESSAGE_AFTER_REDIRECT"][0]);*/

      // success
      $result = $instance->prepareInputForAdd($input);
      $this->boolean($instance->isNewItem())->isTrue()->array($result)->hasKeys([
         '_useremails',
         'entities_id',
         'users_id',
         'invitation_token',
         'expiration_date',
         'documents_id',
      ])->string($result['_useremails'])->isEqualTo($uniqueEmail)->integer($result['documents_id'])
         ->string($result['invitation_token'])->string($expiration = $result['expiration_date']);
      $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][0][0])->isEqualTo($sessionMessages[2]);
      unset($_SESSION["MESSAGE_AFTER_REDIRECT"][0]);

      // check if expiration date is valid
      $this->if($expiration = new \DateTime($expiration))
         ->dateTime($expiration->sub(new \DateInterval(\PluginFlyvemdmInvitation::DEFAULT_TOKEN_LIFETIME)))
         ->hasDate(date('Y'), date('m'), date('d'));

      // Do not handle deleted users
      $user = new \User();
      $user->deleteByCriteria(['name' => $uniqueEmail]);
      $this->boolean($instance->prepareInputForAdd($input))->isFalse();
      $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][0][0])->isEqualTo($sessionMessages[3]);
      unset($_SESSION["MESSAGE_AFTER_REDIRECT"][0]);
   }

   /**
    * @tags testPrepareInputForUpdate
    */
   public function testPrepareInputForUpdate() {
      $this->login('glpi', 'glpi');
      $instance = $this->createNewInstance();
      $this->array($instance->prepareInputForUpdate([]));
   }

   /**
    * @tags testGetFromDBByToken
    */
   public function testGetFromDBByToken() {
      $instance = $this->createNewInstance();
      $this->boolean($instance->getFromDBByToken('invalidToken'))->isFalse();
   }

   /**
    * @tags testGetSearchOptionsNew
    */
   public function testGetSearchOptionsNew() {
      $this->given($this->newTestedInstance)
         ->array($result = $this->testedInstance->getSearchOptionsNew())
         ->child[0](function ($child) {
            $child->hasKeys(['id', 'name'])->values
               ->string[0]->isEqualTo('common')
               ->string[1]->isEqualTo('Invitation');
         })
         ->child[1](function ($child) {
            $child->hasKeys(['table', 'name'])->values
               ->string[1]->isEqualTo('glpi_users')
               ->string[2]->isEqualTo('name');
         })
         ->child[2](function ($child) {
            $child->hasKeys(['table', 'name'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_invitations')
               ->string[2]->isEqualTo('id');
         })
         ->child[3](function ($child) {
            $child->hasKeys(['table', 'name'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_invitations')
               ->string[2]->isEqualTo('status');
         })
         ->child[4](function ($child) {
            $child->hasKeys(['table', 'name'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_invitations')
               ->string[2]->isEqualTo('expiration_date');
         });
   }

   /**
    * @tags testShowForm
    */
   public function testShowForm() {
      $this->login('glpi', 'glpi');
      $instance = $this->createNewInstance();
      ob_start();
      $instance->showForm(0);
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)->contains("method='post' action='-/plugins/flyvemdm/front/invitation.form.php'")
         ->contains("<input type='hidden' name='entities_id' value='0'>")
         ->contains('<input name="_useremails" value="">')
         ->contains('input type="hidden" name="_glpi_csrf_token"');
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
      $this->string(json_encode([$document->getID()]))->isEqualTo($queuedNotification->getField('documents'));

      // Send an invitation to the same user
      $secondInvitation = $this->newTestedInstance();
      $secondInvitation->add([
         'entities_id' => $_SESSION['glpiactive_entity'],
         '_useremails' => $email,
      ]);
      $this->boolean($secondInvitation->isNewItem())->isFalse();

      // Check both invitations have the same user
      $userFk = User::getForeignKeyField();
      $this->integer((int)$invitation->getField($userFk))->isEqualTo($secondInvitation->getField($userFk));
   }
}
