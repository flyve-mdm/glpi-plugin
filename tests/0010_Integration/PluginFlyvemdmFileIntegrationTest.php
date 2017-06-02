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
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

class PluginFlyvemdmFileIntegrationTest extends RegisteredUserTestCase
{

   protected $fileDestination;

   public function setUp() {
      parent::setUp();
      $this->fileDestination = '%SDCARD%/path/to/';
   }

   public function testInitAddFleet() {
      $fleet = new PluginFlyvemdmFleet();
      $fleet->add([
            'entities_id'     => $_SESSION['glpiactive_entity'],
            'name'            => 'a fleet'
      ]);
      $this->assertFalse($fleet->isNewItem());

      return $fleet;
   }

   public function testInitCreateFile() {
      global $DB;

      // Create an file (directly in DB)
      $fileName = 'flyve-user-manual.pdf';
      $fileTable = PluginFlyvemdmFile::getTable();
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
      $file = new PluginFlyvemdmFile();
      $this->assertTrue($file->getFromDBByQuery("WHERE `name`='$fileName'"), $mysqlError);

      return $file;
   }

   public function testGetFileDeploymentPolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('deployFile'));

      return $policyData;
   }

   public function testGetFileRemovalPolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('removeFile'));

      return $policyData;
   }

   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitAddFleet
    */
   public function testApplyPolicy(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet) {
      $task = $this->ApplyAddFilePolicy($policyData, $file, $fleet);
      $this->assertFalse($task->isNewItem());

      return $task;
   }

   /**
    * @depends testInitCreateFile
    */
   public function testDeleteFile(PluginFlyvemdmFile $file) {
      $this->assertTrue($file->delete([
            'id'           => $file->getID()
      ]));

      return $file;
   }

   /**
    * @depends testInitCreateFile
    * @depends testApplyPolicy
    * @depends testDeleteFile
    */
   public function testAppliedPoliciesRemoved(PluginFlyvemdmFile $file, PluginFlyvemdmTask $task) {
      $itemtype = $file->getType();
      $itemId = $file->getID();
      $rows = $task->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
      $this->assertEquals(0, count($rows));
   }

   /**
    * @depends testGetFileRemovalPolicy
    * @depends testInitCreateFile
    * @depends testApplyPolicy
    * @depends testDeleteFile
    */
   public function testRemovePolicyAdded(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmTask $task) {
      $policyId = $policyData->getID();
      $filePath = $this->fileDestination . $file->getField('name');
      $rows = $task->find("`plugin_flyvemdm_policies_id`='$policyId' AND `value`='$filePath'");
      $this->assertEquals(1, count($rows));
   }

   /**
    *
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitAddFleet
    * @depends testRemovePolicyAdded
    */
   public function testAddAndRemoveConflict(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet) {
      $task = $this->ApplyAddFilePolicy($policyData, $file, $fleet);
      $this->assertTrue($task->isNewItem());
   }

   protected function ApplyAddFilePolicy(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet) {
      $value = new stdClass();
      $value->remove_on_delete = '1';
      $value->destination = $this->fileDestination;

      $task = new PluginFlyvemdmTask();
      $addSuccess = $task->add([
            'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policyData->getID(),
            'value'                       => $value,
            'itemtype'                    => 'PluginFlyvemdmFile',
            'items_id'                    => $file->getID()
      ]);

      return $task;
   }
}
