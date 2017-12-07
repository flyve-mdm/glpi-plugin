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
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Test\CommonTestCase;

class PluginFlyvemdmMqttUpdateQueue extends CommonTestCase
{
   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->setupGLPIFramework();
      $this->login('glpi', 'glpi');
   }

   public function afterTestMethod($method) {
      parent::afterTestMethod($method);
      \Session::destroy();
   }

   /**
    * @tags testApplyPolicy
    */
   public function testApplyPolicy() {
      $fleet = $this->createFleet();

      // Get a policy
      $policyData = new \PluginFlyvemdmPolicy();
      $this->boolean($policyData->getFromDBByQuery("WHERE `symbol`='passwordEnabled'"))->isTrue();
      $policyFactory = new \PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($policyData->getID());
      $this->variable($policy)->isNotNull();

      // Apply the policy to a fleet
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();
      $fleetFk = \PluginFlyvemdmFleet::getForeignKeyField();
      $task = new \PluginFlyvemdmTask();
      $task->add([
         $fleetFk => $fleet->getID(),
         $policyFk => $policyData->getID(),
         'value' => 'PASSWORD_NONE',
      ]);
      $this->boolean($task->isNewItem())->isFalse();

      $groupName = $policyData->getField('group');
      $fleetId = $fleet->getID();
      $mqttUpdateQueue = new \PluginFlyvemdmMqttupdatequeue();
      $rows = $mqttUpdateQueue->find("`group` = '$groupName'
                                      AND `$fleetFk` = '$fleetId'
                                      AND `status` = 'queued'");
      $this->integer(count($rows))->isEqualTo(1);
   }

   /**
    * @return object PluginFlyvemdmFleet mocked
    */
   private function createFleet() {
      $fleet = $this->newMockInstance(\PluginFlyvemdmFleet::class, '\MyMock');
      $fleet->getMockController()->post_addItem = function () {
      };
      $fleet->add([
         'entities_id' => $_SESSION['glpiactive_entity'],
         'name' => 'a fleet',
      ]);
      $this->boolean($fleet->isNewItem())->isFalse();

      return $fleet;
   }
}
