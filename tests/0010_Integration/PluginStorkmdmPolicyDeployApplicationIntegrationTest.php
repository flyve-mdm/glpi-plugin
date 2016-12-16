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

class PluginStorkmdmPolicyDeployapplicationIntegrationTest extends RegisteredUserTestCase {

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

   public function testInitCreateApplication() {
      global $DB;

      // Create an application (directly in DB)
      $packageName = 'com.test.application';
      $packageTable = PluginStorkmdmPackage::getTable();
      $entityId = $_SESSION['glpiactive_entity'];
      $query = "INSERT INTO $packageTable (
         `name`,
         `alias`,
         `version`,
         `version_code`,
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
         '9475',
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

   /**
    * @depends testInitCreateFleet
    * @depends testInitCreateApplication
    */
   public function testGetAppDeploymentPolicy() {
      $policyData = new PluginStorkmdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('deployApp'));

      return $policyData;
   }

   /**
    * @depends testGetAppDeploymentPolicy
    * @depends testInitCreateApplication
    * @depends testInitCreateFleet
    */
   public function testApplyPolicyWithoutValue(PluginStorkmdmPolicy $policyData, PluginStorkmdmPackage $package, PluginStorkmdmFleet $fleet)
   {
      $value = new stdClass();

      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
            'plugin_storkmdm_policies_id' => $policyData->getID(),
            'itemtype'                    => 'PluginStorkmdmPackage',
            'items_id'                    => $package->getID()
      ]);
      $this->assertFalse($addSuccess);
   }

   /**
    * @depends testGetAppDeploymentPolicy
    * @depends testInitCreateApplication
    * @depends testInitCreateFleet
    */
   public function testApplyPolicyWithoutItemtype(PluginStorkmdmPolicy $policyData, PluginStorkmdmPackage $package, PluginStorkmdmFleet $fleet) {
      $value = new stdClass();
      $value->remove_on_delete = '1';

      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
            'plugin_storkmdm_policies_id' => $policyData->getID(),
            'value'                       => $value,
            'items_id'                    => $package->getID()
            ]);
      $this->assertFalse($addSuccess);
   }

   /**
    * @depends testGetAppDeploymentPolicy
    * @depends testInitCreateApplication
    * @depends testInitCreateFleet
    */
   public function testApplyPolicyWithoutItemId(PluginStorkmdmPolicy $policyData, PluginStorkmdmPackage $package, PluginStorkmdmFleet $fleet) {
      $value = new stdClass();
      $value->remove_on_delete = '1';

      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
            'plugin_storkmdm_policies_id' => $policyData->getID(),
            'value'                       => $value,
            'itemtype'                    => 'PluginStorkmdmPackage',
            ]);
      $this->assertFalse($addSuccess);
   }

   /**
    * @depends testGetAppDeploymentPolicy
    * @depends testInitCreateApplication
    * @depends testInitCreateFleet
    */
   public function testApplyPolicy(PluginStorkmdmPolicy $policyData, PluginStorkmdmPackage $package, PluginStorkmdmFleet $fleet) {
      global $DB;

      $table = PluginStorkmdmMqttupdatequeue::getTable();
      $this->assertTrue($DB->query("TRUNCATE TABLE `$table`"));

      $groupName = $policyData->getField('group');
      $policyFactory = new PluginStorkmdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($policyData->getID());
      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $fleetId = $fleet->getID();

      $value = new stdClass();
      $value->remove_on_delete = '1';

      $addSuccess = $fleet_policy->add([
         'plugin_storkmdm_fleets_id'   => $fleetId,
         'plugin_storkmdm_policies_id' => $policyData->getID(),
         'value'                       => $value,
         'items_id'                    => $package->getID(),
         'itemtype'                    => 'PluginStorkmdmPackage',
      ]);

      $mqttUpdateQueue = new PluginStorkmdmMqttupdatequeue();
      $rows = $mqttUpdateQueue->find("`group` = '$groupName'
            AND `plugin_storkmdm_fleets_id` = '$fleetId'
            AND `status` = 'queued'");
      $this->assertCount(1, $rows);
   }

   /**
    * @depends testGetAppDeploymentPolicy
    * @depends testInitCreateApplication
    * @depends testInitCreateFleet
    * @depends testApplyPolicy
    */
   public function testApplyAgainPolicy(PluginStorkmdmPolicy $policyData, PluginStorkmdmPackage $package, PluginStorkmdmFleet $fleet) {
      $policyFactory = new PluginStorkmdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($policyData->getID());
      $fleet_policy = new PluginStorkmdmFleet_Policy();

      $value = new stdClass();
      $value->remove_on_delete = '1';

      $addSuccess = $fleet_policy->add([
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
            'plugin_storkmdm_policies_id' => $policyData->getID(),
            'value'                       => $value,
            'items_id'                    => $package->getID(),
            'itemtype'                    => 'PluginStorkmdmPackage',
      ]);
      $this->assertFalse($addSuccess);
   }

   /**
    * @depends testGetAppDeploymentPolicy
    * @depends testInitCreateFleet
    */
   public function testUnapplyPolicy(PluginStorkmdmPolicy $policyData, PluginStorkmdmFleet $fleet) {
      global $DB;

      $table = PluginStorkmdmMqttupdatequeue::getTable();
      $this->assertTrue($DB->query("TRUNCATE TABLE `$table`"));

      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $fleet_policy->getFromDBForItems($fleet, $policyData);

      $deleteSuccess = $fleet_policy->delete([
            'id'        => $fleet_policy->getID(),
      ]);
   }
}