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
 * @author    Domingo Oropeza
 * @copyright Copyright © 2018 Teclib
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmPolicyRemoveapplication extends CommonTestCase {

   private $dataField = [
      'group'     => 'application',
      'symbol'    => 'removeApp',
      'type_data' => '',
      'unicity'   => 0,
   ];

   /**
    * @param null|\PluginFlyvemdmPolicy $policyData
    * @return array
    */
   private function createNewPolicyInstance($policyData = null) {
      if (null === $policyData) {
         $policyData = new \PluginFlyvemdmPolicy();
         $policyData->fields = $this->dataField;
      }
      $policy = $this->newTestedInstance($policyData);
      return [$policy, $policyData];
   }

   /**
    * @return array
    */
   protected function providerIntegrityCheck() {
      return [
         'Check Invalid arguments 1' => [
            'data'     => ['value' => '', 'itemType' => '', 'itemId' => ''],
            'expected' => ['return' => false, 'message' => 'An application ID is required'],
         ],
         'Check Invalid arguments 2' => [
            'data'     => ['value' => 'fake.lorem.package', 'itemType' => 'ipsum', 'itemId' => 0],
            'expected' => ['return' => false],
         ],
         'Check Invalid arguments 3' => [
            'data'     => ['value' => 'fake.lorem.package', 'itemType' => '', 'itemId' => 1],
            'expected' => ['return' => false],
         ],
         'Valid check 1'             => [
            'data'     => ['value' => 'fake.lorem.package', 'itemType' => '', 'itemId' => 0],
            'expected' => ['return' => true],
         ],
      ];
   }

   /**
    * @dataProvider providerIntegrityCheck
    * @tags testIntegrityCheck
    *
    * @param array $data
    * @param array $expected
    */
   public function testIntegrityCheck(array $data, array $expected) {
      list($policy) = $this->createNewPolicyInstance();
      $success = $policy->integrityCheck($data['value'], $data['itemType'], $data['itemId']);
      $this->boolean($success)->isEqualTo($expected['return']);
      if (!$expected['return'] && isset($expected['message'])) {
         $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][0][0])->isEqualTo($expected['message']);
      }
      unset($_SESSION["MESSAGE_AFTER_REDIRECT"]);
   }

   /**
    * @return array
    */
   public function providerUnicityCheck() {
      return [
         'Check for invalid package' => [
            'data'     => ['value' => 'not_existing_package'],
            'expected' => ['return' => true],
         ],
         // TODO: For test this case a task must be created first
         /*'Check for valid package' => [
            'data'     => ['value' => 'package_name'],
            'expected' => ['return' => false],
         ],*/
      ];
   }

   /**
    * @dataProvider providerUnicityCheck
    * @tags testUnicityCheck
    *
    * @param array $data
    * @param array $expected
    */
   public function testUnicityCheck(array $data, array $expected) {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmFleet');
      $mockInstance->getMockController()->getID = 2;
      if (!$expected['return']) {
         $application = $this->createDummyPackage();
         $data['value'] = $application->getField('package_name');
      }
      $this->boolean($policy->unicityCheck($data['value'], '', '',
         $mockInstance))->isEqualTo($expected['return']);
   }

   /**
    * @tags testGetMqttMessage
    */
   public function testGetMqttMessage() {
      list($policy) = $this->createNewPolicyInstance();
      $this->boolean($policy->getBrokerMessage(null, null, null))->isFalse();

      $result = $policy->getBrokerMessage($packageName = 'fake.lorem.ipsum.package', '', 0);
      $this->array($result)->hasKeys([$this->dataField['symbol']])
         ->string($result[$this->dataField['symbol']])->isEqualTo($packageName);
   }

   public function providerFilterStatus() {
      $policyBaseTest = new PluginFlyvemdmPolicyBase();
      $statuses = $policyBaseTest->providerFilterStatus();
      $statuses = array_merge($statuses, [
         [
            'status' => 'waiting',
            'expected' => 'waiting'
         ],
      ]);
      $this->array($statuses)->size->isEqualTo(8);

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
      $policyDefinition->getFromDBBySymbol('deployApp');
      $policyObject = new \PluginFlyvemdmPolicyRemoveapplication($policyDefinition);
      $this->variable($policyObject->filterStatus($status))->isEqualTo($expected);
   }
}