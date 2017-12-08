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
 * @author    Thierry Bugier
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Test\CommonTestCase;

class PluginFlyvemdmFleet extends CommonTestCase {

   /**
    * @param $method
    */
   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testDefaultFleet':
         case 'testShowForm':
         case 'testPrepareInputForAdd':
         case 'testFromDBByDefaultForEntity':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    * @param $method
    */
   public function afterTestMethod($method) {
      switch ($method) {
         case 'testDefaultFleet':
         case 'testShowForm':
         case 'testPrepareInputForAdd':
         case 'testFromDBByDefaultForEntity':
            parent::afterTestMethod($method);
            \Session::destroy();
            break;
      }
   }

   /**
    * @return object
    */
   private function createInstance() {
      $this->newTestedInstance();
      return $this->testedInstance;
   }

   /**
    * @tags testClass
    */
   public function testClass() {
      $class = $this->testedClass->getClass();
      $this->given($class)->string($class::$rightname)->isEqualTo('flyvemdm:fleet');
   }

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $instance = $this->createInstance();
      $this->string($instance->getTypeName(1))->isEqualTo('Fleet')
         ->string($instance->getTypeName(3))->isEqualTo('Fleets');
   }

   /**
    * @tags testGetMenuPicture
    */
   public function testGetMenuPicture() {
      $class = $this->testedClass->getClass();
      $this->given($class)->string($class::getMenuPicture())->isEqualTo('fa-group');
   }

   /**
    * @tags testShowForm
    */
   public function testShowForm() {
      $instance = $this->createInstance();
      ob_start();
      $instance->showForm(1);
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)
         ->matches("#method='post' action='.+?\/plugins\/flyvemdm\/front\/fleet\.form\.php'#")
         ->contains("input type='hidden' name='entities_id' value='0'")
         ->contains("input type='hidden' name='is_recursive' value='0'")
         ->contains("type='text' name='name'")
         ->contains('input type="hidden" name="_glpi_csrf_token"');
   }

   protected function inputAddProvider() {
      return [
         'empty'          => [
            [],
            ['is_default' => 0, 'entities_id' => 0],
         ],
         'changed values' => [
            ['is_default' => 1, 'entities_id' => 1],
            ['is_default' => 1, 'entities_id' => 1],
         ],
      ];
   }

   /**
    * @dataProvider inputAddProvider
    * @tags testPrepareInputForAdd
    */
   public function testPrepareInputForAdd($input, $expected) {
      $instance = $this->createInstance();
      $keys = array_keys($expected);
      $result = $instance->prepareInputForAdd($input);
      $this->array($result)->hasKeys($keys);
      foreach ($expected as $key => $value) {
         $this->variable($result[$key])->isEqualTo($expected[$key]);
      }
   }

   protected function inputUpdateProvider() {
      return [
         'default values' => [
            ['is_default' => 0, 'entities_id' => 0],
            ['entities_id' => 0],
         ],
         'changed values' => [
            ['is_default' => 1, 'entities_id' => 0],
            ['entities_id' => 0],
         ],
      ];
   }

   /**
    * @dataProvider inputUpdateProvider
    * @tags testPrepareInputForUpdate
    */
   public function testPrepareInputForUpdate($input, $expected) {
      $instance = $this->createInstance();
      $keys = array_keys($expected);
      $result = $instance->prepareInputForUpdate($input);
      $this->array($result)->hasKeys($keys);
      foreach ($expected as $key => $value) {
         $this->variable($result[$key])->isEqualTo($expected[$key]);
      }
   }

   /**
    * @tags testDefineTabs
    */
   public function testDefineTabs() {
      // Test a managed fleet shows the policies tab
      $instance = $this->createInstance();
      $tabs = $instance->defineTabs();
      $key = 'PluginFlyvemdmFleet$main';
      $this->array($tabs)->hasKeys([$key, 1])
         ->string($tabs[$key])->isEqualTo('Fleet')
         ->string($tabs[1])->isEqualTo('Main');
      /*$instance->add([
         'name' => 'I manage devices',
         'entities_id' => $_SESSION['glpiactive_entity'],
      ]);
      $this->boolean($instance->isNewItem())->isFalse();
      $tabs = $instance->defineTabs();
      $this->array($tabs)->hasKey('PluginFlyvemdmTask$1');

      // Test a not manged fleet does not show a policies tab
      $instance = $this->newInstance();
      $instance->getFromDBByCrit([
         'AND' => [
            'entities_id' => $_SESSION['glpiactive_entity'],
            'is_default' => '1',
         ],
      ]);
      $this->boolean($instance->isNewItem())->isFalse();
      $tabs = $instance->defineTabs();
      $this->array($tabs)->notHasKey('PluginFlyvemdmTask$1');*/
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
               ->string[1]->isEqualTo('Fleet');
         })
         ->child[1](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_fleets')
               ->string[2]->isEqualTo('name')
               ->string[4]->isEqualTo('itemlink');
         })
         ->child[2](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_fleets')
               ->string[2]->isEqualTo('id')
               ->string[5]->isEqualTo('number');
         })
         ->child[3](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_tasks')
               ->string[2]->isEqualTo('items_id')
               ->string[4]->isEqualTo('specific');
         })
         ->child[4](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_tasks')
               ->string[2]->isEqualTo('itemtype')
               ->string[4]->isEqualTo('itemtypename');
         })
         ->child[5](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_fleets')
               ->string[2]->isEqualTo('is_default')
               ->string[4]->isEqualTo('bool');
         });
   }

   /**
    * @tags testGetTopic
    */
   public function testGetTopic() {
      $instance = $this->createInstance();
      $this->variable($instance->getTopic())->isNull();
   }

   /**
    * @tags testGetFleet
    */
   public function testGetFleet() {
      $instance = $this->createInstance();
      $this->variable($instance->getFleet())->isNull();
   }

   /**
    * @tags testGetPackages
    */
   public function testGetPackages() {
      $instance = $this->createInstance();
      $this->array($instance->getPackages())->isEmpty();
   }

   /**
    * @tags testGetFiles
    */
   public function testGetFiles() {
      $instance = $this->createInstance();
      $this->array($instance->getFiles())->isEmpty();
   }

   /**
    * @tags testFromDBByDefaultForEntity
    */
   public function testFromDBByDefaultForEntity() {
      $instance = $this->createInstance();
      $this->string($instance->getFromDBByDefaultForEntity())->isEqualTo('1');
   }

   /**
    * @tags testDefaultFleet
    */
   public function testDefaultFleet() {
      $class = $this->testedClass->getClass();
      $this->given($class)->variable($class::getDefaultFleet(-1))->isNull();
      $this->given($class)->object($class::getDefaultFleet())->isInstanceOf('\PluginFlyvemdmFleet');
   }
}