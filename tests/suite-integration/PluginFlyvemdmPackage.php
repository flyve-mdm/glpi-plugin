<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2018 Teclib'
 * Copyright © 2010-2018 by the FusionInventory Development Team.
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
 * @author    Thierry Bugier
 * @copyright Copyright © 2018 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmPackage extends CommonTestCase {

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->setupGLPIFramework();
      $this->login('glpi', 'glpi');
   }

   public function afterTestMethod($method) {
      parent::afterTestMethod($method);
      \Session::destroy();
   }

   /**
    * @tags testCreateApplication
    */
   public function testCreateApplication() {
      global $DB;

      // Create an application (directly in DB) because we are not uploading any file
      $package = $this->createDummyPackage($_SESSION['glpiactive_entity']);
      $packageName = $package->getField('package_name');

      $policyDataDeploy = new \PluginFlyvemdmPolicy();
      $this->boolean($policyDataDeploy->getFromDBBySymbol('deployApp'))->isTrue();

      $policyDataRemove = new \PluginFlyvemdmPolicy();
      $this->boolean($policyDataRemove->getFromDBBySymbol('removeApp'))->isTrue();

      $fleet = $this->createFleet([
         'entities_id' => $_SESSION['glpiactive_entity'],
         'name'        => __CLASS__ . '::'. __FUNCTION__,
      ]);
      $task = $this->applyAddPackagePolicy($policyDataDeploy, $package, $fleet);
      $this->boolean($task->isNewItem())->isFalse();

      // delete the application
      $this->boolean($package->delete([
         'id' => $package->getID(),
      ]))->isTrue();

      // Check the policy is removed
      $itemtype = $package->getType();
      $itemId = $policyDataDeploy->getID();
      $rows = $task->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
      $this->integer(count($rows))->isEqualTo(0);

      // Check a removal policy is added
      $policyId = $policyDataRemove->getID();
      $rows = $task->find("`plugin_flyvemdm_policies_id`='$policyId' AND `value`='$packageName'");
      $this->integer(count($rows))->isGreaterThanOrEqualTo(1);

      // Check adding a deploy policy conflicts with removal one
      $task = $this->applyAddPackagePolicy($policyDataDeploy, $package, $fleet);
      $this->boolean($task->isNewItem())->isTrue();
   }

   /**
    * @param \PluginFlyvemdmPolicy       $policyData
    * @param \PluginFlyvemdmPackage      $package
    * @param \PluginFlyvemdmFleet        $fleet
    *
    * @return \PluginFlyvemdmTask
    */
   private function applyAddPackagePolicy(
      \PluginFlyvemdmPolicy $policyData,
      \PluginFlyvemdmPackage $package,
      \PluginFlyvemdmFleet $fleet
   ) {
      $value = new \stdClass();
      $value->remove_on_delete = '1';

      $task = new \PluginFlyvemdmTask();
      $task->add([
         'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
         'plugin_flyvemdm_policies_id' => $policyData->getID(),
         'value'                       => $value,
         'itemtype'                    => get_class($package),
         'items_id'                    => $package->getID(),
      ]);

      return $task;
   }
}
