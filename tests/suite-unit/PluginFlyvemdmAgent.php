<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2016 by the FusionInventory Development Team.
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
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Test\CommonTestCase;
use PluginFlyvemdmAgent as FlyvemdmAgent;

class PluginFlyvemdmAgent extends CommonTestCase
{

   /**
    * @param $method
    */
   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testShowForm':
         case 'testShowForFleet':
         case 'testShowDangerZone':
         case 'testPrepareInputForAdd':
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
         case 'testShowForFleet':
         case 'testShowDangerZone':
         case 'testPrepareInputForAdd':
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
      $this->testedClass->hasConstant('ENROLL_DENY');
      $this->testedClass->hasConstant('ENROLL_INVITATION_TOKEN');
      $this->testedClass->hasConstant('ENROLL_ENTITY_TOKEN');
      $this->testedClass->hasConstant('DEFAULT_TOKEN_LIFETIME');
      $this->given($this->testedClass)
         ->integer(FlyvemdmAgent::ENROLL_DENY)->isEqualTo(0)
         ->integer(FlyvemdmAgent::ENROLL_INVITATION_TOKEN)->isEqualTo(1)
         ->integer(FlyvemdmAgent::ENROLL_ENTITY_TOKEN)->isEqualTo(2)
         ->string(FlyvemdmAgent::DEFAULT_TOKEN_LIFETIME)->isEqualTo('P7D');
      $class = $this->testedClass->getClass();
      $this->given($class)->string($class::$rightname)->isEqualTo('flyvemdm:agent');
   }

   /**
    * @tags testGetEnumMdmType
    */
   public function testGetEnumMdmType() {
      $instance = $this->createInstance();
      $result = $instance->getEnumMdmType();
      $this->array($result)->hasKeys(['android', 'apple'])
         ->string($result['android'])->isEqualTo('Android')
         ->string($result['apple'])->isEqualTo('Apple');
   }

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $instance = $this->createInstance();
      $this->string($instance->getTypeName(1))->isEqualTo('Agent')
         ->string($instance->getTypeName(3))->isEqualTo('Agents');
   }

   /**
    * @tags testGetMenuPicture
    */
   public function testGetMenuPicture() {
      $instance = $this->createInstance();
      $this->string($instance->getMenuPicture())->isEqualTo('fa-tablet');
   }

   /**
    * @tags testGetRights
    */
   public function testGetRights() {
      $instance = $this->createInstance();
      $this->array($result = $instance->getRights())->containsValues([
         'Create',
         'Read',
         'Update',
         ['short' => 'Purge', 'long' => 'Delete permanently'],
      ]);
   }


   /**
    * @tags testDefineTabs
    */
   public function testDefineTabs() {
      $instance = $this->createInstance();
      $result = $instance->defineTabs();
      $this->array($result)->values
         ->string[0]->isEqualTo('Agent')
         ->string[1]->isEqualTo('Historical');
   }

//   /**
//    * @tags testGetTabNameForItem
//    */
//   public function testGetTabNameForItem() {
//      $instance = $this->createInstance();
//      $result = $instance->getTabNameForItem();
//   }

//   /**
//    * @tags testDisplayTabContentForItem
//    */
//   public function testDisplayTabContentForItem() {
//      $class = $this->testedClass->getClass();
//      $mockInstance = $this->newMockInstance('\PluginFlyvemdmAgent');
//      $mockInstance->getMockController()->showDangerZone = function() {};
//      $this->boolean($class::displayTabContentForItem($mockInstance))->isTrue();
//
//      $mockInstance = $this->newMockInstance('\PluginFlyvemdmFleet');
//      $mockInstance->getMockController()->showForFleet = function() {};
//      $this->boolean($class::displayTabContentForItem($mockInstance))->isTrue();
//   }

//   /**
//    * @tags testShowForm
//    */
//   public function testShowForm() {
//      $instance = $this->createInstance();
//      ob_start();
//      // TODO: have a fake agent registered in DB before this.
//      $instance->showForm(1);
//      $result = ob_get_contents();
//      ob_end_clean();
//      $this->string($result)
//         ->contains("method='post' action='-/plugins/flyvemdm/front/agent.form.php'")
//         ->contains("input type='hidden' name='entities_id' value='0'")
//         ->contains("type='text' name='name'")
//         ->contains('input type="hidden" name="computers_id"')
//         ->contains('input type="hidden" name="plugin_flyvemdm_fleets_id"')
//         ->contains('input type="hidden" name="_glpi_csrf_token"');
//   }

