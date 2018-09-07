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

class PluginFlyvemdmFDroidMarket extends CommonTestCase {

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
            'F-Droid Markets',
         ],
         'singular'  => [
            1,
            'F-Droid Market',
         ],
         'plural'    => [
            2,
            'F-Droid Markets',
         ],
      ];
   }

   /**
    * @tags testGetTypeName
    * @dataProvider providerGetTypeName
    */
   public function testGetTypeName($count, $expected) {
      $output = \PluginFlyvemdmFDroidMarket::getTypeName($count);
      $this->string($output)->isEqualTo($expected);
   }

   public function providerCronInfo() {
      return [
         [
            'UpdateRepositories',
            ['description' => __('Updates the list of applications')],
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
      $output = \PluginFlyvemdmFDroidMarket::cronInfo($name);
      if ($expected !== null) {
         $this->array($output)->size->isEqualTo(1);
         $this->array($output)
            ->hasKey('description')
            ->contains('Updates the list of applications');
      } else {
         $this->variable($output)->isNull();
      }
   }

   public function testCronUpdateRepositories() {
      $fixtureFile = __DIR__ . '/../fixtures/fdroid-repo.xml';
      $this->boolean(is_readable($fixtureFile));
      $instances = [
         $this->newTestedInstance(),
         $this->newTestedInstance(),
      ];
      foreach ($instances as $instance) {
         $instance->add([
            'name' => $this->getUniqueString(),
            'url'  => $fixtureFile,
         ]);
      }
      \PluginFlyvemdmFDroidMarket::cronUpdateRepositories(new \CronTask());

      $fdroidApplication = new \PluginFlyvemdmFDroidApplication();
      $marketFk = \PluginFlyvemdmFDroidMarket::getForeignKeyField();
      foreach ($instances as $instance) {
         $marketId = $instance->getID();
         $rows = $fdroidApplication->find("`$marketFk` = '$marketId'");
         $this->array($rows)->size->isEqualTo(1);
      }
   }

   public function testUpdateRepository() {
      $fixtureFile = __DIR__ . '/../fixtures/fdroid-repo.xml';
      $this->boolean(is_readable($fixtureFile));
      $instance = $this->newTestedInstance();
      $instance->add([
         'name' => $this->getUniqueString(),
         'url'  => $fixtureFile,
      ]);

      $instance->getFromDB($instance->getID());
      $instance->updateRepository();

      $fdroidApplication = new \PluginFlyvemdmFDroidApplication();
      $marketFk = \PluginFlyvemdmFDroidMarket::getForeignKeyField();
      $marketId = $instance->getID();
      $rows = $fdroidApplication->find("`$marketFk` = '$marketId'");
      $this->array($rows)->size->isEqualTo(1, json_encode($rows, JSON_PRETTY_PRINT));
   }
}