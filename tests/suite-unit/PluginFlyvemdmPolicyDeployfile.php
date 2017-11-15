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

class PluginFlyvemdmPolicyDeployfile extends CommonTestCase
{

   private $dataField = [
      'group' => 'file',
      'symbol' => 'deployFile',
      'type_data' => '',
      'unicity' => '0',
   ];

   public function setUp() {
      parent::setUp();
      self::setupGLPIFramework();
   }

   protected function validationProvider() {
      return [
         'Check values exist' => [
            'data' => [null, null, null],
            'expected' => [false, 'A destination and the remove on delete flag are mandatory'],
         ],
         'Check remove_on_delete is boolean' => [
            'data' => [['destination' => 'target', 'remove_on_delete' => ''], null, null],
            'expected' => [false, 'The remove on delete flag must be 0 or 1'],
         ],
         'Check the itemtype is a file' => [
            'data' => [['destination' => 'target', 'remove_on_delete' => 0], null, null],
            'expected' => [false, 'You must choose a file to apply this policy'],
         ],
         'Check the file exists' => [
            'data' => [
               ['destination' => 'target', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               '-1',
            ],
            'expected' => [false, 'The file does not exists'],
         ],
         'Check relative directory expression 1' => [
            'data' => [
               ['destination' => 'target/../file.txt', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               true,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check relative directory expression 2' => [
            'data' => [
               ['destination' => 'target/./file.txt', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check relative directory expression 3' => [
            'data' => [
               ['destination' => 'target/../', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check relative directory expression 4' => [
            'data' => [
               ['destination' => 'target/./', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check relative directory expression 5' => [
            'data' => [
               ['destination' => '/../file.txt', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check relative directory expression 6' => [
            'data' => [
               ['destination' => '/./file.txt', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check double directory separator' => [
            'data' => [
               ['destination' => 'target//file.txt', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check base path against well known paths 1' => [
            'data' => [
               ['destination' => '/file.ext', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check base path against well known paths 2' => [
            'data' => [
               ['destination' => 'file.ext', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check base path against well known paths 3' => [
            'data' => [
               ['destination' => '', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Check base path against well known paths 4' => [
            'data' => [
               ['destination' => '/folder/file.ext', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [false, 'invalid base path'],
         ],
         'Valid check 1' => [
            'data' => [
               ['destination' => '%SDCARD%/', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [true],
         ],
         'Valid check 2' => [
            'data' => [
               ['destination' => '%SDCARD%', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [true],
         ],
         'Valid check 3' => [
            'data' => [
               ['destination' => '%SDCARD%/file.ext', 'remove_on_delete' => 0],
               PluginFlyvemdmFile::class,
               1,
            ],
            'expected' => [true],
         ],
      ];
   }

   /**
    * @dataProvider validationProvider
    * @tags testCreatePolicy
    */
   public function testCreatePolicy($data, $expected) {
      list($policy) = $this->createNewPolicyInstance();
      if ($data[2] === true) {
         $item = $this->createFile();
         $data[2] = $item->getID();
      }
      $success = $policy->integrityCheck($data[0], $data[1], $data[2]);
      $this->boolean($success)->isEqualTo($expected[0]);
      if (!$expected[0]) {
         $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][0][0])->isEqualTo($expected[1]);
         unset($_SESSION["MESSAGE_AFTER_REDIRECT"]); // to clear the buffer
      }
   }

   private function createNewPolicyInstance() {
      $policyData = new PluginFlyvemdmPolicy();
      $policyData->fields = $this->dataField;
      $policy = $this->newTestedInstance($policyData);
      return [$policy, $policyData];
   }

   private function createFile() {
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

   /**
    * @tags testGetMqttMessage
    */
   public function testGetMqttMessage() {
      list($policy) = $this->createNewPolicyInstance();

      $this->boolean($policy->getMqttMessage(null, null, null))->isFalse();
      $item = $this->createFile();
      $value = '{"destination":"%SDCARD%/filename.ext","remove_on_delete":0}';
      $result = $policy->getMqttMessage($value, $item->getType(), $item->getID());
      $this->array($result)->hasKeys(['id', 'version', $this->dataField['symbol']])
         ->string($result['id'])->isEqualTo($item->getID())
         ->string($result['version'])->isEqualTo("0")
         ->string($result[$this->dataField['symbol']])->isEqualTo('%SDCARD%/filename.ext/');
   }

   /**
    * @tags testUnicityCheck
    */
   public function testUnicityCheck() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmFleet');
      $mockInstance->getMockController()->getID = 1;
      $this->boolean($policy->unicityCheck(['destination' => 'filename.ext'],
         PluginFlyvemdmFile::class, 1, $mockInstance))->isTrue();
      // TODO: finish this test
   }

   /**
    * @tags testConflictCheck
    */
   public function testConflictCheck() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmFleet');
      $mockInstance->getMockController()->getID = 1;
      $this->boolean($policy->conflictCheck(['destination' => 'filename.ext'],
         PluginFlyvemdmFile::class, 1, $mockInstance))->isTrue();
      // TODO: finish this test
   }

   /**
    * @tags testUnApply
    */
   public function testUnApply() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmFleet');
      $mockInstance->getMockController()->getID = 1;

      $this->boolean($policy->unapply($mockInstance, null, null, null))->isFalse();

      $value = '{"destination":"%SDCARD%/filename.ext","remove_on_delete":0}';
      $this->boolean($policy->unapply($mockInstance, $value, null, null))->isFalse();
      $this->boolean($policy->unapply($mockInstance, $value, PluginFlyvemdmFile::class,
         1))->isTrue();

      $value = '{"destination":"%SDCARD%/filename.ext","remove_on_delete":1}';
      $this->boolean($policy->unapply($mockInstance, $value, PluginFlyvemdmFile::class,
         -1))->isFalse();
      // TODO: finish this test
   }

   /**
    * @tags testShowValueInput
    */
   public function testShowValueInput() {
      list($policy) = $this->createNewPolicyInstance();
      $value = $policy->showValueInput();
      $this->string($value)
         ->contains('input type="hidden" name="items_id" value="0"')
         ->contains('input type="hidden" name="itemtype" value="PluginFlyvemdmFile"')
         ->contains('input type="text" name="value[destination]" value=""')
         ->contains('input type="hidden" name="value[remove_on_delete]" value="1"');
   }

   /**
    * @tags testShowValue
    */
   public function testShowValue() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmTask');
      $mockInstance->getMockController()->getField = 0;
      $mockInstance->getMockController()->getField[2] = 1;
      $mockInstance->getMockController()->getField[3] = '{"destination":"path"}';
      $this->string($policy->showValue($mockInstance))->isEqualTo(NOT_AVAILABLE);
      // TODO: make this test work directly by @tags with a clean DB.
      // $this->string($policy->showValue($mockInstance))->isEqualTo('path/filename.ext');
   }

   /**
    * @tags testPreprocessFormData
    */
   public function testPreprocessFormData() {
      list($policy) = $this->createNewPolicyInstance();
      $this->array($policy->preprocessFormData($input = ['invalidKey' => 'invalidValue']))->isEqualTo($input);
      $this->array($output = $policy->preprocessFormData([
         'destination_base' => 1,
         'value' => [
            'destination' => 'targetString',
         ],
      ]))->string($output['value']['destination'])->isEqualTo('%SDCARD%targetString');
   }
}