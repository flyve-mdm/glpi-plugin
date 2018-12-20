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
 * @copyright Copyright © 2018 Teclib
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

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
         case 'testGetAgents':
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
         case 'testGetAgents':
            parent::afterTestMethod($method);
            $this->terminateSession();
            break;
      }
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
      $instance = $this->newTestedInstance();
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
    * @tags testPurgeteItem
    */
   public function testPurgeteItem() {
      $instance = \PluginFlyvemdmFleet::getDefaultFleet(0);
      $this->variable($instance)->isNotNull();
      $_SESSION['glpiactiveentities'] = [0];
      $this->boolean($instance->canPurgeItem())->isFalse();
   }

   /**
    * @tags testShowForm
    */
   public function testShowForm() {
      $instance = $this->newTestedInstance();
      ob_start();
      $instance->showForm(1);
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)
         ->matches("#method='post' action='.+?\/plugins\/flyvemdm\/front\/fleet\.form\.php'#")
         ->contains("input type='hidden' name='entities_id' value='0'")
         ->contains("name='is_recursive'")
         ->contains("type='text' name='name'")
         ->contains('input type="hidden" name="_glpi_csrf_token"');
   }

   protected function providerPrepareInputForAdd() {
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
    * @dataProvider providerPrepareInputForAdd
    * @tags testPrepareInputForAdd
    * @param array $input
    * @param array $expected
    */
   public function testPrepareInputForAdd($input, $expected) {
      $instance = $this->newTestedInstance();
      $keys = array_keys($expected);
      $result = $instance->prepareInputForAdd($input);
      $this->array($result)->hasKeys($keys);
      foreach ($expected as $key => $value) {
         $this->variable($result[$key])->isEqualTo($expected[$key]);
      }
   }

   protected function providerInputUpdate() {
      return [
         [
            'initial'   => ['is_default' => 0],
            'input'     => ['is_default' => 0, 'entities_id' => 0],
            'expected'  => ['entities_id' => 0],
         ],
         [
            'initial'   => ['is_default' => 0],
            'input'     => ['is_default' => 1, 'entities_id' => 0],
            'expected'  => ['entities_id' => 0],
         ],
         [
            'initial'   => ['is_default' => 0, 'is_recursive' => 0],
            'input'     => ['is_default' => 1, 'entities_id' => 0, 'is_recursive' => 1],
            'expected'  => ['entities_id' => 0, 'is_recursive' => 1],
         ],
         [
            'initial'   => ['is_default' => 1, 'is_recursive' => 0],
            'input'     => ['is_default' => 1, 'entities_id' => 0, 'is_recursive' => 1],
            'expected'  => ['entities_id' => 0],
         ],
      ];
   }

   /**
    * @dataProvider providerInputUpdate
    * @tags testPrepareInputForUpdate
    * @param array $initial
    * @param array $input
    * @param array $expected
    */
   public function testPrepareInputForUpdate($initial, $input, $expected) {
      $instance = $this->newTestedInstance();
      $instance->fields = $initial;
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
      $instance = $this->newTestedInstance();
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
    * @tags testGetTopic
    */
   public function testGetTopic() {
      $instance = $this->newTestedInstance();
      $this->variable($instance->getTopic())->isNull();
   }

   /**
    * @tags testGetFleet
    */
   public function testGetFleet() {
      $instance = $this->newTestedInstance();
      $this->variable($instance->getFleet())->isNull();
   }

   /**
    * @tags testGetPackages
    */
   public function testGetPackages() {
      $instance = $this->newTestedInstance();
      $this->array($instance->getPackages())->isEmpty();
   }

   /**
    * @tags testGetFiles
    */
   public function testGetFiles() {
      $instance = $this->newTestedInstance();
      $this->array($instance->getFiles())->isEmpty();
   }

   /**
    * @tags testFromDBByDefaultForEntity
    */
   public function testFromDBByDefaultForEntity() {
      $instance = $this->newTestedInstance();
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

   /**
    * @tags testGetAgents
    */
   public function testGetAgents() {
      $instance = $this->newTestedInstance();
      $instance->add([
         'name' => __FUNCTION__,
      ]);
      $this->boolean($instance->isNewItem())->isFalse();

      $agents = [];
      for ($i = 0; $i < 3; $i++) {
         $agent = $this->createAgent([]);
         $agent->update([
            'id' => $agent->getID(),
            'plugin_flyvemdm_fleets_id' => $instance->getID(),
         ]);
         $agents[$agent->getID()] = $agent;
      }
      $output = $instance->getAgents();
      $this->array($output)->size->isEqualTo(count($agents));
      foreach ($output as $agent) {
         $this->object($agent)->isInstanceOf(\PluginFlyvemdmAgent::class);
         $this->boolean(isset($agents[$agent->getID()]))->isTrue();
      }
   }
}