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
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmTask extends CommonTestCase {

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->login('glpi', 'glpi');
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
         'valid ' => [
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
         'invalid policy ID' => [
            'input' => [
               'value' => '0',
               'plugin_flyvemdm_policies_id' => $nonExistingPolicyId,
               'itemtype_applied' => \PluginFlyvemdmFleet::class,
               'items_id_applied' => $fleet->getID(),
               'itemtype' => '',
               'items_id' => '',
            ],
            'expected' => false,
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
            'expected' => false,
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
            'expected' => false,
         ],
      ];
   }

   /**
    * @dataProvider providerPrepareInputForAdd
    * @engine inline
    * @param array $input
    * @param boolean $expected
    */
   public function testPrepareInputForAdd($input, $expected) {
      $instance = $this->newTestedInstance();
      $output = $instance->prepareInputForAdd($input);
      if ($expected === false) {
         $this->boolean($output)->isFalse();
      } else {
         $this->array($output)->size->isEqualTo(count($expected));
         $this->array($output)->hasKeys(array_keys($expected));
      }
   }
}