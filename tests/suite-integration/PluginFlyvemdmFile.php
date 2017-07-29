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

class PluginFlyvemdmFile extends CommonTestCase {
   public function beforeTestMethod($method) {
      $this->resetState();
      parent::beforeTestMethod($method);
      $this->setupGLPIFramework();
      $this->login('glpi', 'glpi');
   }

   /**
    *
    */
   public function testApplyPolicy(\PluginFlyvemdmPolicy $deployPolicyData, \PluginFlyvemdmFile $file, \PluginFlyvemdmFleet $fleet) {
      $file = $this->createFile();
      $fileDestination = '%SDCARD%/path/to/';

      // Applya a policy on a file
      $deployPolicyData = $this->getFileDeploymentPolicy();
      $fleet = $this->createFleet();

      $fleet_policy = $this->ApplyAddFilePolicy($deployPolicyData, $file, $fleet, $fileDestination);
      $this->boolean($fleet_policy->isNewItem())->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // delete the file
      $this->boolean($file->delete([
         'id' => $file->getID()
      ]))->isTrue();

      // Test a file removal policy is created with expected content
      $removePolicyData = $this->getFileRemovalPolicy();
      $policyId = $removePolicyData->getID();
      $filePath = $fileDestination . $file->getField('name');
      $rows = $fleet_policy->find("`plugin_flyvemdm_policies_id`='$policyId' AND `value`='$filePath'");
      $this->integer(count($rows))->isEqualTo(1);

      // Test the applied policies are removed
      $itemtype = $file->getType();
      $itemId = $file->getID();
      $rows = $fleet_policy->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
      $this->integer(count($rows))->isEqualTo(0);

      // Test add policy fails when a removal policy exists
      $fleet_policy = $this->ApplyAddFilePolicy($deployPolicyData, $file, $fleet, $fileDestination);
      $this->boolean($fleet_policy->isNewItem())->isTrue();

   }

   private function createFile() {
      global $DB;

      // Create an file (directly in DB)
      $fileName = $this->getUniqueString() . '.pdf';
      $fileTable = \PluginFlyvemdmFile::getTable();
      $entityId = $_SESSION['glpiactive_entity'];
      $query = "INSERT INTO $fileTable (
         `name`,
         `source`,
         `entities_id`
      )
      VALUES (
         '$fileName',
         '2/12345678_$fileName.pdf',
         '$entityId'
      )";
      $DB->query($query);
      $mysqlError = $DB->error();
      $file = $this->newTestedInstance();
      $this->boolean($file->getFromDBByQuery("WHERE `name`='$fileName'"))->isTrue($mysqlError);

      return $file;
   }

   private function getFileDeploymentPolicy() {
      $policyData = new \PluginFlyvemdmPolicy();
      $this->boolean($policyData->getFromDBBySymbol('deployFile'))->isTrue();

      return $policyData;
   }

   private function getFileRemovalPolicy() {
      $policyData = new \PluginFlyvemdmPolicy();
      $this->boolean($policyData->getFromDBBySymbol('removeFile'))->isTrue();

      return $policyData;
   }

   private function createFleet() {
      $fleet = $this->newMockInstance(\PluginFlyvemdmFleet::class, '\MyMock');
      $fleet->getMockController()->post_addItem = function() {};
      $fleet->add([
         'entities_id'     => $_SESSION['glpiactive_entity'],
         'name'            => $this->getUniqueString(),
      ]);
      $this->boolean($fleet->isNewItem())->isFalse();

      return $fleet;
   }

   private function ApplyAddFilePolicy(\PluginFlyvemdmPolicy $policyData, \PluginFlyvemdmFile $file, \PluginFlyvemdmFleet $fleet, $filedestination) {
      $value = new \stdClass();
      $value->remove_on_delete = '1';
      $value->destination = $filedestination;

      $task = new \PluginFlyvemdmTask();
      $fleetFk = \PluginFlyvemdmFleet::getForeignKeyField();
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();
      $task->add([
         $fleetFk   => $fleet->getID(),
         $policyFk  => $policyData->getID(),
         'value'    => $value,
         'itemtype' => \PluginFlyvemdmFile::class,
         'items_id' => $file->getID()
      ]);

      return $task;
   }

}