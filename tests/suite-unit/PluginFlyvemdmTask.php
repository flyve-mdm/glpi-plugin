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
 * @author    Domingo Oropeza
 * @copyright Copyright © 2018 Teclib
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmTask extends CommonTestCase {

   /**
    * @param $method
    */
   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testPrepareInputForAdd':
         case 'testPrepareInputForUpdate':
         case 'testDisplayTabContentForItem':
         case 'testPreprocessInput':
         case 'testGetTabNameForItem':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    * @param $method
    */
   public function afterTestMethod($method) {
      switch ($method) {
         case 'testPrepareInputForAdd':
         case 'testPrepareInputForUpdate':
         case 'testDisplayTabContentForItem':
         case 'testPreprocessInput':
         case 'testGetTabNameForItem':
            parent::afterTestMethod($method);
            $this->terminateSession();
            break;
      }
   }

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $instance = $this->newTestedInstance();
      $this->string($instance->getTypeName(1))->isEqualTo('Task')
         ->string($instance->getTypeName(3))->isEqualTo('Tasks');
   }

   public function providerCanCreate() {
      return [
         [
            'fleetRight'   => READ,
            'policyRight'  => READ,
            'expected'   => true,
         ],
         [
            'fleetRight' => 0,
            'policyRight'  => READ,
            'expected'   => true,
         ],
         [
            'fleetRight' => 0,
            'policyRight'  => 0,
            'expected'   => false,
         ],
         [
            'fleetRight' => READ,
            'policyRight'  => 0,
            'expected'   => false,
         ],
      ];
   }

   /**
    * @dataProvider providerCanCreate
    * @tags testCanCreate
    * @param integer $fleetRight
    * @param integer $policyRight
    * @param boolean $expected
    */
   public function testCanCreate($fleetRight, $policyRight, $expected) {
      $_SESSION['glpiactiveprofile'][\PluginFlyvemdmFleet::$rightname] = $fleetRight;
      $_SESSION['glpiactiveprofile'][\PluginFlyvemdmPolicy::$rightname] = $policyRight;
      $haveRight = \PluginFlyvemdmTask::canCreate();
      $this->boolean($haveRight)->isEqualTo($expected);
   }

   public function providerCanUpdate() {
      return [
         [
            'fleetRight'   => READ,
            'policyRight'  => READ,
            'expected'   => true,
         ],
         [
            'fleetRight' => 0,
            'policyRight'  => READ,
            'expected'   => true,
         ],
         [
            'fleetRight' => 0,
            'policyRight'  => 0,
            'expected'   => false,
         ],
         [
            'fleetRight' => READ,
            'policyRight'  => 0,
            'expected'   => false,
         ],
      ];
   }

   /**
    * @dataProvider providerCanUpdate
    * @tags testCanUpdate
    * @param integer $fleetRight
    * @param integer $policyRight
    * @param boolean $expected
    */
   public function testCanUpdate($fleetRight, $policyRight, $expected) {
      $_SESSION['glpiactiveprofile'][\PluginFlyvemdmFleet::$rightname] = $fleetRight;
      $_SESSION['glpiactiveprofile'][\PluginFlyvemdmPolicy::$rightname] = $policyRight;
      $haveRight = \PluginFlyvemdmTask::canUpdate();
      $this->boolean($haveRight)->isEqualTo($expected);
   }

   public function providerCanCreateItem() {
      return [
         [
            'fleetRight'   => READ,
            'policyRight'  => READ,
            'expected'   => true,
         ],
      ];
   }

   public function providerPrepareInputForAdd() {
      $existingPolicy = new \PluginFlyvemdmPolicy();
      $existingPolicy->getFromDbBySymbol('storageEncryption');
      $fleet = $this->createFleet([
         'name' => $this->getUniqueString(),
      ]);

      $existingPolicyId = $existingPolicy->getID();
      $nonExistingPolicyId = \PluginFlyvemdmCommon::getMax($existingPolicy, '', 'id') + 1;
      $nonExistingFleetId = $fleet->getID() + 1;

      $computer = new \Computer();
      $computer->add([
         'name' => 'a computer',
         'entities_id' => $_SESSION['glpiactive_entity'],
      ]);
      $computerId = $computer->getID();
      return [
         'valid' => [
            'input' => [
               'value' => '0',
               'plugin_flyvemdm_policies_id' => $existingPolicyId,
               'itemtype_applied' => \PluginFlyvemdmFleet::class,
               'items_id_applied' => $fleet->getID(),
               'itemtype' => '',
               'items_id' => '',
            ],
            'expected' => [
               'value' => '0',
               'plugin_flyvemdm_policies_id' => $existingPolicyId,
               'itemtype_applied' => \PluginFlyvemdmFleet::class,
               'items_id_applied' => $fleet->getID(),
               'itemtype' => '',
               'items_id' => '',
            ],
         ],
         'plugin_flyvemdm_policies_id not set' => [
            'input' => [
               'value' => '0',
               'itemtype_applied' => \PluginFlyvemdmFleet::class,
               'items_id_applied' => $fleet->getID(),
            ],
            'expected' => [
               'value'   => false,
               'message' => 'Notifiable and policy must be specified',
            ],
         ],
         'itemtype_applied not set' => [
            'input' => [
               'value'                       => '0',
               'plugin_flyvemdm_policies_id' => $nonExistingPolicyId,
               'items_id_applied'            => $fleet->getID(),
            ],
            'expected' => [
               'value'   => false,
               'message' => 'Notifiable and policy must be specified',
            ],
         ],
         'items_id_applied not set' => [
            'input' => [
               'value'                       => '0',
               'plugin_flyvemdm_policies_id' => $nonExistingPolicyId,
               'itemtype_applied'            => \PluginFlyvemdmFleet::class,
            ],
            'expected' => [
               'value'   => false,
               'message' => 'Notifiable and policy must be specified',
            ],
         ],
         'try on not managed fleet' => [
            'input' => [
               'value'                       => '0',
               'plugin_flyvemdm_policies_id' => $existingPolicyId,
               'itemtype_applied'            => \PluginFlyvemdmFleet::class,
               'items_id_applied'            => 1,
               'itemtype'                    => '',
               'items_id'                    => '',
            ],
            'expected' => [
               'value'   => false,
               'message' => 'Cannot apply a policy on a not managed fleet',
            ],
         ],
         'invalid value' => [
            'input' => [
               'value'                       => 'loremIpsum',
               'plugin_flyvemdm_policies_id' => $existingPolicyId,
               'itemtype_applied'            => \PluginFlyvemdmFleet::class,
               'items_id_applied'            => $fleet->getID(),
               'itemtype'                    => null,
               'items_id'                    => '',
            ],
            'expected' => [
               'value'   => false,
               'message' => 'Incorrect value for this policy',
            ],
         ],
         'invalid policy ID' => [
            'input' => [
               'value' => '0',
               'plugin_flyvemdm_policies_id' => $nonExistingPolicyId,
               'itemtype_applied' => \PluginFlyvemdmFleet::class,
               'items_id_applied' => $fleet->getID(),
               'itemtype' => '',
               'items_id' => '',
            ],
            'expected' => [
               'value'   => false,
               'message' => 'Policy not found',
            ],
         ],
         'invalid fleet ID' => [
            'input' => [
               'value' => '0',
               'plugin_flyvemdm_policies_id' => $existingPolicyId,
               'itemtype_applied' => \PluginFlyvemdmFleet::class,
               'items_id_applied' => $nonExistingFleetId,
               'itemtype' => '',
               'items_id' => '',
            ],
            'expected' => [
               'value'   => false,
               'message' => 'Cannot find the notifiable object',
            ],
         ],
         'invalid agent ID' => [
            'input' => [
               'value' => '0',
               'plugin_flyvemdm_policies_id' => $existingPolicyId,
               'itemtype_applied' => \Computer::class,
               'items_id_applied' => $computerId,
               'itemtype' => '',
               'items_id' => '',
            ],
            'expected' => [
               'value'   => false,
               'message' => 'This is not a notifiable object',
            ],
         ],
      ];
   }

   /**
    * @dataProvider providerPrepareInputForAdd
    * @tags testPrepareInputForAdd
    * @engine inline
    * @param array $input
    * @param boolean $expected
    */
   public function testPrepareInputForAdd($input, $expected) {
      $instance = $this->newTestedInstance();
      $output = $instance->prepareInputForAdd($input);
      if ($expected['value'] === false) {
         $this->boolean($output)->isFalse();
         $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][1][0])->isEqualTo($expected['message']);
         unset($_SESSION["MESSAGE_AFTER_REDIRECT"]); // to clear the buffer
      } else {
         $this->array($output)->hasKeys(array_keys($expected))
            ->size->isEqualTo(count($expected));
      }
   }

   public function providerPrepareInputForUpdate() {
      $existingPolicy = new \PluginFlyvemdmPolicy();
      $existingPolicy->getFromDbBySymbol('storageEncryption');
      $fleet = $this->createFleet([
         'name' => $this->getUniqueString(),
      ]);
      $existingPolicyId = $existingPolicy->getID();

      $task = new \PluginFlyvemdmTask();
      $task->add([
         'value' => '0',
         'plugin_flyvemdm_policies_id' => $existingPolicyId,
         'itemtype_applied' => \PluginFlyvemdmFleet::class,
         'items_id_applied' => $fleet->getID(),
         'itemtype' => '',
         'items_id' => '',
      ]);
      return [
         'valid' => [
            'input' => [
               'id' => $task->getID(),
               'value' => '0',
               'plugin_flyvemdm_policies_id' => $existingPolicyId,
               'items_id_applied' => $fleet->getID(),
               'itemtype' => '',
               'items_id' => '0',
            ],
            'expected' => [
               'id' => $task->getID(),
               'value' => '0',
               'plugin_flyvemdm_policies_id' => $existingPolicyId,
               'items_id_applied' => $fleet->getID(),
               'itemtype' => '',
               'items_id' => '0',
            ],
         ],
         'not notifiable item' => [
            'input' => [
               'id' => $task->getID(),
               'value' => '0',
               'plugin_flyvemdm_policies_id' => $existingPolicyId,
               'items_id_applied' => '1',
            ],
            'expected' => [
               'value' => false,
               'message' => 'Cannot apply a policy on this flyvemdm',
            ],
         ],
      ];
   }

   /**
    * @dataProvider providerPrepareInputForUpdate
    * @tags testPrepareInputForUpdate
    * @engine inline
    * @param array $input
    * @param boolean $expected
    */
   public function testPrepareInputForUpdate($input, $expected) {
      $instance = $this->newTestedInstance();
      $instance->getFromDB($input['id']);
      $output = $instance->prepareInputForUpdate($input);
      if ($expected['value'] === false) {
         $this->boolean($output)->isFalse();
         $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][1][0])->isEqualTo($expected['message']);
         unset($_SESSION["MESSAGE_AFTER_REDIRECT"]); // to clear the buffer
      } else {
         $this->array($output)->hasKeys(array_keys($expected))
            ->size->isEqualTo(count($expected));
      }
   }

   /**
    * @tags testUnpublishPolicy
    */
   public function testUnpublishPolicy() {
      $instance = $this->newTestedInstance();
      $result = $instance->unpublishPolicy(new \PluginFlyvemdmFleet);
      $this->variable($result)->isNull();
   }

   /**
    * @tags testDisplayTabContentForItem
    */
   public function testDisplayTabContentForItem() {
      $class = $this->testedClass->getClass();
      $result = $class::displayTabContentForItem(new \CommonGLPI());
      $this->variable($result)->isNull();
      ob_start();
      $class::displayTabContentForItem(new \PluginFlyvemdmFleet());
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)->isNotEmpty();
   }

   /**
    * @tags testPreprocessInput
    */
   public function testPreprocessInput() {
      $instance = $this->newTestedInstance();
      $result = $instance->preprocessInput(['plugin_flyvemdm_policies_id' => 6]);
      $this->array($result)->hasKey('plugin_flyvemdm_policies_id');
   }

   /**
    * @tags testGetTabNameForItem
    */
   public function testGetTabNameForItem() {
      // Invalid itemType
      $instance = $this->newTestedInstance();
      $result = $instance->getTabNameForItem(new \PluginFlyvemdmEntityConfig);
      $this->string($result)->isEmpty();
      // Valid itemType
      $result = $instance->getTabNameForItem(new \PluginFlyvemdmFleet);
      $this->string($result)->isEqualTo('Tasks');
   }

   /**
    * @tags testAddNeededInfoToInput
    */
   public function testAddNeededInfoToInput() {
      $instance = $this->newTestedInstance();
      // Default add values
      $result = $instance->addNeededInfoToInput(['_field' => 'value']);
      $this->array($result)->hasKeys(['itemtype','items_id','value'])->values
         ->string[0]->isEqualTo('value')
         ->integer[2]->isEqualTo(0)
         ->string[3]->isEqualTo('');
      $this->variable($result['itemtype'])->isNull(); // can't be asserted using previous code

      // Test no modifications
      $input = ['_field' => 'value', 'itemtype' => 'agent', 'items_id' => 1, 'value' => 'lorem',];
      $result = $instance->addNeededInfoToInput($input);
      $this->array($result)->isIdenticalTo($input);
   }

}