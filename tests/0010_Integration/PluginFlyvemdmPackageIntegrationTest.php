<?php
/*
 LICENSE

Copyright (C) 2016 Teclib'
Copyright (C) 2010-2016 by the FusionInventory Development Team.

This file is part of Flyve MDM Plugin for GLPI.

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
You should have received a copy of the GNU Affero General Public License
along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 ------------------------------------------------------------------------------
 @author    Thierry Bugier Pineau
 @copyright Copyright (c) 2016 Flyve MDM plugin team
 @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
 */

class PluginFlyvemdmPackageIntegrationTest extends RegisteredUserTestCase
{

   protected $applicationName;

   public function setUp() {
      parent::setUp();
      $this->applicationName = 'com.domain.author.application';
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

   public function testCreateApplication() {
      $package = new PluginFlyvemdmPackage();
      global $DB;

      // Create an application (directly in DB) because we are not uploading any file
      $this->applicationName = 'com.domain.author.application';
      $packageName = $this->applicationName;
      $packageTable = PluginFlyvemdmPackage::getTable();
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
      $package = new PluginFlyvemdmPackage();
      $this->assertTrue($package->getFromDBByQuery("WHERE `name`='$packageName'"), $mysqlError);

      return $package;
   }

   public function testGetAppDeploymentPolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('deployApp'));

      return $policyData;
   }

   public function testGetAppRemovalPolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('removeApp'));

      return $policyData;
   }

   /**
    * @depends testGetAppDeploymentPolicy
    * @depends testCreateApplication
    * @depends testInitAddFleet
    */
   public function testApplyPolicy(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmPackage $package, PluginFlyvemdmFleet $fleet) {
      $task = $this->applyAddPackagePolicy($policyData, $package, $fleet);
      $this->assertFalse($task->isNewItem());

      return $task;
   }

   /**
    * @depends testCreateApplication
    */
   public function testDeleteApplication(PluginFlyvemdmPackage $package) {
      $this->assertTrue($package->delete([
            'id'           => $package->getID()
      ]));

      return $package;
   }

   /**
    * @depends testDeleteApplication
    * @depends testApplyPolicy
    */
   public function testAppliedPoliciesRemoved(PluginFlyvemdmPackage $package, PluginFlyvemdmTask $task) {
      $itemtype = $package->getType();
      $itemId = $package->getID();
      $rows = $task->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
      $this->assertEquals(0, count($rows));
   }

   /**
    * @depends testGetAppRemovalPolicy
    * @depends testCreateApplication
    * @depends testApplyPolicy
    * @param PluginFlyvemdmPolicy $policyData
    * @param PluginFlyvemdmPackage $package
    * @param PluginFlyvemdmTask $task
    */
   public function testRemovePolicyAdded(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmPackage $package, PluginFlyvemdmTask $task) {
      $policyId = $policyData->getID();
      $packageName = $this->applicationName;
      $rows = $task->find("`plugin_flyvemdm_policies_id`='$policyId' AND `value`='$packageName'");
      $this->assertEquals(1, count($rows));
   }

   /**
    * @depends testGetAppDeploymentPolicy
    * @depends testCreateApplication
    * @depends testInitAddFleet
    * @depends testRemovePolicyAdded
    */
   public function testAddAndRemoveConflict(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmPackage $package, PluginFlyvemdmFleet $fleet) {
      $task = $this->applyAddPackagePolicy($policyData, $package, $fleet);
      $this->assertTrue($task->isNewItem());
   }

   protected function applyAddPackagePolicy(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmPackage $package, PluginFlyvemdmFleet $fleet) {
      $value = new stdClass();
      $value->remove_on_delete = '1';

      $task = new PluginFlyvemdmTask();
      $addSuccess = $task->add([
            'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policyData->getID(),
            'value'                       => $value,
            'itemtype'                    => 'PluginFlyvemdmPackage',
            'items_id'                    => $package->getID(),
      ]);

      return $task;
   }

}