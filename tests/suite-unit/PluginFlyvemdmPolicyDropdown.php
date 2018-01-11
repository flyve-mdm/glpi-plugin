<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2017 by the FusionInventory Development Team.
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
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Test\CommonTestCase;
use PluginFlyvemdmPolicy;

class PluginFlyvemdmPolicyDropdown extends CommonTestCase {

   private $dataField = [
      'group'     => 'testGroup',
      'symbol'    => 'dropdownPolicy',
      'type_data' => '{"VAL_1":"value 1", "VAL_2":"value 2"}',
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
    * @return array
    */
   private function getTypeData() {
      $dataArray = json_decode($this->dataField['type_data'], true);
      $keys = array_keys($dataArray);
      return [$dataArray, $keys];
   }

   /**
    * @tags testCreatePolicy
    */
   public function testCreatePolicy() {
      list($policy) = $this->createNewPolicyInstance();
      $this->boolean($policy->integrityCheck("VAL_1", null, '0'))->isTrue();

      $this->boolean($policy->integrityCheck("VAL_INVALID", null, '0'))->isFalse();
   }

   /**
    * @tags testGetMqttMessage
    */
   public function testGetMqttMessage() {
      list($policy, $policyData) = $this->createNewPolicyInstance();
      // Test the mqtt message if the policy
      $array = $policy->getMqttMessage('VAL_1', null, '0');
      $symbol = $policyData->fields['symbol'];
      $this->array($array)->hasKey($symbol)->string($array[$symbol])->isEqualTo('VAL_1');

      $this->boolean($policy->getMqttMessage(null, null, '1'))->isFalse();
   }

   /**
    * @tags testShowValue
    */
   public function testShowValue() {
      list($policy) = $this->createNewPolicyInstance();
      list($data, $keys) = $this->getTypeData();
      $mockedClass = $this->newMockInstance('\PluginFlyvemdmTask');
      $mockedClass->getMockController()->getField = $keys[0];

      $this->string($policy->showValue($mockedClass))->isEqualTo($data[$keys[0]]);
   }

   /**
    * @tags testShowValueInput
    */
   public function testShowValueInput() {
      list($policy) = $this->createNewPolicyInstance();
      list($data, $keys) = $this->getTypeData();
      $this->string($policy->showValueInput())
         ->contains("<option value='" . $keys[0] . "'>" . $data[$keys[0]] . "</option><option value='" . $keys[1] . "'>" . $data[$keys[1]] . "</option>");
   }

   /**
    * @tags testTranslateData
    */
   public function testTranslateData() {
      list($policy) = $this->createNewPolicyInstance();
      $this->array($policy->translateData())->isEqualTo(json_decode($this->dataField['type_data'],
         true));
   }
}
