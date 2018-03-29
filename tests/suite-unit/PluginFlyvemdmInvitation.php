<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2018 Teclib'
 * Copyright © 2010-2018 by the FusionInventory Development Team.
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
 * @author    Thierry Bugier
 * @copyright Copyright © 2018 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Tests\CommonTestCase;

class PluginFlyvemdmInvitation extends CommonTestCase {

   /**
    * @param $method
    */
   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testShowForm':
         case 'testPrepareInputForAdd':
         case 'testPrepareInputForUpdate':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    * @param $method
    */
   public function afterTestMethod($method) {
      switch ($method) {
         case 'testShowForm':
         case 'testPrepareInputForAdd':
         case 'testPrepareInputForUpdate':
            parent::afterTestMethod($method);
            \Session::destroy();
            break;
      }
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
      $instance = $this->newTestedInstance();
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
      $this->string($class::getTypeName(1))->isEqualTo('Invitation')
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
      $instance = $this->newTestedInstance();
      $this->array($result = $instance->getRights())->containsValues([
         'Create',
         'Read',
         'Update',
         ['short' => 'Purge', 'long' => 'Delete permanently'],
      ]);
   }

   /**
    * @tags testPrepareInputForAdd
    * @engine inline
    */
   public function testPrepareInputForAdd() {
      $uniqueEmail = $this->getUniqueEmail();
      $sessionMessages = [
         'Email address is invalid',
         'Cannot create the user',
         'Document move succeeded.',
         'The user already exists and has been deleted. You must restore or purge him first.',
      ];
      $instance = $this->newTestedInstance();

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
      if (is_dir($destination = GLPI_DOC_DIR . '/PNG/')) {
         // this folder should no exist on test environment for asserting the test
         \PluginFlyvemdmCommon::recursiveRmdir($destination);
      }
      $result = $instance->prepareInputForAdd($input);
      $this->boolean($instance->isNewItem())->isTrue()
         ->array($result)->hasKeys([
            '_useremails',
            'entities_id',
            'users_id',
            'invitation_token',
            'expiration_date',])
         ->string($result['_useremails'])->isEqualTo($uniqueEmail)
         ->string($result['invitation_token'])
         ->string($expiration = $result['expiration_date']);

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
      $instance = $this->newTestedInstance();
      $this->array($instance->prepareInputForUpdate([]));
   }

   /**
    * @tags testGetFromDBByToken
    */
   public function testGetFromDBByToken() {
      $instance = $this->newTestedInstance();
      $this->boolean($instance->getFromDBByToken('invalidToken'))->isFalse();
   }

   /**
    * @tags testShowForm
    */
   public function testShowForm() {
      $instance = $this->newTestedInstance();
      ob_start();
      $instance->showForm(0);
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)
         ->matches("#method='post' action='.+?\/plugins\/flyvemdm\/front\/invitation\.form\.php'#")
         ->contains("input type='hidden' name='entities_id' value='0'")
         ->contains('input name="_useremails" value=""')
         ->contains('input type="hidden" name="_glpi_csrf_token"');
   }
}