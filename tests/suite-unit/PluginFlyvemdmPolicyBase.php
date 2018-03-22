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

use Glpi\Tests\CommonTestCase;

class PluginFlyvemdmPolicyBase extends CommonTestCase {

   private $dataField = [];

   /**
    * @return array
    */
   private function createNewPolicyInstance() {
      $policyData = new \PluginFlyvemdmPolicy();
      $policyData->fields = $this->dataField;
      $policy = $this->newTestedInstance($policyData);
      return [$policy, $policyData];
   }

   /**
    * @tags testCanApply
    */
   public function testCanApply() {
      list($policy) = $this->createNewPolicyInstance();
      $this->boolean($policy->canApply(new \PluginFlyvemdmFleet(), null, null, null))->isTrue();
   }

   /**
    * @tags testUnicityCheck
    */
   public function testUnicityCheck() {
      $this->dataField = ['id' => 1];
      list($policy) = $this->createNewPolicyInstance();

      $mockedFleet = $this->newMockInstance('\PluginFlyvemdmFleet');
      $mockedFleet->getMockController()->getID = 1;
      $this->boolean($policy->unicityCheck(null, null, null, $mockedFleet))->isTrue();
      // TODO this second call should return false
      //$this->boolean($policy->unicityCheck(null, null, null, $mockedFleet))->isFalse();
   }

   /**
    * @tags testConflictCheck
    */
   public function testConflictCheck() {
      list($policy) = $this->createNewPolicyInstance();
      $this->boolean($policy->conflictCheck(null, null, null,
         new \PluginFlyvemdmFleet()))->isTrue();
   }

   /**
    * @tags testIntegrityCheck
    */
   public function testIntegrityCheck() {
      list($policy) = $this->createNewPolicyInstance();
      $this->boolean($policy->integrityCheck(null, null, null))->isTrue();
   }

   /**
    * @tags testTranslateData
    */
   public function testTranslateData() {
      list($policy) = $this->createNewPolicyInstance();
      $this->string($policy->translateData())->isEmpty();
   }

   /**
    * @tags testGetGroup
    */
   public function testGetGroup() {
      list($policy) = $this->createNewPolicyInstance();
      $this->variable($policy->getGroup())->isNull();
   }

   /**
    * @tags testApply
    */
   public function testApply() {
      list($policy) = $this->createNewPolicyInstance();
      $this->boolean($policy->apply(new \PluginFlyvemdmFleet(), null, null, null))->isTrue();
   }

   /**
    * @tags testUnapply
    */
   public function testUnapply() {
      list($policy) = $this->createNewPolicyInstance();
      $this->boolean($policy->unapply(new \PluginFlyvemdmFleet(), null, null, null))->isTrue();
   }

   /**
    * @tags testShowValueInput
    */
   public function testShowValueInput() {
      list($policy) = $this->createNewPolicyInstance();
      $this->string($policy->showValueInput())->isEqualTo('<input name="value" value="" >');
   }

   /**
    * @tags testShowValue
    */
   public function testShowValue() {
      list($policy) = $this->createNewPolicyInstance();
      $mockedFleet = $this->newMockInstance('\PluginFlyvemdmTask');
      $mockedFleet->getMockController()->getField = 'lorem';
      $this->string($policy->showValue($mockedFleet))->isEqualTo('lorem');
   }

   /**
    * @tags testPreprocessFormData
    */
   public function testPreprocessFormData() {
      list($policy) = $this->createNewPolicyInstance();
      $this->array($policy->preprocessFormData($input = ['field' => 'value']))->isEqualTo($input);
   }

   /**
    * @tags testFilterStatus
    */
   public function testFilterStatus() {
      list($policy) = $this->createNewPolicyInstance();
      $this->string($policy->filterStatus($status = 'value'))->isEqualTo($status);
   }

   /**
    * @tags testGetPolicyData
    */
   public function testGetPolicyData() {
      list($policy) = $this->createNewPolicyInstance();
      $this->object($policy->getPolicyData())->isInstanceOf('PluginFlyvemdmPolicy');
   }
}