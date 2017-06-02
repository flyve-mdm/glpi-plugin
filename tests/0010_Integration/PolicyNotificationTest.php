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
      $task = new PluginFlyvemdmTask();
      $taskId = $task->add([
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