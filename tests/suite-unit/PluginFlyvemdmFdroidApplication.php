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

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmFDroidApplication extends CommonTestCase {

   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testPrepareInputForAdd':
         case 'testPrepareInputForUpdate':
         case 'testDisplayTabContentForItem':
         case 'testGetTabNameForItem':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   public function afterTestMethod($method) {
      switch ($method) {
         case 'testPrepareInputForAdd':
         case 'testPrepareInputForUpdate':
         case 'testDisplayTabContentForItem':
         case 'testGetTabNameForItem':
            parent::afterTestMethod($method);
            \Session::destroy();
            break;
      }
   }

   /**
    * @tags testGetEnumImportStatus
    */
   public function testGetEnumImportStatus() {
      $output = \PluginFlyvemdmFDroidApplication::getEnumImportStatus();
      $this->array($output)->size->isEqualTo(3);
      $this->array($output)->hasKeys([
         'no_import',
         'to_import',
         'imported',
      ]);
   }

   /**
    * @tags testGetMenuPicture
    */
   public function testGetMenuPicture() {
      $output = \PluginFlyvemdmFDroidApplication::getMenuPicture();
      $this->string($output)->isEqualTo('');
   }

   public function providerGetTypeName() {
      return [
         'none'      => [
            0,
            'F-Droid applications',
         ],
         'singular'  => [
            1,
            'F-Droid application',
         ],
         'plural'    => [
            2,
            'F-Droid applications',
         ],
      ];
   }

   /**
    * @tags testGetTypeName
    * @dataProvider providerGetTypeName
    */
   public function testGetTypeName($count, $expected) {
      $output = \PluginFlyvemdmFDroidApplication::getTypeName($count);
      $this->string($output)->isEqualTo($expected);
   }

   public function providerGetTabNameForItem() {
      return [
         [
            new \PluginFlyvemdmFDroidMarket(),
            0,
            'F-Droid applications',
         ],
         [
            new \PluginFlyvemdmFDroidMarket(),
            1,
            '',
         ],
         [
            new \PluginFlyvemdmInvitation(),
            0,
            '',
         ],
      ];
   }

   /**
    * @dataProvider providerGetTabNameForItem
    * @tags testGetTabNameForItem
    */
   public function testGetTabNameForItem($item, $withTemplate, $expected) {
      $instance = $this->newTestedInstance();
      $output = $instance->getTabNameForItem($item, $withTemplate);
      $this->string($output)->isEqualTo($expected);
   }

   public function providerDisplayTabContentForItem() {
      return [
         'no app for a market' => [
            'item'     => new \PluginFlyvemdmFDroidMarket(),
            'expected' => 'There is no application yet',
         ],
      ];
   }

   /**
    * @tags testGetAdditionalLinks
    */
   public function testGetAdditionalLinks() {
      $instance = $this->newTestedInstance();
      $output = $instance->getAdditionalLinks();
      $this->array($output)->size->isEqualTo(0);
   }

   /**
    * @tags testGetRights
    */
   public function testGetRights() {
      $instance = $this->newTestedInstance();
      $this->array($result = $instance->getRights())->containsValues([
         'Read',
         'Update',
      ]);
   }

   public function providerCronInfo() {
      return [
         [
            'DownloadApplications',
            ['description' => __('download applications from the market')],
         ],
         [
            'SomethingElse',
            null,
         ],
      ];
   }

   /**
    * @tags testCronInfo
    * @dataProvider providerCronInfo
    */
   public function testCronInfo($name, $expected) {
      $output = \PluginFlyvemdmFDroidApplication::cronInfo($name);
      if ($expected !== null) {
         $this->array($output)->size->isEqualTo(1);
         $this->array($output)
            ->hasKey('description')
            ->contains('download applications from the market');
      } else {
         $this->variable($output)->isNull();
      }
   }

   public function providerTestImport() {
      $instance = $this->newTestedInstance();
      $applicationName = 'I exist' . $this->getUniqueString();
      $id = $instance->add([
         'name' => $applicationName
      ]);
      $this->boolean($instance->isNewItem())->isFalse();
      return [
         'no name' => [
            'input' => [
               'key' => 'something',
            ],
            'expected' => false,
         ],
         'existing item' => [
            'input' => [
               'name' => $applicationName,
            ],
            'expected' => $id,
         ],
      ];
   }

   /**
    * @tags testImport
    * @dataProvider providerTestImport
    */
   public function testImport($input, $expected) {
      $instance = $this->newTestedInstance();
      $output = $instance->import($input);
      $this->variable($output)->isEqualTo($expected);
      if ($expected === false) {
         $this->boolean($output);
      }

      // test non existing item
      $missingName = 'I miss' . $this->getUniqueString();
      $output = $instance->import([
         'name' => $missingName,
      ]);
      $instance = $this->newTestedInstance();
      $this->boolean($instance->getFromDB($output))->isTrue();
   }

   public function providerPrepareInputForAdd() {
      return [
         'no checks' => [
            'input' => [
               '_skip_checks' => '',
               'something' => 'random',
            ],
            'expected' => [
               '_skip_checks' => '',
               'something' => 'random',
            ]
         ],
      ];
   }

   /**
    * @dataProvider providerPrepareInputForAdd
    */
   public function testPrepareInputForAdd($input, $expected) {

   }
}