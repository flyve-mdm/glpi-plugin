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
 * @author    Domingo Oropeza
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;


use atoum;

class PluginFlyvemdmCommon extends atoum
{
   protected function providerConvertToGib() {
      return [
         'B' => ['data' => 1, 'expected' => '1.00 B'],
         'KiB' => ['data' => 2048, 'expected' => '2.00 KiB'],
         'MiB' => ['data' => 2097152, 'expected' => '2.00 MiB'],
         'GiB' => ['data' => 2147483648, 'expected' => '2.00 GiB'],
         'TiB' => ['data' => 2199023255552, 'expected' => '2.00 TiB'],
         '1023B' => ['data' => 1023, 'expected' => '1&nbsp;023.00 B'],
         '2.3KiB' => ['data' => 2360, 'expected' => '2.30 KiB'],
         '13MiB' => ['data' => 13631488, 'expected' => '13.00 MiB'],
      ];
   }

   /**
    * @dataProvider providerConvertToGib
    * @tags testConvertToGiB
    * @param integer $data
    * @param string $expected
    */
   public function testConvertToGiB($data, $expected) {
      $this->given($this->newTestedInstance)
         ->string($this->testedInstance->convertToGib($data))->isEqualTo($expected);
   }

   /**
    * @tags testGetMassiveActions
    */
   public function testGetMassiveActions() {
      $class = $this->testedClass->getClass();
      $result = $class::getMassiveActions([]);
      $this->given($class)->string($result)->contains("autoOpen: false")
         ->contains("modal: true")->contains('title: "Actions"')
         ->contains("ajax/massiveaction.php");
   }

   /**
    * @tags testGetEnumValues
    */
   public function testGetEnumValues() {
      $class = $this->testedClass->getClass();
      $table = \PluginFlyvemdmAgent::getTable();
      $result = $class::getEnumValues($table, 'id');
      $this->given($class)->array($result)->isEmpty();
      $result = $class::getEnumValues($table, 'mdm_type');
      $this->given($class)->array($result)->isNotEmpty()->containsValues(['android', 'apple']);
   }

   /**
    * @tags testGenerateUUID
    */
   public function testGenerateUUID() {
      $this->given($this->newTestedInstance)
         ->string($this->testedInstance->generateUUID())
         ->matches('/\w{8}-\w{4}-4\w{3}-[8,9,A,B]\w{3}-\w{12}/i');
   }

}