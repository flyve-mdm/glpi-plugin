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

use Glpi\test\CommonTestCase;

class PluginFlyvemdmPolicyBooleanTest extends CommonTestCase {

   public function setUp() {
      parent::setUp();
      self::setupGLPIFramework();
      self::login('glpi', 'glpi', true);
   }

   public function testCreatePolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $policyData->fields = [
            'group'     => 'testGroup',
            'symbol'    => 'booleanPolicy',
            'type_data' => '',
            'unicity'   => '1',
            'value'     => '0'
      ];
      $policy = new PluginFlyvemdmPolicyBoolean($policyData);
      $this->assertInstanceOf('PluginFlyvemdmPolicyBoolean', $policy);
      return $policy;
   }

   /**
    * @depends testCreatePolicy
    * @param PluginFlyvemdmPolicyInterface $policy
    */
   public function testIntegrityCheckWithFalse(PluginFlyvemdmPolicyInterface $policy) {
      $this->assertTrue($policy->integrityCheck('0', null, '0'));
   }

   /**
    * @depends testCreatePolicy
    * @param PluginFlyvemdmPolicyInterface $policy
    */
   public function testIntegrityCheckWithTrue(PluginFlyvemdmPolicyInterface $policy) {
      $this->assertTrue($policy->integrityCheck('1', null, '0'));
   }

   /**
    * @depends testCreatePolicy
    * @param PluginFlyvemdmPolicyInterface $policy
    */
   public function testIntegrityCheckWithNonBoolean(PluginFlyvemdmPolicyInterface $policy) {
      $this->assertFalse($policy->integrityCheck('something', null, '0'));
   }

   /**
    * @depends testCreatePolicy
    * @param PluginFlyvemdmPolicyInterface $policy
    */
   public function testIntegrityCheckWithItemtype(PluginFlyvemdmPolicyInterface $policy) {
      $this->assertFalse($policy->integrityCheck('0', 'PluginFlyvemdmFile', '1'));
   }

   /**
    * @depends testCreatePolicy
    * @param PluginFlyvemdmPolicyInterface $policy
    */
   public function testApplyPolicy(PluginFlyvemdmPolicyInterface $policy) {
      $array = $policy->getMqttMessage('0', null, '0');
      reset($array);
      $symbol = key($array);
      $this->assertAttributeEquals('booleanPolicy', 'symbol', $policy);
   }

}