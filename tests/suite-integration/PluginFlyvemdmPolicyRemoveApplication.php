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

class PluginFlyvemdmPolicyRemoveApplication extends CommonTestCase {

   public function beforeTestMethod($method) {
      $this->resetState();
      parent::beforeTestMethod($method);
      $this->setupGLPIFramework();
      $this->login('glpi', 'glpi');
   }

   public function testDeployRemoveApplicationPolicy() {
      global $DB;

      $packageName = 'com.domain.author.application';

      // Create an application (directly in DB) because we are not uploading any file
      $packageTable = \PluginFlyvemdmPackage::getTable();
      $entityId = $_SESSION['glpiactive_entity'];
      $query = "INSERT INTO $packageTable (
                  `name`,
                  `alias`,
                  `version`,
                  `filename`,
                  `filesize`,
                  `entities_id`,
                  `dl_filename`,
                  `icon`
               )
               VALUES (
                  '$packageName',
                  'application',
                  '1.0.5',
                  '$entityId/123456789_application_105.apk',
                  '1048576',
                  '$entityId',
                  'application_105.apk',
                  ''
               )";
      $DB->query($query);
      $mysqlError = $DB->error();
      $package = new \PluginFlyvemdmPackage();
      $this->boolean($package->getFromDBByQuery("WHERE `name`='$packageName'"))->isTrue($mysqlError);

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
      $fleet_policy = new \PluginFlyvemdmFleet_Policy();
      $fleet_policy->add([
         $policyFk   => $policyData->getID(),
         $fleetFk    => $fleet->getID(),
         'value'     => '',
      ]);
      $this->boolean($fleet_policy->isNewItem())->isTrue();

      // Clear data in the table MqttUpdateQueue
      $table = \PluginFlyvemdmMqttupdatequeue::getTable();
      $this->boolean($DB->query("TRUNCATE TABLE `$table`"))->isTrue();

      // Test apply the policy to the fleet
      $groupName = $policyData->getField('group');
      $fleetId = $fleet->getID();
      $fleet_policy = new \PluginFlyvemdmFleet_Policy();
      $fleet_policy->add([
         $policyFk   => $policyData->getID(),
         $fleetFk    => $fleet->getID(),
         'value'     => $package->getField('name'),
      ]);
      $this->boolean($fleet_policy->isNewItem())->isFalse();

      // Check an mqtt message is queued
      $mqttUpdateQueue = new \PluginFlyvemdmMqttupdatequeue();
      $rows = $mqttUpdateQueue->find("`group` = '$groupName'
                                       AND `$fleetFk` = '$fleetId'
                                       AND `status` = 'queued'");
      $this->integer(count($rows))->isEqualTo(1);
   }

   private function createFleet() {
      $fleet = $this->newMockInstance(\PluginFlyvemdmFleet::class, '\MyMock');
      $fleet->getMockController()->post_addItem = function() {};
      $fleet->add([
         'entities_id'     => $_SESSION['glpiactive_entity'],
         'name'            => $this->getUniqueString()
      ]);
      $this->boolean($fleet->isNewItem())->isFalse();

      return $fleet;
   }
}
