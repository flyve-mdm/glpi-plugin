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

class PluginFlyvemdmPolicyDeployfileIntegrationTest extends RegisteredUserTestCase {

   public function testInitCreateFleet() {
      // Create a fleet
      $entityId = $_SESSION['glpiactive_entity'];
      $fleet = new PluginFlyvemdmFleet();
      $fleet->add([
            'name'            => 'managed fleet',
            'entities_id'     => $entityId,
      ]);
      $this->assertFalse($fleet->isNewItem());
      return $fleet;
   }

   public function testInitGetDestination() {
      return "%SDCARD%/path/to/";
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
         `entities_id`,
         `version`
      )
      VALUES (
         '$fileName',
         '2/12345678_flyve-user-manual.pdf',
         '$entityId',
         '1'
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

   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitCreateFleet
    */
   public function testApplyPolicyWithoutValue(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet) {
      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policyData->getID(),
            'itemtype'                    => 'PluginFlyvemdmFile',
            'items_id'                    => $file->getID()
      ]);
      $this->assertFalse($addSuccess);
   }

   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitCreateFleet
    */
   public function testApplyPolicyWithoutDestination(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet) {
      $value = new stdClass();
      $value->remove_on_delete = '1';

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policyData->getID(),
            'itemtype'                    => 'PluginFlyvemdmFile',
            'items_id'                    => $file->getID(),
            'value'                       => json_encode($value, JSON_UNESCAPED_SLASHES)
      ]);
      $this->assertFalse($addSuccess);
   }

   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitCreateFleet
    * @depends testInitGetDestination
    */
   public function testApplyPolicyWithoutRemoveFlag(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet, $destination) {
      $value = new stdClass();
      $value->destination = $destination;

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policyData->getID(),
            'itemtype'                    => 'PluginFlyvemdmFile',
            'items_id'                    => $file->getID(),
            'value'                       => json_encode($value, JSON_UNESCAPED_SLASHES)
      ]);
      $this->assertFalse($addSuccess);
   }



   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitCreateFleet
    * @depends testInitGetDestination
    */
   public function testApplyPolicyWithoutItemtype(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet, $destination) {
      $value = new stdClass();
      $value->remove_on_delete = '1';
      $value->destination = $destination;

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policyData->getID(),
            'value'                       => json_encode($value, JSON_UNESCAPED_SLASHES),
            'items_id'                    => $file->getID()
      ]);
      $this->assertFalse($addSuccess);
   }

   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitCreateFleet
    * @depends testInitGetDestination
    */
   public function testApplyPolicyWithoutItemId(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet, $destination) {
      $value = new stdClass();
      $value->remove_on_delete = '1';
      $value->destination = $destination;

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policyData->getID(),
            'value'                       => json_encode($value, JSON_UNESCAPED_SLASHES),
            'itemtype'                    => 'PluginFlyvemdmFile',
      ]);
      $this->assertFalse($addSuccess);
   }


   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitCreateFleet
    * @depends testInitGetDestination
    */
   public function testApplyPolicy(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet, $destination) {
      global $DB;

      $table = PluginFlyvemdmMqttupdatequeue::getTable();
      $this->assertTrue($DB->query("TRUNCATE TABLE `$table`"));

      $groupName = $policyData->getField('group');
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($policyData->getID());
      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $fleetId = $fleet->getID();

      $value = new stdClass();
      $value->remove_on_delete = '1';
      $value->destination = $destination;

      $addSuccess = $fleet_policy->add([
         'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
         'plugin_flyvemdm_policies_id' => $policyData->getID(),
         'value'                       => $value,
         'itemtype'                    => 'PluginFlyvemdmFile',
         'items_id'                    => $file->getID()
      ]);

      $mqttUpdateQueue = new PluginFlyvemdmMqttupdatequeue();
      $rows = $mqttUpdateQueue->find("`group` = '$groupName'
            AND `plugin_flyvemdm_fleets_id` = '$fleetId'
            AND `status` = 'queued'");
      $this->assertCount(1, $rows);
   }

   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitCreateFleet
    * @depends testInitGetDestination
    * @testApplyPolicy
    */
   public function testApplyAgainPolicy(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFile $file, PluginFlyvemdmFleet $fleet, $destination) {
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($policyData->getID());
      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $value = new stdClass();
      $value->remove_on_delete = '1';
      $value->destination = $destination;

      $addSuccess = $fleet_policy->add([
            'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
            'plugin_flyvemdm_policies_id' => $policyData->getID(),
            'value'                       => $value,
            'itemtype'                    => 'PluginFlyvemdmFile',
            'items_id'                    => $file->getID()
      ]);
      $this->assertFalse($addSuccess);
   }

   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFleet
    * @depends testApplyPolicy
    */
   public function testUnapplyPolicy(PluginFlyvemdmPolicy $policyData, PluginFlyvemdmFleet $fleet) {
      global $DB;

      $table = PluginFlyvemdmMqttupdatequeue::getTable();
      $this->assertTrue($DB->query("TRUNCATE TABLE `$table`"));

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $fleet_policy->getFromDBForItems($fleet, $policyData);
      $fleetId = $fleet->getID();
      $groupName = $policyData->getField('group');

      $deleteSuccess = $fleet_policy->delete([
            'id'        => $fleet_policy->getID(),
      ]);

      $mqttUpdateQueue = new PluginFlyvemdmMqttupdatequeue();
      $rows = $mqttUpdateQueue->find("`group` = '$groupName'
            AND `plugin_flyvemdm_fleets_id` = '$fleetId'
            AND `status` = 'queued'");
      $this->assertCount(1, $rows);
   }
}