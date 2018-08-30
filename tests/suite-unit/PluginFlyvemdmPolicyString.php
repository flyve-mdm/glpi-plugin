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
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;
use PluginFlyvemdmPolicy;

class PluginFlyvemdmPolicyString extends CommonTestCase {

   private $dataField = [
      'group'     => 'testGroup',
      'symbol'    => 'stringPolicy',
      'type_data' => '',
      'unicity'   => '1',
   ];

   /**
    * @return array
    */
   private function createNewPolicyInstance() {
      $policyData = new \PluginFlyvemdmPolicy();
      $policyData->fields = $this->dataField;
      $policy = $this->newTestedInstance($policyData);
      return [$policy, $policyData];
   }

   /**
    * @tags testCreatePolicy
    */
   public function testCreatePolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $policyData->fields = $this->dataField;
      $policy = $this->newTestedInstance($policyData);

      $this->boolean($policy->integrityCheck('a little string', null, '0'))->isTrue();
   }

   /**
    * @tags testGetMqttMessage
    */
   public function testGetMqttMessage() {
      list($policy, $policyData) = $this->createNewPolicyInstance();
      // Test the mqtt message if the policy
      $array = $policy->getMqttMessage('a little string', null, '0');
      $symbol = $policyData->fields['symbol'];
      $this->array($array)->hasKey($symbol)->string($array[$symbol])->isEqualTo('a little string');
   }

   public function providerFilterStatus() {
      $policyBaseTest = new PluginFlyvemdmPolicyBase();
      $statuses= $policyBaseTest->providerFilterStatus();
      $this->array($statuses)->size->isEqualTo(7);

      $statuses = array_merge($statuses, [
         [
            'status' => 'invalid',
            'expected' => null
         ],
      ]);

      return $statuses;
   }

   /**
    * @dataProvider providerFilterStatus
    * @param string $status
    * @param string $expected
    */
   public function testFilterStatus($status, $expected) {
      $policyDefinition = new \PluginFlyvemdmPolicy();
      $policyDefinition->getFromDBBySymbol('passwordMinLength');
      $policyObject = new \PluginFlyvemdmPolicyString($policyDefinition);
      $this->variable($policyObject->filterStatus($status))->isEqualTo($expected);
   }
}
