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

class PluginFlyvemdmPolicyRemoveApplication extends CommonTestCase {

   private $defaultEntity = 0;

   /**
    * @tags testDeployRemoveApplicationPolicy
    */
   public function testDeployRemoveApplicationPolicy() {
      global $DB;

      $packageName = 'com.domain.author.application' . uniqid();

      // Create an application (directly in DB) because we are not uploading any file
      $packageTable = \PluginFlyvemdmPackage::getTable();
      $entityId = $this->defaultEntity;
      $query = "INSERT INTO $packageTable (
                  `name`,
                  `alias`,
                  `version`,
                  `filename`,
                  `entities_id`,
                  `dl_filename`,
                  `icon`
               )
               VALUES (
                  '$packageName',
                  'application',
                  '1.0.5',
                  '$entityId/123456789_application_105.apk',
                  '$entityId',
                  'application_105.apk',
                  ''
               )";
      $DB->query($query);
      $mysqlError = $DB->error();
      $package = new \PluginFlyvemdmPackage();
      $this->boolean($package->getFromDBByCrit(['name' => $packageName]))
         ->isTrue($mysqlError);

      // Create a fleet
      $fleet = $this->createFleet();

      // Get the policy
      $policyData = new \PluginFlyvemdmPolicy();
      $this->boolean($policyData->getFromDBBySymbol('removeApp'))->isTrue();
      $policyFactory = new \PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromPolicy($policyData);
      $this->object($policy)->isInstanceOf(\PluginFlyvemdmPolicyRemoveapplication::class);

      // Test Apply the policy to the fleet with an empty value
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();
      $fleetFk = \PluginFlyvemdmFleet::getForeignKeyField();
      $task = new \PluginFlyvemdmTask();
      $task->add([
         $policyFk => $policyData->getID(),
         $fleetFk  => $fleet->getID(),
         'value'   => '',
      ]);
      $this->boolean($task->isNewItem())->isTrue();

      // Test apply the policy to the fleet
      $groupName = $policyData->getField('group');
      $fleetId = $fleet->getID();
      $task = new \PluginFlyvemdmTask();
      $task->add([
         $policyFk => $policyData->getID(),
         $fleetFk  => $fleet->getID(),
         'value'   => $package->getField('name'),
      ]);
      $this->boolean($task->isNewItem())->isFalse();
   }

   /**
    * @return object PluginFlyvemdmFleet mocked
    */
   private function createFleet() {
      $fleet = $this->newMockInstance(\PluginFlyvemdmFleet::class, '\MyMock');
      $fleet->getMockController()->post_addItem = function () {};
      $fleet->add([
         'entities_id' => $this->defaultEntity,
         'name'        => $this->getUniqueString(),
      ]);
      $this->boolean($fleet->isNewItem())->isFalse();

      return $fleet;
   }
}
