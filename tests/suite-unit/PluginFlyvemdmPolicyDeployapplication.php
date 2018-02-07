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
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Test\CommonTestCase;
use PluginFlyvemdmPackage;

class PluginFlyvemdmPolicyDeployapplication extends CommonTestCase {

   private $dataField = [
      'group'     => 'application',
      'symbol'    => 'deployApp',
      'type_data' => '',
      'unicity'   => 0,
   ];

   private $packageName = 'com.domain.author.application.apk';
   private $filename = 'application.apk';

   /**
    * @return array
    */
   private function createNewPolicyInstance() {
      $policyData = new \PluginFlyvemdmPolicy();
      $policyData->fields = $this->dataField;
      $policy = $this->newTestedInstance($policyData);
      return [$policy, $policyData];
   }

   protected function validationProvider() {
      return [
         'Check values exist'                  => [
            'data'     => [null, null, null],
            'expected' => [false, 'The remove on delete flag is mandatory'],
         ],
         'Check remove_on_delete is boolean'   => [
            'data'     => [['remove_on_delete' => ''], null, null],
            'expected' => [false, 'The remove on delete flag must be 0 or 1'],
         ],
         'Check the itemtype is a application' => [
            'data'     => [['remove_on_delete' => 0], null, null],
            'expected' => [false, 'You must choose an application to apply this policy'],
         ],
         'Check the app exists'                => [
            'data'     => [
               ['remove_on_delete' => 0],
               PluginFlyvemdmPackage::class,
               '-1',
            ],
            'expected' => [false, 'The application does not exists'],
         ],
         'Valid check 1'                       => [
            'data'     => [
               ['remove_on_delete' => 0],
               PluginFlyvemdmPackage::class,
               true,
            ],
            'expected' => [true],
         ],
      ];
   }

   /**
    * @dataProvider validationProvider
    * @tags testCreatePolicy
    * @param $data
    * @param $expected
    */
   public function testCreatePolicy($data, $expected) {
      list($policy) = $this->createNewPolicyInstance();
      if ($data[2] === true) {
         $item = $this->createAppInDB();
         $data[2] = $item->getID();
      }
      $success = $policy->integrityCheck($data[0], $data[1], $data[2]);
      $this->boolean($success)->isEqualTo($expected[0]);
      if (!$expected[0]) {
         $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][0][0])->isEqualTo($expected[1]);
         unset($_SESSION["MESSAGE_AFTER_REDIRECT"]); // to clear the buffer
      }
   }

   /**
    * Create an application (directly in DB) because we are not uploading any file
    * @return PluginFlyvemdmPackage
    */
   private function createAppInDB() {
      global $DB;

      $uniqid = uniqid();
      $table_file = PluginFlyvemdmPackage::getTable();
      $query = "INSERT INTO `$table_file` (
        `package_name`,
        `alias`,
        `version`,
        `filename`,
        `filesize`,
        `entities_id`,
        `dl_filename`,
        `icon`
        ) VALUES (
        '" . $uniqid . $this->packageName . "',
        'application',
        '1.0.5',
        '0/" . $uniqid . $this->filename . "',
        '1048576',
        '0',
        '" . $this->filename . "',
        ''
        )";
      $result = $DB->query($query);
      $this->boolean($result)->isTrue();

      $file = new PluginFlyvemdmPackage();
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
      $item = $this->createAppInDB();
      $value = '{"remove_on_delete":0}';
      $result = $policy->getMqttMessage($value, $item->getType(), $item->getID());
      $this->array($result)->hasKeys(['id', 'versionCode', $this->dataField['symbol']])
         ->string($result['id'])->isEqualTo($item->getID())
         ->string($result['versionCode'])->isEqualTo("")
         ->string($result[$this->dataField['symbol']])->isEqualTo($item->getField('package_name'));
   }

   /**
    * @tags testUnicityCheck
    */
   public function testUnicityCheck() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmFleet');
      $mockInstance->getMockController()->getID = 1;
      $application = $this->createAppInDB();
      $this->boolean($policy->unicityCheck(null, PluginFlyvemdmPackage::class, $application->getID(),
         $mockInstance))->isTrue();
      // TODO: finish this test
   }

   /**
    * @tags testConflictCheck
    */
   public function testConflictCheck() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmFleet');
      $mockInstance->getMockController()->getID = 1;
      $packageClass = PluginFlyvemdmPackage::class;
      $this->boolean($policy->conflictCheck(null, $packageClass, -1, $mockInstance))->isFalse();
      $this->string($_SESSION["MESSAGE_AFTER_REDIRECT"][1][0])
         ->isEqualTo('The application does not exists');
      unset($_SESSION["MESSAGE_AFTER_REDIRECT"]); // to clear the buffer
      $application = $this->createAppInDB();
      $this->boolean($policy->conflictCheck(null, $packageClass, $application->getID(),
         $mockInstance))->isTrue();
      // TODO: finish this test
   }

   /**
    * @tags testUnApply
    */
   public function testUnApply() {
      list($policy) = $this->createNewPolicyInstance();
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmFleet');
      $mockInstance->getMockController()->getID = 1;
      $appInDB = $this->createAppInDB();

      // check integrity
      $this->boolean($policy->unapply($mockInstance, null, null, null))->isFalse();

      // check for task to delete
      $value = '{"remove_on_delete":0}';
      $packageClass = PluginFlyvemdmPackage::class;
      $this->boolean($policy->unapply($mockInstance, $value, $packageClass, $appInDB->getID()))
         ->isTrue();

      $value = '{"remove_on_delete":1}';
      $this->boolean($policy->unapply($mockInstance, $value, $packageClass, -1))->isFalse();
      /*
      $this->boolean($policy->unapply($mockInstance, $value, $packageClass, $appInDB->getID()))
         ->isTrue(); // TODO: fix this, Cannot apply a policy on a not managed fleet
      */
   }

   /**
    * @tags testShowValueInput
    * @engine inline
    */
   public function testShowValueInput() {
      list($policy) = $this->createNewPolicyInstance();
      $value = $policy->showValueInput();
      $this->string($value)
         ->contains('dropdown_items_id')
         ->contains('ajax/getDropdownValue.php')
         ->contains('input type="hidden" name="itemtype" value="PluginFlyvemdmPackage"');

      $matches = null;
      preg_match(
         '/.*<select[^>]*name=\'value\[remove_on_delete\]\'[^>]*>.*/',
         $value,
         $matches
      );
      $this->array($matches)->hasSize(1);
   }

   /**
    * @tags testShowValue
    */
   public function testShowValue() {
      list($policy) = $this->createNewPolicyInstance();
      $name = 'appName';
      $alias = 'appAlias';
      $mockInstance = $this->newMockInstance('\PluginFlyvemdmTask');
      $mockInstance->getMockController()->getField = 0;
      $mockInstance->getMockController()->getField[2] = 1;
      $mockInstance->getMockController()->getField[3] = $alias;
      $mockInstance->getMockController()->getField[4] = $name;
      $this->string($policy->showValue($mockInstance))->isEqualTo(NOT_AVAILABLE);
      // TODO: finish this test
      //$this->string($policy->showValue($mockInstance))->isEqualTo("$alias ($name)");
   }
}