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

class PluginFlyvemdmPolicyRemoveFile extends CommonTestCase {
   public function beforeTestMethod($method) {
      $this->resetState();
      parent::beforeTestMethod($method);
      $this->setupGLPIFramework();
      $this->login('glpi', 'glpi');
   }

   /**
    *
    */
   public function testDeployRemoveFilePolicy() {
      global $DB;

      $destination = '%SDCARD%/path/to/file.pdf';

      // Create a file (directly in DB)
      $fileName = 'flyve-user-manual.pdf';
      $fileTable = \PluginFlyvemdmFile::getTable();
      $entityId = $_SESSION['glpiactive_entity'];
      $query = "INSERT INTO $fileTable (
         `name`,
         `source`,
         `entities_id`
      )
      VALUES (
         '$fileName',
         '2/12345678_flyve-user-manual.pdf',
         '$entityId'
      )";
      $DB->query($query);
      $mysqlError = $DB->error();
      $file = new \PluginFlyvemdmFile();
      $this->boolean($file->getFromDBByQuery("WHERE `name`='$fileName'"))->isTrue($mysqlError);

      // Create a fleet
      $fleet = $this->createFleet();

      $policyData = new \PluginFlyvemdmPolicy();
      $this->boolean($policyData->getFromDBBySymbol('removeFile'))->isTrue();
      $policyFactory = new \PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromPolicy($policyData);
      $this->object($policy)->isInstanceOf(\PluginFlyvemdmPolicyRemovefile::class);

      // Apply the policy to the fleet with incomplete data
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();
      $fleetFk = \PluginFlyvemdmFleet::getForeignKeyField();
      $fleet_policy = new \PluginFlyvemdmFleet_Policy();
      $fleet_policy->add([
         $policyFk => $policyData->getID(),
         'value'   => $destination
      ]);
      $this->boolean($fleet_policy->isNewItem())->isTrue();

      // Apply the policy with bad data
      $fleet_policy = new \PluginFlyvemdmFleet_Policy();
      $fleet_policy->add([
         $fleetFk    => $fleet->getID(),
         $policyFk   => '-1',
         'value'     => $destination
      ]);
      $this->boolean($fleet_policy->isNewItem())->isTrue();

      // Clean the Mqtt messages queu table
      $table = \PluginFlyvemdmMqttupdatequeue::getTable();
      $this->boolean($DB->query("TRUNCATE TABLE `$table`"))->isTrue();

      $groupName = $policyData->getField('group');
      $fleetId = $fleet->getID();

      // Apply the policy to the fleet
      $fleet_policy = new \PluginFlyvemdmFleet_Policy();
      $fleet_policy->add([
         $fleetFk   => $fleet->getID(),
         $policyFk  => $policyData->getID(),
         'value'    => $destination
      ]);
      $this->boolean($fleet_policy->isNewItem())->isFalse();

      // Check there is a MQTT message queued
      $mqttUpdateQueue = new \PluginFlyvemdmMqttupdatequeue();
      $rows = $mqttUpdateQueue->find("`group` = '$groupName'
                                       AND `plugin_flyvemdm_fleets_id` = '$fleetId'
                                       AND `status` = 'queued'");
      $this->integer(count($rows))->isEqualTo(1);

   }

   private function createFleet() {
      $fleet = $this->newMockInstance(\PluginFlyvemdmFleet::class, '\MyMock');
      $fleet->getMockController()->post_addItem = function() {};
      $fleet->add([
         'entities_id'     => $_SESSION['glpiactive_entity'],
         'name'            => 'a fleet'
      ]);
      $this->boolean($fleet->isNewItem())->isFalse();

      return $fleet;
   }
}
