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

class PluginFlyvemdmPolicyDropdown extends CommonTestCase {

   public function setUp() {
      parent::setUp();
      self::setupGLPIFramework();
   }


   public function testCreatePolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $policyData->fields = [
            'group'     => 'testGroup',
            'symbol'    => 'dropdownPolicy',
            'type_data' => '{"VAL_1":"value 1", "VAL_2":"value 2"}',
            'unicity'   => '1',
      ];
      $policy = $this->newTestedInstance($policyData);

      $this->boolean($policy->integrityCheck("VAL_1", null, '0'))->isTrue();

      $this->boolean($policy->integrityCheck("VAL_INVALID", null, '0'))->isFalse();

      $array = $policy->getMqttMessage('VAL_1', null, '0');
      $this->array($array)->hasKey($policyData->fields['symbol']);
   }
}
