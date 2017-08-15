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
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */
namespace tests\units;

use Glpi\Test\CommonTestCase;
use PluginFlyvemdmPolicy;

class PluginFlyvemdmPolicyInteger extends CommonTestCase {

   public function setUp() {
      parent::setUp();
      self::setupGLPIFramework();
   }

   public function testCreatePolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $policyData->fields = [
            'group'     => 'testGroup',
            'symbol'    => 'integerPolicy',
            'type_data' => '',
            'unicity'   => '1',
      ];
      $policy = $this->newTestedInstance($policyData);

      $this->boolean($policy->integrityCheck('a string', null, '0'))->isFalse();

      $this->boolean($policy->integrityCheck('42', null, '0'))->isTrue();

      $array = $policy->getMqttMessage('6', null, '0');
      $this->array($array)->hasKey($policyData->fields['symbol']);
   }

   public function testCreatePolicyWithConstraints() {
      $policyData = new PluginFlyvemdmPolicy();
      $policyData->fields = [
            'group'     => 'testGroup',
            'symbol'    => 'integerPolicy',
            'type_data' => '{"min":"-5", "max":"5"}',
            'unicity'   => '1'
      ];
      $policy = $this->newTestedInstance($policyData);

      $this->boolean($policy->integrityCheck('-10', null, '0'))->isFalse();

      $this->boolean($policy->integrityCheck('10', null, '0'))->isFalse();

      $this->boolean($policy->integrityCheck('3', null, '0'))->isTrue();

      $this->boolean($policy->integrityCheck('a string', null, '0'))->isFalse();
   }
}