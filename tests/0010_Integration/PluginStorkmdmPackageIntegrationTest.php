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

class PluginStorkmdmPackageIntegrationTest extends RegisteredUserTestCase
{

   public function testInitAddFleet() {
      $fleet = new PluginStorkmdmFleet();
      $fleet->add([
            'entities_id'     => $_SESSION['glpiactive_entity'],
            'name'            => 'a fleet'
      ]);
      $this->assertFalse($fleet->isNewItem());

      return $fleet;
   }

   public function testCreateApplication() {
      $package = new PluginStorkmdmPackage();
      global $DB;

      // Create an application (directly in DB) because we are not uploading any file
      $packageName = 'com.domain.author.application';
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

   public function testGetAppDeploymentPolicy() {
      $policyData = new PluginStorkmdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('deployApp'));

      return $policyData;
   }

   /**
    * @depends testGetAppDeploymentPolicy
    * @depends testCreateApplication
    * @depends testInitAddFleet
    */
   public function testApplyPolicy(PluginStorkmdmPolicy $policyData, PluginStorkmdmPackage $package, PluginStorkmdmFleet $fleet) {
      $value = new stdClass();
      $value->remove_on_delete = '1';

      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
            'plugin_storkmdm_policies_id' => $policyData->getID(),
            'value'                       => $value,
            'itemtype'                    => 'PluginStorkmdmPackage',
            'items_id'                    => $package->getID(),
      ]);
      $this->assertFalse($fleet_policy->isNewItem());

      return $fleet_policy;
   }

   /**
    * @depends testCreateApplication
    */
   public function testDeleteApplication(PluginStorkmdmPackage $package) {
      $this->assertTrue($package->delete([
            'id'           => $package->getID()
      ]));

      return $package;
   }

   /**
    * @depends testDeleteApplication
    * @depends testApplyPolicy
    */
   public function testAppliedPoliciesRemoved(PluginStorkmdmPackage $package, PluginStorkmdmFleet_Policy $fleet_policy) {
      $itemtype = $package->getType();
      $itemId = $package->getID();
      $rows = $fleet_policy->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
      $this->assertEquals(0, count($rows));
   }

}