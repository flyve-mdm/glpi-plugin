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
use PluginFlyvemdmFile;

class PluginFlyvemdmPolicyDeployfile extends CommonTestCase {

   public function setUp() {
      parent::setUp();
      self::setupGLPIFramework();
   }

   protected function pathProvider() {
      return [
         [
            'data'      => [
               '%SDCARD%/'
            ],
            'expected'  => true,
         ],
         [
            'data'      => [
               '%SDCARD%/../'
            ],
            'expected'  => false,
         ],
         [
            'data'      => [
               '%SDCARD%'
            ],
            'expected'  => true,
         ],
         [
            'data'      => [
               '%SDCARD%/file.ext'
            ],
            'expected'  => true,
         ],
         [
            'data'      => [
               '%SDCARD%/../file.ext'
            ],
            'expected'  => false,
         ],
         [
            'data'      => [
               '/file.ext'
            ],
            'expected'  => false,
         ],
         [
            'data'      => [
               'file.ext'
            ],
            'expected'  => false,
         ],
         [
            'data'      => [
               ''
            ],
            'expected'  => false,
         ],
         [
            'data'      => [
               '/folder/file.ext'
            ],
            'expected'  => false,
         ],
         [
            'data'      => [
               '/%SDCARD%//file.ext'
            ],
            'expected'  => false,
         ],
         [
            'data'      => [
               '/../file.ext'
            ],
            'expected'  => false,
         ],
      ];
   }

   /**
    * @dataProvider pathProvider
    */
   public function testPathIntegrity($data, $expected) {
      $policy = $this->createPolicy();
      $item = $this->createFile($policy);

      $value = [
         'destination'        => $data[0],
         'remove_on_delete'   => 0,
      ];
      $success = $policy->integrityCheck($value, $item->getType(), $item->getId());
      $this->boolean($success)->isEqualTo($expected);
   }

   private function createPolicy() {
      $policyData = new PluginFlyvemdmPolicy();
      $policyData->fields = [
         'group'     => 'file',
         'symbol'    => 'deployFile',
         'type_data' => '',
         'unicity'   => '0',
      ];
      $policy = $this->newTestedInstance($policyData);

      return $policy;
   }

   private function createFile($policy) {
      global $DB;

      $table_file = PluginFlyvemdmFile::getTable();
      $query = "INSERT INTO `$table_file` (`name`) VALUES ('filename.ext')";
      $result = $DB->query($query);
      $this->boolean($result)->isTrue();

      $file = new PluginFlyvemdmFile();
      $file->getFromDB($DB->insert_id());
      $this->boolean($file->isNewItem())->isFalse();

      return $file;
   }
}