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

class PluginStorkmdmFileIntegrationTest extends RegisteredUserTestCase
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

   public function testInitCreateFile() {
      global $DB;

      // Create an file (directly in DB)
      $fileName = 'flyve-user-manual.pdf';
      $fileTable = PluginStorkmdmFile::getTable();
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
      $file = new PluginStorkmdmFile();
      $this->assertTrue($file->getFromDBByQuery("WHERE `name`='$fileName'"), $mysqlError);

      return $file;
   }

   public function testGetFileDeploymentPolicy() {
      $policyData = new PluginStorkmdmPolicy();
      $this->assertTrue($policyData->getFromDBBySymbol('deployFile'));

      return $policyData;
   }

   /**
    * @depends testGetFileDeploymentPolicy
    * @depends testInitCreateFile
    * @depends testInitAddFleet
    */
   public function testApplyPolicy(PluginStorkmdmPolicy $policyData, PluginStorkmdmFile $file, PluginStorkmdmFleet $fleet) {
      $value = new stdClass();
      $value->remove_on_delete = '1';
      $value->destination = '%SDCARD%/path/to/file.pdf';

      $fleet_policy = new PluginStorkmdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
            'plugin_storkmdm_fleets_id'   => $fleet->getID(),
            'plugin_storkmdm_policies_id' => $policyData->getID(),
            'value'                       => $value,
            'itemtype'                    => 'PluginStorkmdmFile',
            'items_id'                    => $file->getID()
      ]);
      $this->assertFalse($fleet_policy->isNewItem());

      return $fleet_policy;
   }

   /**
    * @depends testInitCreateFile
    */
   public function testDeleteFile(PluginStorkmdmFile $file) {
      $this->assertTrue($file->delete([
            'id'           => $file->getID()
      ]));

      return $file;
   }

   /**
    * @depends testDeleteFile
    * @depends testApplyPolicy
    */
   public function testAppliedPoliciesRemoved(PluginStorkmdmFile $file, PluginStorkmdmFleet_Policy $fleet_policy) {
      $itemtype = $file->getType();
      $itemId = $file->getID();
      $rows = $fleet_policy->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
      $this->assertEquals(0, count($rows));
   }

}
