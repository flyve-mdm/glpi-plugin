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
 * @copyright Copyright © 2018 Teclib
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmPolicyBoolean extends CommonTestCase {

   private $dataField = [
      'group'     => 'testGroup',
      'symbol'    => 'booleanPolicy',
      'type_data' => '',
      'unicity'   => '1',
      'value'     => '0',
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
      list($policy) = $this->createNewPolicyInstance();
      $this->boolean($policy->integrityCheck('0', null, '0'))->isTrue();

      $this->boolean($policy->integrityCheck('1', null, '0'))->isTrue();;

      $this->boolean($policy->integrityCheck('something', null, '0'))->isFalse();

      $this->boolean($policy->integrityCheck('0', \PluginFlyvemdmFile::class, '1'))->isFalse();

   }

   /**
    * @tags testGetMqttMessage
    */
   public function testGetMqttMessage() {
      list($policy, $policyData) = $this->createNewPolicyInstance();
      // Test the mqtt message if the policy
      $array = $policy->getBrokerMessage('0', null, '0');
      $symbol = $policyData->fields['symbol'];
      $this->array($array)->hasKey($symbol)->string($array[$symbol])->isEqualTo('false');

      $array = $policy->getBrokerMessage('1', null, '0');
      $symbol = $policyData->fields['symbol'];
      $this->array($array)->hasKey($symbol)->string($array[$symbol])->isEqualTo('true');

      $this->boolean($policy->getBrokerMessage('0', null, '1'))->isFalse();
   }

   /**
    * @tags testShowValue
    */
   public function testShowValue() {
      list($policy) = $this->createNewPolicyInstance();

      $mockedClass = $this->newMockInstance('\PluginFlyvemdmTask');
      $mockedClass->getMockController()->getField = 1;
      $mockedClass->getMockController()->getField[1] = 0;

      $this->string($policy->showValue($mockedClass))->isEqualTo('No');
      $this->string($policy->showValue($mockedClass))->isEqualTo('Yes');
   }

   /**
    * @tags testShowValueInput
    */
   public function testShowValueInput() {
      list($policy) = $this->createNewPolicyInstance();

      $this->string($policy->showValueInput('0'))
         ->contains("<option value='0' selected>No</option><option value='1'>Yes</option>");

      $this->string($policy->showValueInput('1'))
         ->contains("<option value='0'>No</option><option value='1' selected>Yes</option>");
   }

   public function providerFilterStatus() {
      $policyBaseTest = new PluginFlyvemdmPolicyBase();
      $statuses = $policyBaseTest->providerFilterStatus();
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
    * @tags testFilterStatus
    * @param string $status
    * @param string $expected
    */
   public function testFilterStatus($status, $expected) {
      $policyDefinition = new \PluginFlyvemdmPolicy();
      $policyDefinition->getFromDBBySymbol('storageEncryption');
      $policyObject = new \PluginFlyvemdmPolicyBoolean($policyDefinition);
      $this->variable($policyObject->filterStatus($status))->isEqualTo($expected);
   }
}
