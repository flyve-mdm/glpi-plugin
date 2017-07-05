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

class PolicyNotificationTest extends RegisteredUserTestCase
{

   public function testInitCreateFleet() {
      $fleet = new PluginFlyvemdmFleet();
      $fleet->add([
            'name'               => 'test fleet',
            'entities_id'        => $_SESSION['glpiactive_entity']
      ]);
      $this->assertFalse($fleet->isNewItem());

      return $fleet;
   }

   /**
    * @depends testInitCreateFleet
    */
   public function testApplyPolicy($fleet) {
      // Get a policy
      $policyData = new PluginFlyvemdmPolicy();
      $this->assertTrue($policyData->getFromDBByQuery("WHERE `symbol`='passwordEnabled'"));
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($policyData->getID());

      // Apply the policy to a fleet
      $fleetPolicy = new PluginFlyvemdmFleet_Policy();
      $fleetPolicyId = $fleetPolicy->add([
            'plugin_flyvemdm_fleets_id'      => $fleet->getID(),
            'plugin_flyvemdm_policies_id'    => $policyData->getID(),
            'value'                          => 'PASSWORD_NONE'
      ]);

      $groupName = $policyData->getField('group');
      $fleetId = $fleet->getID();
      $mqttUpdateQueue = new PluginFlyvemdmMqttupdatequeue();
      $rows = $mqttUpdateQueue->find("`group` = '$groupName'
                                      AND `plugin_flyvemdm_fleets_id` = '$fleetId'
                                      AND `status` = 'queued'");
      $this->assertCount(1, $rows);
   }

}