//   /**
//    * @tags testShowDangerZone
//    */
//   public function testShowDangerZone() {
//      $instance = $this->createInstance();
//      ob_start();
//      // TODO: have a fake agent registered in DB before this.
//      $instance->showDangerZone(1);
//      $result = ob_get_contents();
//      ob_end_clean();
//      $this->string($result)
//         ->contains("method='post' action='/plugins/flyvemdm/front/agent.form.php'")
//         ->contains("input type='checkbox' class='new_checkbox' name='lock'")
//         ->contains("input type='checkbox' class='new_checkbox' name='wipe'")
//         ->contains('iinput type="submit" value="Unenroll" name="unenroll"')
//         ->contains('input type="hidden" name="_glpi_csrf_token"');
//   }

   /**
    * @tags testShowForFleet
    */
   public function testShowForFleet() {
      $class = $this->testedClass->getClass();
      ob_start();
      $class::showForFleet(new \PluginFlyvemdmFleet());
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)->contains('There is no agent yet');
   }

//   /**
//    * @tags testCanViewItem
//    */
//   public function testCanViewItem() {
//   }
//
//   /**
//    * @tags testPrepareInputForAdd
//    */
//   public function testPrepareInputForAdd() {
//   }
//
//   /**
//    * @tags testPrepareInputForUpdate
//    */
//   public function testPrepareInputForUpdate() {
//   }
//
//   /**
//    * @tags testPost_getFromDB
//    */
//   public function testPost_getFromDB() {
//   }
//
//   /**
//    * @tags testPre_deleteItem
//    */
//   public function testPre_deleteItem() {
//   }
//
//   /**
//    * @tags testPost_updateItem
//    */
//   public function testPost_updateItem() {
//   }
//
//   /**
//    * @tags testPost_restoreItem
//    */
//   public function testPost_restoreItem() {
//   }
//
//   /**
//    * @tags testPost_purgeItem
//    */
//   public function testPost_purgeItem() {
//   }

   /**
    * @tags testGetSearchOptionsNew
    */
   public function testGetSearchOptionsNew() {
      $this->given($this->newTestedInstance)
         ->array($result = $this->testedInstance->getSearchOptionsNew())
         ->child[2](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_fleets')
               ->string[2]->isEqualTo('name')
               ->string[4]->isEqualTo('dropdown');
         })
         ->child[3](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_computers')
               ->string[2]->isEqualTo('id')
               ->string[4]->isEqualTo('dropdown');
         })
         ->child[4](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_computers')
               ->string[2]->isEqualTo('serial')
               ->string[4]->isEqualTo('dropdown');
         })
         ->child[5](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_users')
               ->string[2]->isEqualTo('id')
               ->string[6]->isEqualTo('dropdown');
         })
         ->child[6](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_fleets')
               ->string[2]->isEqualTo('id')
               ->string[5]->isEqualTo('number');
         })
         ->child[7](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_agents')
               ->string[2]->isEqualTo('last_contact')
               ->string[4]->isEqualTo('datetime');
         })
         ->child[8](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_users')
               ->string[2]->isEqualTo('realname')
               ->string[6]->isEqualTo('dropdown');
         })
         ->child[9](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_agents')
               ->string[2]->isEqualTo('version')
               ->string[4]->isEqualTo('string');
         })
         ->child[10](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_agents')
               ->string[2]->isEqualTo('is_online')
               ->string[4]->isEqualTo('boolean');
         })
         ->child[11](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_agents')
               ->string[2]->isEqualTo('mdm_type')
               ->string[4]->isEqualTo('boolean');
         })
         ->child[12](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_agents')
               ->string[2]->isEqualTo('has_system_permission')
               ->string[4]->isEqualTo('boolean');
         })
         ->child[13](function ($child) {
            $child->hasKeys(['table', 'field', 'datatype'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_agents')
               ->string[2]->isEqualTo('enroll_status')
               ->string[4]->isEqualTo('boolean');
         });
   }

//   /**
//    * @tags testAddDefaultJoin
//    */
//   public function testAddDefaultJoin() {
//   }
//
//   /**
//    * @tags testAddDefaultWhere
//    */
//   public function testAddDefaultWhere() {
//   }
//
//   /**
//    * @tags testGetSubscribedTopic
//    */
//   public function testGetSubscribedTopic() {
//   }
//
//   /**
//    * @tags testUpdateSubscription
//    */
//   public function testUpdateSubscription() {
//   }
//
//   /**
//    * @tags testGetByTopic
//    */
//   public function testGetByTopic() {
//   }
//
//   /**
//    * @tags testUnsubscribe
//    */
//   public function testUnsubscribe() {
//   }
//
//   /**
//    * @tags testGetTopicsToCleanup
//    */
//   public function testGetTopicsToCleanup() {
//   }
//
//   /**
//    * @tags testGetAgents
//    */
//   public function testGetAgents() {
//   }
//
//   /**
//    * @tags testGetPackages
//    */
//   public function testGetPackages() {
//   }
//
//   /**
//    * @tags testGetFiles
//    */
//   public function testGetFiles() {
//   }
//
//   /**
//    * @tags testGetFleet
//    */
//   public function testGetFleet() {
//   }
//
//   /**
//    * @tags testGetOwner
//    */
//   public function testGetOwner() {
//   }
//
//   /**
//    * @tags testGetComputer
//    */
//   public function testGetComputer() {
//   }

}