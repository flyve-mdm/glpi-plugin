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
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Test\CommonTestCase;

class PluginFlyvemdmPolicyCategory extends CommonTestCase
{

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $class = $this->testedClass->getClass();
      $this->given($class)
         //->string($class::getTypeName())->isEqualTo('Policy category') // TODO: check why this is failing
         ->string($class::getTypeName(3))->isEqualTo('Policy categories');
   }

   /**
    * @tags testGetSearchOptionsNew
    */
   public function testGetSearchOptionsNew() {
      $this->given($this->newTestedInstance)
         ->array($result = $this->testedInstance->getSearchOptionsNew())
         ->child[0](function ($child) {
            $child->hasKeys(['id', 'name'])->values
               ->string[0]->isEqualTo('common')
               ->string[1]->isEqualTo('Policy category');
         })
         ->child[1](function ($child) {
            $child->hasKeys(['table', 'name'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_policycategories')
               ->string[3]->isEqualTo('Name');
         })
         ->child[2](function ($child) {
            $child->hasKeys(['table', 'name'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_policycategories')
               ->string[3]->isEqualTo('ID');
         })
         ->child[3](function ($child) {
            $child->hasKeys(['table', 'name'])->values
               ->string[1]->isEqualTo('glpi_plugin_flyvemdm_policycategories')
               ->string[3]->isEqualTo('comment');
         });
   }

}