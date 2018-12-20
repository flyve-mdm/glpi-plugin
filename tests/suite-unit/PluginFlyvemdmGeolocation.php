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

class PluginFlyvemdmGeolocation extends CommonTestCase {

   protected function providerAddInput() {
      return [
         'invalid computer' => [
            'input'    => [],
            'expected' => ['result' => false, 'message' => 'associated device is mandatory'],
         ],
         'invalid latitude' => [
            'input'    => ['computers_id' => '1'],
            'expected' => ['result' => false, 'message' => 'latitude and longitude are mandatory'],
         ],
         'invalid longitude' => [
            'input'    => ['computers_id' => '1', 'latitude' => 'na'],
            'expected' => ['result' => false, 'message' => 'latitude and longitude are mandatory'],
         ],
         'invalid latitude positive value' => [
            'input'    => ['computers_id' => '1', 'latitude' => '180.000001', 'longitude' => '90'],
            'expected' => ['result' => false, 'message' => 'latitude is invalid'],
         ],
         'invalid latitude negative value' => [
            'input'    => ['computers_id' => '1', 'latitude' => '-180.00001', 'longitude' => '90'],
            'expected' => ['result' => false, 'message' => 'latitude is invalid'],
         ],
         'invalid longitude positive value' => [
            'input'    => ['computers_id' => '1', 'latitude' => '90', 'longitude' => '180.000001'],
            'expected' => ['result' => false, 'message' => 'longitude is invalid'],
         ],
         'invalid longitude negative value' => [
            'input'    => ['computers_id' => '1', 'latitude' => '90', 'longitude' => '-180.00001'],
            'expected' => ['result' => false, 'message' => 'longitude is invalid'],
         ],
         'computer does not exist' => [
            'input'    => ['computers_id' => '-1', 'latitude' => 'na', 'longitude' => 'na'],
            'expected' => ['result' => false, 'message' => 'Device not found'],
         ],
         'agent does not exist' => [
            'input'    => ['_agents_id' => '-1'],
            'expected' => ['result' => false, 'message' => 'Device not found'],
            'extra'    => ['isAgent' => true]
         ],
      ];
   }
   /**
    * @dataProvider providerAddInput
    * @tags testGetprepareInputForAdd
    *
    * @param array $input
    * @param array $expected
    * @param array $extraArguments
    */
   public function testGetprepareInputForAdd(array $input, array $expected, array $extraArguments = []) {
      $instance = $this->newMockInstance(\PluginFlyvemdmGeolocation::class);
      if (isset($extraArguments['isAgent']) && $extraArguments['isAgent']) {
         $config = \Config::getConfigurationValues('flyvemdm', ['agent_profiles_id']);
         $_SESSION['glpiactiveprofile']['id'] = $config['agent_profiles_id'];
      } else {
         $_SESSION['glpiactiveprofile']['id'] = 1;
      }
      $result = $instance->prepareInputForAdd($input);
      if ($expected['result'] === false) {
         $this->assertInvalidResult($result, $expected['message']);
      } else {
         $this->variable($result)->isEqualTo($expected['result']);
      }
   }
}