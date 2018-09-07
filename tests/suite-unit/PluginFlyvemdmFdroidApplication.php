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
      parent::afterTestMethod($method);
      switch ($method) {
         case 'testPrepareInputForAdd':
         case 'testPrepareInputForUpdate':
         case 'testDisplayTabContentForItem':
         case 'testGetTabNameForItem':
            \Session::destroy();
            break;
      }
   }

   /**
    * @tags testGetEnumImportStatus
    */
   public function testGetEnumImportStatus() {
      $output = \PluginFlyvemdmFDroidApplication::getEnumImportStatus();
      $this->array($output)->hasSize(3);
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
      $result = $instance->getRights();
      $this->array($result)->containsValues([
         'Read',
         'Update',
      ])
      ->hasKeys([READ, UPDATE]);
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
      $applicationName2 = 'I miss' . $this->getUniqueString();
      $id = $instance->add([
         'name' => $applicationName
      ]);
      $this->boolean($instance->isNewItem())->isFalse();
      $market = new \PluginFlyvemdmFDroidMarket();
      $market->add([
         'name' => $this->getUniqueString(),
      ]);
      $this->boolean($market->isNewItem())->isFalse();
      return [
         'no name' => [
            'input' => [
               'plugin_flyvemdm_fdroidmarkets_id' => $market->getID(),
            ],
            'expected' => false,
         ],
         'no repository' => [
            'input' => [
               'name' => 'something',
            ],
            'expected' => false,
         ],
         'existing item' => [
            'input' => [
               'name' => $applicationName,
               'plugin_flyvemdm_fdroidmarkets_id' => $market->getID(),
            ],
            'expected' => $id,
         ],
         'not existing item' => [
            'input' => [
               'name' => $applicationName2,
               'plugin_flyvemdm_fdroidmarkets_id' => $market->getID(),
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
      $output = \PluginFlyvemdmFDroidApplication::import($input);
      if ($expected === false) {
         $this->variable($output)->isEqualTo($expected);
         $this->boolean($output);
      } else {
         $this->integer($output);
         $instance = $this->newTestedInstance();
         $this->boolean($instance->getFromDB($output))->isTrue();
      }
   }

   public function providerPrepareInputForUpdate() {
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
         'checks' => [
            'input' => [
               'something' => 'random',
            ],
            'expected' => [
               'something' => 'random',
            ]
         ],
      ];
   }

   /**
    * @dataProvider providerPrepareInputForUpdate
    */
   public function testPrepareInputForUpdate($input, $expected) {
      $instance = $this->newTestedInstance();
      $output = $instance->prepareInputForUpdate($input);
      $this->array($output)->size->isEqualTo(count($expected));
      $this->array($output)->hasKeys(array_keys($expected));
   }

   public function testPost_updateItem() {
      // Create a market
      $fixtureFile = __DIR__ . '/../fixtures/fdroid-app-old-version.xml';
      $this->boolean(is_readable($fixtureFile));
      $fdroidMarket = new \PluginFlyvemdmFdroidMarket();
      $this->deleteAfterTestMethod[__FUNCTION__][] = $fdroidMarket;

      $fdroidMarket->add([
         'name' => $this->getUniqueString(),
         'url'  => $fixtureFile,
      ]);
      $this->boolean($fdroidMarket->isNewitem())->isFalse();
      $fdroidMarket->getFromDB($fdroidMarket->getID());
      // Download the applciations list from the market
      $fdroidMarket->updateRepository();

      // Get an application and ensure it is auto upgradable
      $instance = new $this->newTestedInstance();
      $instance->getFromDBByCrit([
         \PluginFlyvemdmFdroidMarket::getForeignKeyField() => $fdroidMarket->getID(),
         'package_name' => 'subreddit.android.appstore',
      ]);
      $this->boolean($instance->isNewitem())->isFalse();
      $instance->update([
         'id' => $instance->getID(),
         'is_auto_upgradable' => '1',
      ]);

      // import the application
      $this->boolean($instance->downloadApplication())
         ->isTrue();

      // Check the package is created
      $package = new \PluginFlyvemdmPackage();
      $package->getFromDB($instance->fields[
         \PluginFlyvemdmPackage::getForeignKeyField()
      ]);

      // Artificially mark the package parsed
      $package->update([
         'id' => $package->getID(),
         'parse_status' => 'parsed',
      ]);
      $this->string($package->fields['dl_filename'])
         ->isEqualTo($instance->fields['filename']);

      // Check the file is downloaded
      $content = file_get_contents(GLPI_PLUGIN_DOC_DIR . "/" . $package->fields['filename']);
      $this->string($content)->contains('oldversion');

      // Update the repository with up to date data
      $fixtureFile = __DIR__ . '/../fixtures/fdroid-app-new-version.xml';
      $fdroidMarket->update([
         'id'   => $fdroidMarket->getID(),
         'url'  => $fixtureFile,
      ]);

      // Download the applciations list from the market
      $fdroidMarket->updateRepository();

      // Get again an application and ensure it is auto upgradable
      $instanceUpdated = new $this->newTestedInstance();
      $instanceUpdated->getFromDBByCrit([
         \PluginFlyvemdmFdroidMarket::getForeignKeyField() => $fdroidMarket->getID(),
         'package_name' => 'subreddit.android.appstore',
      ]);
      $this->boolean($instanceUpdated->isNewitem())->isFalse();
      $this->string($instanceUpdated->fields['filename'])
         ->isNotEqualTo($instance->fields['filename']);

      // Check the package is created
      $packageUpdated = new \PluginFlyvemdmPackage();
      $packageUpdated->getFromDB($instance->fields[
         \PluginFlyvemdmPackage::getForeignKeyField()
      ]);

       // Check the file is downloaded
       $content = file_get_contents(GLPI_PLUGIN_DOC_DIR . "/" . $packageUpdated->fields['filename']);
       $this->string($content)->contains('newversion');

      $this->string($packageUpdated->fields['dl_filename'])
         ->isEqualTo($packageUpdated->fields['dl_filename']);
      $this->string($packageUpdated->fields['parse_status'])
         ->isEqualTo('pending');
   }

   public function testAddDefaultJoin() {
      $output = \PluginFlyvemdmFdroidApplication::addDefaultJoin('my_ref_table', []);
      $table = \PluginFlyvemdmFDroidMarket::getTable();
      $fkTable = \PluginFlyvemdmFDroidMarket::getForeignKeyField();
      $this->string($output)->isEqualTo("LEFT JOIN `$table` ON `$table`.`id`=`my_ref_table`.`$fkTable` ");
   }
}