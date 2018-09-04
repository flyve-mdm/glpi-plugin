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
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmPackage extends CommonTestCase {

   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testShowForm':
         case 'testPrepareInputForAdd':
         case 'testAddNeededInfoToInput':
         case 'testPrepareInputForUpdate':
         case 'testPostGetFromDB':
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
         case 'testAddNeededInfoToInput':
         case 'testPrepareInputForUpdate':
            parent::afterTestMethod($method);
            $this->terminateSession();
            break;
      }
   }

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $class = $this->testedClass->getClass();
      $this->string($class::getTypeName(1))->isEqualTo('Package')
         ->string($class::getTypeName(3))->isEqualTo('Packages');
   }

   /**
    * @tags testGetMenuPicture
    */
   public function testGetMenuPicture() {
      $class = $this->testedClass->getClass();
      $this->given($class)
         ->string($class::getMenuPicture())->isEqualTo('fa-gear');
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
         ->matches("#method='post' action='.+?\/plugins\/flyvemdm\/front\/package\.form\.php'#")
         ->contains("input type='hidden' name='entities_id' value='0'")
         ->contains("type='text' name='name' value=\"\"")
         ->contains("type=\"text\" name=\"alias\" value=\"\"")
         ->contains("type='file' name='file[]'")
         ->contains('input type="hidden" name="_glpi_csrf_token"');
   }

   /**
    * @tags testAddNeededInfoToInput
    */
   public function testAddNeededInfoToInput() {
      $instance = $this->newTestedInstance();
      $input = $instance->addNeededInfoToInput([]);
      $this->array($input)->hasKey('entities_id')->integer['entities_id']->isEqualTo(0);
   }

   public function providerPostGetFromDB() {
      return [
         [['isApi' => false, 'download' => false]],
         [['isApi' => true, 'download' => false]],
         //[['isApi' => true, 'download' => true]], // this assert needs mocking the isAPI function
      ];
   }

   /**
    * @tags testPostGetFromDB
    * @dataProvider providerPostGetFromDB
    * @param $argument
    */
   public function testPostGetFromDB($argument) {
      $isApi = $argument['isApi'];
      if ($isApi && $argument['download']) {
         $_SERVER['HTTP_ACCEPT'] = 'application/octet-stream';
      }

      $file = $this->createDummyPackage($_SESSION['glpiactive_entity']);
      if ($isApi && $argument['download']) {
         $this->resource($file)->isStream();
      } else if ($isApi) {
         $this->array($fields = $file->fields)->hasKeys(['filesize', 'mime_type'])
            ->integer($fields['filesize'])->isGreaterThan(0)
            ->string($fields['mime_type'])->isNotEmpty();
      } else {
         $this->object($file)->isInstanceOf('PluginFlyvemdmPackage');
      }
   }

}