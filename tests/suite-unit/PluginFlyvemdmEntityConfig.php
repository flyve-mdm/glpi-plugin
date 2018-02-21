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


class PluginFlyvemdmEntityConfig extends CommonTestCase {

   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testCanAddAgent':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    * @engine inline
    */
   public function testCanAddAgent() {
      global $DB;

      $entity = new \Entity();
      $entityId = $entity->add([
         'name' => 'device count limit' . $this->getUniqueString(),
         'entities_id' => '0',
      ]);
      $this->boolean($entity->isNewItem())->isFalse();

      $DbUtils = new \DBUtils();
      $agents = $DbUtils->countElementsInTable(\PluginFlyvemdmAgent::getTable(), "`entities_id` = '$entityId'");
      $deviceLimit = $agents + 5;

      $entityConfig = new \PluginFlyvemdmEntityConfig();
      $entityConfig->update([
         'id' => $entity->getID(),
         'device_limit' => $deviceLimit,
      ]);

      // Device limit not reached
      $this->boolean($entityConfig->canAddAgent($entityId))->isTrue();

      $agentTable = \PluginFlyvemdmAgent::getTable();
      for ($i = $agents; $i <= $deviceLimit; $i++) {
         $DB->query("INSERT INTO `$agentTable` (
                        `name`,
                        `version`,
                        `plugin_flyvemdm_fleets_id`,
                        `entities_id`)
                     VALUES (
                        '" . $this->getUniqueString() . "',
                        '2.0.0',
                        '1',
                        '" . $entityId . "'
                     )");
      }

      // Device count limit is reached now
      $this->boolean($entityConfig->canAddAgent($entityId))->isFalse();
   }
}
