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

class PluginFlyvemdmAgent extends CommonTestCase {

   /**
    * @param $method
    */
   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testShowForm':
         case 'testShowForFleet':
         case 'testShowDangerZone':
         case 'testPrepareInputForAdd':
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
         case 'testShowForm':
         case 'testShowForFleet':
         case 'testShowDangerZone':
         case 'testPrepareInputForAdd':
         case 'testGetAgents':
            parent::afterTestMethod($method);
            \Session::destroy();
            break;
      }
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
         ->integer(\PluginFlyvemdmAgent::ENROLL_DENY)->isEqualTo(0)
         ->integer(\PluginFlyvemdmAgent::ENROLL_INVITATION_TOKEN)->isEqualTo(1)
         ->integer(\PluginFlyvemdmAgent::ENROLL_ENTITY_TOKEN)->isEqualTo(2)
         ->string(\PluginFlyvemdmAgent::DEFAULT_TOKEN_LIFETIME)->isEqualTo('P7D');
      $class = $this->testedClass->getClass();
      $this->given($class)->string($class::$rightname)->isEqualTo('flyvemdm:agent');
   }

   /**
    * @tags testGetEnumMdmType
    */
   public function testGetEnumMdmType() {
      $instance = $this->newTestedInstance();
      $result = $instance->getEnumMdmType();
      $this->array($result)->hasKeys(['android', 'apple'])
         ->string($result['android'])->isEqualTo('Android')
         ->string($result['apple'])->isEqualTo('Apple');
   }

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $instance = $this->newTestedInstance();
      $this->string($instance->getTypeName(1))->isEqualTo('Agent')
         ->string($instance->getTypeName(3))->isEqualTo('Agents');
   }

   /**
    * @tags testGetMenuPicture
    */
   public function testGetMenuPicture() {
      $instance = $this->newTestedInstance();
      $this->string($instance->getMenuPicture())->isEqualTo('fa-tablet');
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
    * @tags testDefineTabs
    */
   public function testDefineTabs() {
      $instance = $this->newTestedInstance();
      $result = $instance->defineTabs();
      $this->array($result)->values
         ->string[0]->isEqualTo('Agent')
         ->string[1]->isEqualTo('Historical');
   }

   /**
    * @tags testGetTabNameForItem
    */
   /*public function testGetTabNameForItem() {
      $instance = $this->newTestedInstance();
      $result = $instance->getTabNameForItem();
   }*/

   /**
    * @tags testDisplayTabContentForItem
    */
   /*public function testDisplayTabContentForItem() {
      $class = $this->testedClass->getClass();
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmAgent');
      $mockInstance->getMockController()->showDangerZone = function() {};
      $this->boolean($class::displayTabContentForItem($mockInstance))->isTrue();

      $mockInstance = $this->newMockInstance('\PluginFlyvemdmFleet');
      $mockInstance->getMockController()->showForFleet = function() {};
      $this->boolean($class::displayTabContentForItem($mockInstance))->isTrue();
   }*/

   /**
    * @tags testShowForm
    */
   /*public function testShowForm() {
      $instance = $this->newTestedInstance();
      ob_start();
      // TODO: have a fake agent registered in DB before this.
      $instance->showForm(1);
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)
         ->contains("method='post' action='-/plugins/flyvemdm/front/agent.form.php'")
         ->contains("input type='hidden' name='entities_id' value='0'")
         ->contains("type='text' name='name'")
         ->contains('input type="hidden" name="computers_id"')
         ->contains('input type="hidden" name="plugin_flyvemdm_fleets_id"')
         ->contains('input type="hidden" name="_glpi_csrf_token"');
   }*/

   /**
    * @tags testShowDangerZone
    */
   /*public function testShowDangerZone() {
      $instance = $this->newTestedInstance();
      ob_start();
      // TODO: have a fake agent registered in DB before this.
      $instance->showDangerZone(1);
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)
         ->contains("method='post' action='/plugins/flyvemdm/front/agent.form.php'")
         ->contains("input type='checkbox' class='new_checkbox' name='lock'")
         ->contains("input type='checkbox' class='new_checkbox' name='wipe'")
         ->contains('input type="submit" value="Unenroll" name="unenroll"')
         ->contains('input type="hidden" name="_glpi_csrf_token"');
   }*/

   /**
    * @tags testShowForFleet
    */
   public function testShowForFleet() {
      ob_start();
      \PluginFlyvemdmAgent::showForFleet(new \PluginFlyvemdmFleet());
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)->contains('There is no agent yet');
   }

   public function testCanViewItem() {
      // Simulate a profile different of guest
      $config = \Config::getConfigurationValues('flyvemdm', ['guest_profiles_id']);
      $_SESSION['glpiactiveprofile']['id'] = $config['guest_profiles_id'] + 1;

      $testedInstance = $this->newTestedInstance;
      $this->boolean($testedInstance->canViewItem())->isFalse();

      // Simulate a profile equal to guest
      $_SESSION['glpiactiveprofile']['id'] = $config['guest_profiles_id'];
      $testedInstance = $this->newTestedInstance;
      $this->boolean($testedInstance->canViewItem())->isFalse();
   }

   /**
    * @engine inline
    */
   public function testGetTopicsToCleanup() {
      $expected = array_merge(CommonTestCase::commandList(), CommonTestCase::policyList());
      $topics = \PluginFlyvemdmAgent::getTopicsToCleanup();
      $this->array($topics)->size->isEqualTo(count($expected));
      $this->array($topics)->containsValues(
         $expected,
         "Not found policies" . PHP_EOL . json_encode(array_diff($topics, $expected), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
      );
   }

   /**
    * @tags testGetAgents
    */
   public function testGetAgents() {
      $instance = $this->createAgent([]);
      $agents = [$instance->getID() => $instance];
      $output = $instance->getAgents();
      $this->array($output)->size->isEqualTo(count($agents));
      foreach ($output as $agent) {
         $this->object($agent)->isInstanceOf(\PluginFlyvemdmAgent::class);
         $this->boolean(isset($agents[$agent->getID()]))->isTrue();
      }
   }
}
