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

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmTaskstatus extends CommonTestCase {

    private $dataField = [
        'group'     => 'application',
        'symbol'    => 'deployApp',
        'type_data' => '',
        'unicity'   => 0,
     ];
     /**
      * @return array
      */
     private function createNewTaskInstance() {
        $class = new \PluginFlyvemdmTask();
        $task = $this->newTestedInstance($class);
        return $task;
     }

     public function beforeTestMethod($method) {
        switch ($method) {
            case 'testPrepareInputForUpdate':
                parent::beforeTestMethod($method);
                $this->setupGLPIFramework();
                $this->login('glpi', 'glpi');
               break;
         }
     }
     public function afterTestMethod($method) {
        switch ($method) {
            case 'testPrepareInputForUpdate':
                parent::afterTestMethod($method);
                \Session::destroy();
               break;
         }
     }


   /**
    * @return object
    */
   private function createInstance() {
      $this->newTestedInstance();
      return $this->testedInstance;
   }

   /**
    * @tags testClass
    */
   public function testClass() {
      $class = $this->testedClass->getClass();
      $this->given($class)->string($class::$rightname)->isEqualTo('flyvemdm:taskstatus');
   }

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $instance = $this->createInstance();
      $this->string($instance->getTypeName())->isEqualTo('Task status');
   }

   /**
    * @tags testUpdateStatus
    * @dataProvider filterStatusProvider
    * @param unknown $status
    * @param unknown $expected
    */
   public function testUpdateStatus($status, $expected) {
      $instance = $this->createInstance();


      $instance->update([
          'id'     => $instance->getID(),
          'status' => $status,
      ]);
      $this->dump($instance->getField('status'), $expected)->stop();
      $this->string($expected)->isEqualTo($instance->getField('status'));
   }

   /**
    * @tags testPrepareInputForUpdate
    * @dataProvider inputDataProvider
    * @param unknown $input
    */
    public function testPrepareInputForUpdate($input) {
      $instance = $this->createInstance();
      $task = $this->createNewTaskInstance();
      $policy = new \PluginFlyvemdmPolicy();
      $policy->getFromDBByCrit(['symbol' => 'storageEncryption']);
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();

      $taskId = $task->add([
         $policyFk          => $policy->getID(),
         'value'            => '0',
      ]);
      $input[$policyFk] = $policy->getID();
      $this->boolean($instance->prepareInputForUpdate($input))->isFalse();
    }

    public function inputDataProvider() {
        return [
            ['input' =>[]],
            ['input' => [
                'status' => 'received'
            ]]
        ];
    }
    public function filterStatusProvider() {
      return [
         [
            'symbol'   => 'encryption',
            'status'   => 'pending',
            'expected' => 'pending'
         ],
         [
            'symbol'   => 'encryption',
            'status'   => 'received',
            'expected' => 'received'
         ],
         [
            'symbol'   => 'encryption',
            'status'   => 'waiting',
            'expected' => 'waiting'
         ],
         [
            'symbol'   => 'encryption',
            'status'   => 'done',
            'expected' => 'done'
         ],
         [
            'symbol'   => 'encryption',
            'status'   => 'failed',
            'expected' => 'failed'
         ],
         [
            'symbol'   => 'encryption',
            'status'   => 'invalid',
            'expected' => null
         ],
      ];
   }

   /**
    * @dataProvider filterStatusProvider
    * @param string $status
    * @param string $status
    * @param string $expected
    */
   public function testFilterStatus($symbol, $status, $expected) {
      $policy = new \PluginFlyvemdmPolicy();
      $policy->getFromDBBySymbol($policy);
      $policyBase = new \PluginFlyvemdmPolicyBase($policy);
      $this->variable($policyBase->filterStatus($status))->isEqualTo($expected);
   }
}
