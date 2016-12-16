<?php
/**
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
 @link      https://github.com/flyvemdm/backend
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

class PluginStorkmdmPolicyRemoveApplicationIntegrationTest extends RegisteredUserTestCase {

   public function testInitGetPackageName() {
      return 'com.domain.author.application';
   }

   /**
    * @depends testInitGetPackageName
    * @param unknown $packageName
    * @return PluginStorkmdmPackage
    */
   public function testInitCreateApplication($packageName) {
      global $DB;

      // Create an application (directly in DB) because we are not uploading any file
      $packageTable = PluginStorkmdmPackage::getTable();
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
      $package = new PluginStorkmdmPackage();
      $this->assertTrue($package->getFromDBByQuery("WHERE `name`='$packageName'"), $mysqlError);

      return $package;
   }

   public function testInitCreateFleet() {
      // Create a fleet
      $entityId = $_SESSION['glpiactive_entity'];
      $fleet = new PluginStorkmdmFleet();
      $fleet->add([
            'name'            => 'managed fleet',
            'entities_id'     => $entityId,
      ]);
      $this->assertFalse($fleet->isNewItem());

      return $fleet;
   }

   public function testGetPolicyData() {
      $policyData = new PluginStorkmdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('removeApp'));

      return $policyData;
   }

   /**
    * @depends testGetPolicyData
    */
   public function testGetRemoveApplicationPolicy($policyData) {
      $policyFactory = new PluginStorkmdmPolicyFactory();
      $policy = $policyFactory->createFromPolicy($policyData);
      $this->assertInstanceOf('PluginStorkmdmPolicyRemoveapplication', $policy);

      return $policy;
   }

   /**
    * @depends testInitCreateFleet
    * @depends testGetPolicyData
    * @depends testInitCreateApplication
    */
   public function testApplyPolicyWithEmptyValue(PluginStorkmdmFleet $fleet, PluginStorkmdmPolicy $policyData, PluginStorkmdmPackage $package) {
      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $fleet_policy->add([
            'plugin_storkmdm_policies_id' => $policyData->getID(),
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
            'value'                       => '',
      ]);
      $this->assertTrue($fleet_policy->isNewItem());
   }

   /**
    * @depends testInitCreateFleet
    * @depends testGetPolicyData
    * @depends testInitCreateApplication
    */
   public function testApplyPolicyWithoutValue(PluginStorkmdmFleet $fleet, PluginStorkmdmPolicy $policyData, PluginStorkmdmPackage $package) {
      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $fleet_policy->add([
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
            'plugin_storkmdm_policies_id' => $policyData->getID(),

      ]);
      $this->assertTrue($fleet_policy->isNewItem());
   }

   /**
    * @depends testInitCreateFleet
    * @depends testGetPolicyData
    * @depends testInitCreateApplication
    */
   public function testApplyPolicy(PluginStorkmdmFleet $fleet, PluginStorkmdmPolicy $policyData, PluginStorkmdmPackage $package) {
      global $DB;

      $table = PluginStorkmdmMqttupdatequeue::getTable();
      $this->assertTrue($DB->query("TRUNCATE TABLE `$table`"));

      $groupName = $policyData->getField('group');
      $fleetId = $fleet->getID();

      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $fleet_policy->add([
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
            'plugin_storkmdm_policies_id' => $policyData->getID(),
            'value'                       => $package->getField('name'),
      ]);
      $this->assertFalse($fleet_policy->isNewItem());

      $mqttUpdateQueue = new PluginStorkmdmMqttupdatequeue();
      $rows = $mqttUpdateQueue->find("`group` = '$groupName'
            AND `plugin_storkmdm_fleets_id` = '$fleetId'
            AND `status` = 'queued'");
      $this->assertCount(1, $rows);
   }
}