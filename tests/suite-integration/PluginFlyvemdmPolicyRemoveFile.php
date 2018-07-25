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

class PluginFlyvemdmPolicyRemoveFile extends CommonTestCase {

   private $defaultEntity = 0;

   /**
    * @tags testDeployRemoveFilePolicy
    */
   public function testDeployRemoveFilePolicy() {

      $destination = '%SDCARD%/path/to/file.pdf';

      // Create a file (directly in DB)
      $this->createDummyFile($this->defaultEntity);

      // Create a fleet
      $fleet = $this->createFleet([
         'entities_id' => $this->defaultEntity,
         'name'        => __CLASS__ . '::'. __FUNCTION__,
      ]);

      $policyData = new \PluginFlyvemdmPolicy();
      $this->boolean($policyData->getFromDBBySymbol('removeFile'))->isTrue();
      $policyFactory = new \PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromPolicy($policyData);
      $this->object($policy)->isInstanceOf(\PluginFlyvemdmPolicyRemovefile::class);

      // Apply the policy to the fleet with incomplete data
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();
      $task = new \PluginFlyvemdmTask();
      $task->add([
         $policyFk => $policyData->getID(),
         'value'   => $destination,
      ]);
      $this->boolean($task->isNewItem())->isTrue();

      // Apply the policy with bad data
      $task = new \PluginFlyvemdmTask();
      $task->add([
         'itemtype_applied'   => $fleet->getType(),
         'items_id_applied'   => $fleet->getID(),
         $policyFk => '-1',
         'value'   => $destination,
      ]);
      $this->boolean($task->isNewItem())->isTrue();

      // Apply the policy to the fleet
      $task = new \PluginFlyvemdmTask();
      $task->add([
         'itemtype_applied'   => $fleet->getType(),
         'items_id_applied'   => $fleet->getID(),
         $policyFk => $policyData->getID(),
         'value'   => $destination,
      ]);
      $this->boolean($task->isNewItem())->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

   }
}
