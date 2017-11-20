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
 * @author    Thierry Bugier
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */
namespace tests\units;

use Glpi\Test\CommonTestCase;

class PluginFlyvemdmFleet extends CommonTestCase {

   public function setUp() {
      parent::setUp();
      self::setupGLPIFramework();
      $this->boolean($this->login('glpi', 'glpi'))->isTrue();
   }

   /**
    * @engine inline
    */
   public function testDefineTabs() {
      // Test a managed fleet shows the policies tab
      $instance = $this->newInstance();
      $instance->add([
         'name'         => 'I manage devices',
         'entities_id'  => $_SESSION['glpiactive_entity'],
      ]);
      $this->boolean($instance->isNewItem())->isFalse();
      $tabs = $instance->defineTabs();
      $this->array($tabs)->hasKey('PluginFlyvemdmTask$1');

      // Test a not manged fleet does not show a policies tab
      $instance = $this->newInstance();
      $instance->getFromDBByCrit([
         'AND' => [
            'entities_id' => $_SESSION['glpiactive_entity'],
            'is_default' => '1',
         ],
      ]);
      $this->boolean($instance->isNewItem())->isFalse();
      $tabs = $instance->defineTabs();
      $this->array($tabs)->notHasKey('PluginFlyvemdmTask$1');

   }
}