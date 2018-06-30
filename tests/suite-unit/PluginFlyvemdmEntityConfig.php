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


class PluginFlyvemdmEntityConfig extends CommonTestCase {

   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testCanAddAgent':
         case 'testHook_entity_add':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    *
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

   public function providerPrepareInputForUpdate() {
      return [
         [
            'credentials' => ['glpi', 'glpi'],
            'input' => [
               'device_limit' => 42,
               'download_url' => 'https://nothing.local/id=com.nothing.local',
               'agent_token_life' => 'P99D',
            ],
            'output' => [
               'device_limit' => 42,
               'download_url' => 'https://nothing.local/id=com.nothing.local',
               'agent_token_life' => 'P99D',
            ],
            'message' => ''
         ],
         [
            ['normal', 'normal'],
            [
               'device_limit' => 42,
               'download_url' => 'https://nothing.local/id=com.nothing.local',
               'agent_token_life' => 'P99D',
            ],
            [],
            [
               'You are not allowed to change the device limit',
               'You are not allowed to change the download URL of the MDM agent',
               'You are not allowed to change the invitation token life',
            ]
         ]
      ];
   }

   /**
    * @engine inline
    * @dataProvider providerPrepareInputForUpdate
    *
    * @param array $credentials credentials used for login
    * @param array $input input of the tested method
    * @param array|boolean $output expected output
    * @param string $message expected output message (if $output === false or $output === [])
    */
   public function testPrepareInputForUpdate(array $credentials, array $input, $output, $message) {
      // Login
      $loginSuccess = $this->login($credentials[0], $credentials[1]);
      $this->boolean($loginSuccess)->isTrue('Failed to login');

      $instance = $this->newTestedInstance();
      $instance->getFromDB(0);
      $actualOutput = $instance->prepareInputForUpdate($input);
      if ($output === false) {
         $this->boolean($actualOutput)->isFalse();
         $this->sessionHasMessage($message, WARNING);
      } else if ($output === []) {
         $this->array($actualOutput)->isEmpty();
         $this->sessionHasMessage($message, WARNING);
      } else {
         $this->array($actualOutput)->size->isEqualTo(count($output));
         $this->array($actualOutput)->hasKeys(array_keys($output));
         $this->array($actualOutput)->containsValues($output);
      }
   }

   public function testHook_entity_add() {
      $config = \Config::getConfigurationValues('flyvemdm', ['default_device_limit']);
      $defaultDeviceLimit = $config['default_device_limit'];

      // Create an entity in DB
      // This must run hook_entity_add
      $entity = new \Entity();
      $entityId = $entity->import([
         'name' => __FUNCTION__ . ' '. $this->getUniqueString(),
         'entities_id' => 0
      ]);
      // The root entity (id 0) already exists then it cannot be returned or retrieved
      $this->integer($entityId)->isGreaterThan(0);

      $instance = $this->newTestedInstance();
      $instance->getFromDBByCrit([
         \Entity::getForeignKeyField() => $entityId
      ]);
      $this->boolean($instance->isNewItem())->isFalse();
      $this->string($instance->getField('agent_token'))->isNotEmpty();
      $this->string($instance->getField('agent_token_life'))->isEqualTo('P7D');
      $this->string($instance->getField('support_name'))->isEqualTo('');
      $this->string($instance->getField('support_phone'))->isEqualTo('');
      $this->string($instance->getField('support_website'))->isEqualTo('');
      $this->string($instance->getField('support_email'))->isEqualTo('');
      $this->string($instance->getField('support_address'))->isEqualTo('');
      $this->integer((int) $instance->getField('managed'))->isEqualTo(0);
      $this->string($instance->getField('download_url'))->isEqualTo(PLUGIN_FLYVEMDM_AGENT_DOWNLOAD_URL);
      $this->integer((int) $instance->getField('device_limit'))->isEqualTo((int) $defaultDeviceLimit);

      // Test directories are created
      $this->boolean(is_dir(FLYVEMDM_PACKAGE_PATH . '/' . $entityId));
      $this->boolean(is_dir(FLYVEMDM_FILE_PATH . '/' . $entityId));
   }
}
