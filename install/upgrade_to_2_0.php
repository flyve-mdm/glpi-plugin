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
 * @author    the flyvemdm plugin team
 * @copyright Copyright © 2018 Teclib
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmUpgradeTo2_0 {
   /**
    * @param Migration $migration
    */
   public function upgrade(Migration $migration) {
      global $DB;

      $profileRight = new ProfileRight();

      // Merge new rights into current profile
      $profiles_id = $_SESSION['glpiactiveprofile']['id'];
      $currentRights = ProfileRight::getProfileRights($profiles_id);
      $newRights = array_merge($currentRights, [
         PluginFlyvemdmInvitation::$rightname      => ALLSTANDARDRIGHT ,
         PluginFlyvemdmInvitationlog::$rightname   => READ,
         PluginFlyvemdmGeolocation::$rightname     => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         PluginFlyvemdmMqttlog::$rightname         => READ,
      ]);
      $profileRight->updateProfileRights($profiles_id, $newRights);

      $migration->setContext('flyvemdm');
      $migration->addConfig([
         'default_agent_url'     => PLUGIN_FLYVEMDM_AGENT_DOWNLOAD_URL,
         'show_wizard'           => '0',
         'debug_save_inventory'  => '0',
         'android_bugcollecctor_url' => '',
         'android_bugcollector_login' => '',
         'android_bugcollector_passwd' => '',
         'invitation_deeplink' => PLUGIN_FLYVEMDM_DEEPLINK,
      ]);

      $config = Config::getConfigurationValues('flyvemdm', ['mqtt_broker_tls']);
      if (isset($config['mqtt_broker_tls'])) {
         if ($config['mqtt_broker_tls'] !== '0') {
            $config['mqtt_broker_tls_port'] = $config['mqtt_broker_port'];
            $config['mqtt_broker_port'] = '1883';
         } else {
            $config['mqtt_broker_tls_port'] = '8883';
         }
         // Split TLS setting for client in one hand and backend in the other hand
         $config['mqtt_tls_for_clients'] = $config['mqtt_broker_tls'];
         $config['mqtt_tls_for_backend'] = $config['mqtt_broker_tls'];
         Config::setConfigurationValues('flyvemdm', $config);
         Config::deleteConfigurationValues('flyvemdm', ['mqtt_broker_tls']);
      }

      // remove download base URL setting
      Config::deleteConfigurationValues('flyvemdm', ['deploy_base_url']);

      // update Entity config table
      $table = 'glpi_plugin_flyvemdm_entityconfigs';
      $migration->addField($table, 'support_name', 'text', ['after' => 'agent_token_life']);
      $migration->addField($table, 'support_phone', 'string', ['after' => 'support_name']);
      $migration->addField($table, 'support_website', 'string', ['after' => 'support_phone']);
      $migration->addField($table, 'support_email', 'string', ['after' => 'support_website']);
      $migration->addField($table, 'support_address', 'text', ['after' => 'support_email']);
      $migration->addKey($table, 'entities_id', 'entities_id');

      // update Agent table
      $table = 'glpi_plugin_flyvemdm_agents';
      if (!$DB->fieldExists($table, 'enroll_status')) {
         $query = "ALTER TABLE `$table`
                  ADD COLUMN `enroll_status` ENUM('enrolled', 'unenrolling', 'unenrolled') NOT NULL DEFAULT 'enrolled' AFTER `lock`";
         $DB->query($query) or plugin_flyvemdm_upgrade_error($migration);
      }
      $migration->addField($table, 'version', 'string', ['after' => 'name']);
      $migration->addField($table, 'users_id', 'integer', ['after' => 'computers_id']);
      $migration->addField($table, 'is_online', 'integer', ['after' => 'last_contact']);
      $migration->addField($table, 'has_system_permission', 'bool', ['after' => 'mdm_type']);
      $migration->addKey($table, 'computers_id', 'computers_id');
      $migration->addKey($table, 'users_id', 'users_id');
      $migration->addKey($table, 'entities_id', 'entities_id');
      $migration->changeField($table, 'wipe', 'wipe', 'bool');
      $migration->changeField($table, 'lock', 'lock', 'bool');

      $enumMdmType = PluginFlyvemdmAgent::getEnumMdmType();
      $currentEnumMdmType = PluginFlyvemdmCommon::getEnumValues($table, 'mdm_type');
      if (count($currentEnumMdmType) > 0) {
         // The field exists
         if (count($currentEnumMdmType) != count($enumMdmType)) {
            reset($enumMdmType);
            $defaultValue = key($enumMdmType);
            $enumMdmType = "'" . implode("', '", array_keys($enumMdmType)) . "'";
            $query = "ALTER TABLE `$table`
                     CHANGE COLUMN `mdm_type` `mdm_type`
                     ENUM($enumMdmType)
                     NOT NULL DEFAULT '$defaultValue'";
            $DB->query($query) or plugin_flyvemdm_upgrade_error($migration);
         }
      } else {
         // The field does not exists
         reset($enumMdmType);
         $defaultValue = key($enumMdmType);
         $enumMdmType = "'" . implode("', '", array_keys($enumMdmType)) . "'";
         $query = "ALTER TABLE `$table`
                  ADD COLUMN `mdm_type`
                  ENUM($enumMdmType)
                  NOT NULL DEFAULT '$defaultValue'";
         $DB->query($query) or plugin_flyvemdm_upgrade_error($migration);
      }

      // Create task status table
      $table = 'glpi_plugin_flyvemdm_taskstatuses';
      $query = "CREATE TABLE IF NOT EXISTS `$table` (
                  `id`                                  INT(11) NOT NULL AUTO_INCREMENT,
                  `name`                                VARCHAR(255) NOT NULL DEFAULT '',
                  `date_creation`                       DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                  `date_mod`                            DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
                  `plugin_flyvemdm_agents_id`           INT(11) NOT NULL DEFAULT '0',
                  `plugin_flyvemdm_tasks_id`            INT(11) NOT NULL DEFAULT '0',
                  `status`                              VARCHAR(255) NOT NULL DEFAULT '',
                  PRIMARY KEY (`id`),
                  KEY `plugin_flyvemdm_agents_id` (`plugin_flyvemdm_agents_id`),
                  KEY `plugin_flyvemdm_tasks_id` (`plugin_flyvemdm_tasks_id`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->query($query) or plugin_flyvemdm_upgrade_error($migration);

      $migration->addKey($table, 'plugin_flyvemdm_agents_id', 'plugin_flyvemdm_agents_id');
      $migration->addKey($table, 'plugin_flyvemdm_tasks_id', 'plugin_flyvemdm_tasks_id');

      // update Policy table
      $table = 'glpi_plugin_flyvemdm_policies';
      $migration->addField($table, 'recommended_value', 'string', ['after' => 'default_value']);
      $migration->addField($table, 'is_android_system', 'bool', ['after' => 'recommended_value']);
      $migration->addField($table, 'android_min_version', 'string', ['after' => 'is_android_system', 'value' => '0']);
      $migration->addField($table, 'android_max_version', 'string', ['after' => 'android_min_version', 'value' => '0']);
      $migration->addField($table, 'apple_min_version', 'string', ['after' => 'android_max_version', 'value' => '0']);
      $migration->addField($table, 'apple_max_version', 'string', ['after' => 'apple_min_version', 'value' => '0']);
      $migration->addKey($table, 'group', 'group');
      $migration->addKey($table, 'plugin_flyvemdm_policycategories_id', 'plugin_flyvemdm_policycategories_id');
      $migration->dropField($table, 'is_android_policy');
      $migration->dropField($table, 'is_apple_policy');

      // Rename and update fleet_policy into task
      $table = 'glpi_plugin_flyvemdm_tasks';
      $migration->renameTable('glpi_plugin_flyvemdm_fleets_policies', $table);
      $migration->changeField($table, 'plugin_flyvemdm_fleets_policies_id', 'plugin_flyvemdm_tasks_id',
         'integer');
      $migration->addKey($table, 'plugin_flyvemdm_policies_id', 'plugin_flyvemdm_policies_id');

      // Upgrade schema to apply policies on fleets and agents
      if (!$DB->fieldExists($table, 'items_id_applied')) {
         $migration->changeField($table, 'plugin_flyvemdm_fleets_id', 'items_id_applied', 'integer');
         $migration->dropKey($table, 'plugin_flyvemdm_fleets_id');
         $migration->addField($table, 'itemtype_applied', 'string', ['after' => 'id']);
         $migration->addKey($table, ['itemtype_applied', 'items_id_applied'], 'FK_applied');
         // All tasks already created were applied on fleets
         $migration->addPostQuery("UPDATE `$table` SET `itemtype_applied` = 'PluginFlyvemdmFleet'");
         $migration->executeMigration();
      }

      $table = 'glpi_plugin_flyvemdm_policies';
      $policies = [
         'disableFmRadio',
         'disableVoiceMail',
         'disableCallAutoAnswer',
         'disableVoiceDictation',
         'disableUsbOnTheGo',
         'resetPassword',
         'inventoryFrequency',
         'disableSmsMms',
         'disableStreamVoiceCall',
         'disableCreateVpnProfiles',
      ];
      $tasksTable = 'glpi_plugin_flyvemdm_tasks';
      $fleetTable = 'glpi_plugin_flyvemdm_fleets';
      $agentsTable = 'glpi_plugin_flyvemdm_agents';
      $request = [
         'FIELDS'     => [
            $table       => ['symbol'],
            $tasksTable  => ['id', 'itemtype_applied', 'items_id_applied'],
            $fleetTable  => ['entities_id'],
         ],
         'FROM'       => $table,
         'INNER JOIN' => [
            $tasksTable => [
               'FKEY' => [
                  $tasksTable => 'plugin_flyvemdm_policies_id',
                  $table      => 'id',
               ],
            ],
         ],
         'LEFT JOIN'  => [
            $fleetTable  => [
               'itemtype_applied' => 'PluginFlyvemdmFleet',
               'FKEY'             => [
                  $tasksTable => 'items_id_applied',
                  $fleetTable => 'id',
               ],
            ],
            $agentsTable => [
               'itemtype_applied' => 'PluginFlyvemdmAgent',
               'FKEY'             => [
                  $tasksTable  => 'items_id_applied',
                  $agentsTable => 'id',
               ],
            ],
         ],
         'WHERE'      => [
            'symbol' => $policies,
         ],
      ];
      $result = $DB->request($request);
      if (count($result) > 0) {
         $mqttClient = PluginFlyvemdmMqttclient::getInstance();
         foreach ($result as $data) {
            switch ($data['itemtype_applied']) {
               case PluginFlyvemdmFleet::class:
                  $type = 'fleet';
                  $entityId = $data['entities_id'];
                  break;

               case PluginFlyvemdmAgent::class:
                  $agent = new PluginFlyvemdmAgent();
                  $agent->getFromDB($data['items_id_applied']);
                  $type = 'agent';
                  $entityId = $agent->getEntityID();
                  break;

               default:
                  $type = '';
            }
            if ($type === '') {
               continue;
            }
            $topic = implode('/', [
               '/',
               $entityId,
               $type,
               $data['items_id_applied'],
               'Policy',
               $data['symbol'],
               'Task',
               $data['id']
            ]);
            $mqttClient->publish($topic, null, 0, 1);
         }
      }
      $policiesStr = implode("','", $policies);
      $migration->addPostQuery("DELETE FROM `$table` WHERE `symbol` IN ('" . $policiesStr . "')");

      // update Applications table
      $table = 'glpi_plugin_flyvemdm_packages';
      $migration->addField($table, 'parse_status', "enum('pending', 'parsed', 'failed') NOT NULL DEFAULT 'pending'",
         ['after' => 'dl_filename', 'default' => 'pending']);
      $migration->addfield($table, 'name', 'string', ['after' => 'id']);
      $migration->migrationOneTable($table);
      $migration->dropField($table, 'filesize');
      $migration->addField($table, 'name', 'string', ['after' => 'id']);
      $migration->addKey($table, 'entities_id', 'entities_id');
      $migration->addPostQuery("UPDATE `$table` SET `parse_status` = 'parsed'");
      $migration->addPostQuery("UPDATE `$table` SET `name` = `package_name`");

      $result = $DB->request(['FROM' => $table, 'LIMIT' => '1']);
      if ($result->count() > 0) {
         $result->rewind();
         $row = $result->current();
         if (strpos($row['filename'], 'flyvemdm/package/') !== 0) {
            // If there is at least one package and the path does starts with the new prefix, then update all the table
            $migration->addPostQuery("UPDATE `$table` SET `filename` = CONCAT('flyvemdm/package/', `filename`)");
         }
      }

      $table = 'glpi_plugin_flyvemdm_files';
      $migration->addKey($table, 'entities_id', 'entities_id');
      $migration->addField($table, 'comment', 'text');

      // Add display preferences for PluginFlyvemdmFile
      $query = "SELECT * FROM `glpi_displaypreferences`
      WHERE `itemtype` = 'PluginFlyvemdmFile'
         AND `users_id`='0'";
      $result=$DB->query($query);
      if ($DB->numrows($result) == '0') {
         $query = "INSERT INTO `glpi_displaypreferences` (`id`, `itemtype`, `num`, `rank`, `users_id`)
                  VALUES (NULL, 'PluginFlyvemdmFile', '1', '1', '0'),
                  (NULL, 'PluginFlyvemdmFile', '4', '2', '0');";
         $DB->query($query);
      }

      $table = 'glpi_plugin_flyvemdm_fleets';
      $migration->addField($table, 'is_recursive', 'bool');
      $migration->addKey($table, 'entities_id', 'entities_id');

      $table = 'glpi_plugin_flyvemdm_geolocations';
      $migration->addKey($table, 'computers_id', 'computers_id');

      $table = 'glpi_plugin_flyvemdm_mqttacls';
      $migration->addKey($table, 'plugin_flyvemdm_mqttusers_id', 'plugin_flyvemdm_mqttusers_id');

      $table = 'glpi_plugin_flyvemdm_policycategories';
      $migration->addKey($table, 'plugin_flyvemdm_policycategories_id', 'plugin_flyvemdm_policycategories_id');

      // Create invitation log table
      $table = 'glpi_plugin_flyvemdm_invitationlogs';
      $query = "CREATE TABLE IF NOT EXISTS `$table` (
               `id`                               int(11)                   NOT NULL AUTO_INCREMENT,
               `plugin_flyvemdm_invitations_id`   int(11)                   NOT NULL DEFAULT '0',
               `date_creation`                    datetime                  NOT NULL DEFAULT '0000-00-00 00:00:00',
               `event`                            varchar(255)              NOT NULL DEFAULT '',
               PRIMARY KEY (`id`),
               INDEX `plugin_flyvemdm_invitations_id` (`plugin_flyvemdm_invitations_id`),
               INDEX `date_creation` (`date_creation`)
               ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->query($query) or plugin_flyvemdm_upgrade_error($migration);
      $migration->addKey($table, 'plugin_flyvemdm_invitations_id', 'plugin_flyvemdm_invitations_id');

      $table = 'glpi_plugin_flyvemdm_invitations';
      if (!$DB->fieldExists($table, 'name')) {
         $invitationName = _n('Invitation', 'Invitations', 1, 'flyvemdm');
         $migration->addField($table, 'name', 'string', ['after' => 'id']);
         $migration->addPostQuery("UPDATE `$table` SET `name` = '$invitationName'");
      }
      $migration->addKey($table, 'users_id', 'users_id');
      $migration->addKey($table, 'entities_id', 'entities_id');
      $migration->addKey($table, 'documents_id', 'documents_id');

      // drop Mqtt Update queue
      $cronTask = new CronTask();
      $cronTask->deleteByCriteria(['itemtype' => 'PluginFlyvemdmMqttupdatequeue']);
      $table = 'glpi_plugin_flyvemdm_mqttupdatequeues';
      $migration->dropTable($table);

      $table = 'glpi_plugin_flyvemdm_mqttlogs';
      $migration->changeField($table, 'message', 'message', 'mediumtext');
      $migration->addField($table, 'itemtype', 'string', ['after' => 'message']);
      $migration->addField($table, 'items_id', 'integer', ['after' => 'itemtype']);
      $migration->addKey($table, 'itemtype', 'itemtype');
      $migration->addKey($table, 'items_id', 'items_id');
      if (!$DB->fieldExists($table, 'name')) {
         // upgrade fleets logs to their new format
         $migration->addPostQuery("UPDATE $table as t1,
            (SELECT id, SUBSTRING_INDEX(SUBSTRING_INDEX(topic, '/', 3), '/', -1) as new_items_id,
              SUBSTRING(REPLACE(topic, SUBSTRING_INDEX(topic, '/', 3), ''), 2) as new_topic
              FROM $table WHERE topic NOT LIKE '/%' and topic like '%/fleet/%') as t2
            SET t1.itemtype = 'PluginFlyvemdmFleet', t1.items_id = t2.new_items_id, 
            t1.topic = t2.new_topic, t1.topic = t2.new_topic WHERE t1.id = t2.id");

         // upgrade agents logs to their new format
         $migration->addPostQuery("UPDATE $table as t1,
            (SELECT m.id, c.id as new_items_id, 
              SUBSTRING(REPLACE(topic, SUBSTRING_INDEX(topic, '/', 3), ''), 2) as new_topic
              FROM $table as m, glpi_computers as c
              WHERE topic NOT LIKE '/%' and topic like '%/agent/%' 
              AND serial = SUBSTRING_INDEX(SUBSTRING_INDEX(topic, '/', 3), '/', -1)) as t2
            SET t1.itemtype = 'PluginFlyvemdmAgent', t1.items_id = t2.new_items_id, 
            t1.topic = t2.new_topic, t1.topic = t2.new_topic WHERE t1.id = t2.id");
      }

      // Fix PascalCase symbols
      $query = "UPDATE `glpi_plugin_flyvemdm_policies`
                  SET `symbol` = 'maximumFailedPasswordsForWipe'
                  WHERE `symbol`='MaximumFailedPasswordsForWipe'";
      $DB->query($query);
      $query = "UPDATE `glpi_plugin_flyvemdm_policies`
                  SET `symbol` = 'maximumTimeToLock'
                  WHERE `symbol`='MaximumTimeToLock'";
      $DB->query($query);

      // change MQTT topics tree layout : remove leading slash
      $mqttClient = PluginFlyvemdmMqttclient::getInstance();
      $request = [
         'FIELDS' => [
            'glpi_plugin_flyvemdm_agents' => ['entities_id'],
            'glpi_computers' => ['serial'],
         ],
         'FROM'   => 'glpi_plugin_flyvemdm_agents',
         'INNER JOIN' => [
            'glpi_computers' => ['FKEY' => [
               'glpi_plugin_flyvemdm_agents' => 'computers_id',
               'glpi_computers' => 'id'
            ]]
         ],
         'WHERE'  => ['lock' => ['<>' => '0']]
      ];
      $mqttMessage = ['lock' => 'now'];
      $mqttMessage = json_encode($mqttMessage, JSON_UNESCAPED_SLASHES);
      foreach ($DB->request($request) as $row) {
         $topic = implode('/', [
            $row['entities_id'],
            'agent',
            $row['serial'],
            'Command',
            'Lock',
         ]);
         $mqttClient->publish($topic, $mqttMessage, 0, 1);
         $mqttClient->publish('/' . $topic, null, 0, 1);
      }

      // re-use previous request array
      $request['WHERE'] = ['wipe' => ['<>' => '0']];
      $mqttMessage = ['wipe' => 'now'];
      $mqttMessage = json_encode($mqttMessage, JSON_UNESCAPED_SLASHES);
      foreach ($DB->request($request) as $row) {
         $topic = implode('/', [
            $row['entities_id'],
            'agent',
            $row['serial'],
            'Command',
            'Wipe',
         ]);
         $mqttClient->publish($topic, $mqttMessage, 0, 1);
         $mqttClient->publish('/' . $topic, null, 0, 1);
      }

      // re-use previous request array
      $request['WHERE'] = ['enroll_status' => ['=' => 'unenrolling']];
      $mqttMessage = ['unenroll' => 'now'];
      $mqttMessage = json_encode($mqttMessage, JSON_UNESCAPED_SLASHES);
      foreach ($DB->request($request) as $row) {
         $topic = implode('/', [
            $row['entities_id'],
            'agent',
            $row['serial'],
            'Command',
            'Unenroll',
         ]);
         $mqttClient->publish($topic, $mqttMessage, 0, 1);
         $mqttClient->publish('/' . $topic, null, 0, 1);
      }

      $request = [
         'FIELDS' => [
            'glpi_plugin_flyvemdm_tasks' => ['id', 'itemtype_applied', 'items_id_applied', 'plugin_flyvemdm_policies_id', 'itemtype', 'items_id', 'value'],
            'glpi_plugin_flyvemdm_policies' => ['symbol'],
            'glpi_plugin_flyvemdm_fleets' => ['entities_id']
         ],
         'FROM'   => 'glpi_plugin_flyvemdm_tasks',
         'INNER JOIN' => [
            'glpi_plugin_flyvemdm_policies' => [
               'FKEY' => [
                  'glpi_plugin_flyvemdm_tasks' => 'plugin_flyvemdm_policies_id',
                  'glpi_plugin_flyvemdm_policies' => 'id'
               ]
            ],
            'glpi_plugin_flyvemdm_fleets' => [
               'FKEY' => [
                  'glpi_plugin_flyvemdm_tasks' => 'items_id_applied',
                  'glpi_plugin_flyvemdm_fleets' => 'id'
               ]
            ]
         ],
         'WHERE' => [
            'glpi_plugin_flyvemdm_tasks.itemtype_applied' => 'PluginFlyvemdmFleet'
         ]
      ];
      foreach ($DB->request($request) as $row) {
         switch ($row['itemtype_applied']) {
            case PluginFlyvemdmFleet::class:
               $type = 'fleet';
               break;

            case PluginFlyvemdmAgent::class:
               $type = 'agent';
               break;

            default:
               $type = '';
         }
         if ($type === '') {
            continue;
         }
         $topic = implode('/', [
            $row['entities_id'],
            'fleet',
            $row['items_id_applied'],
            'Policy',
            $row['symbol'],
         ]);
         $policyFactory = new PluginFlyvemdmPolicyFactory();
         $appliedPolicy = $policyFactory->createFromDBByID($row['plugin_flyvemdm_policies_id']);
         $policyMessage = $appliedPolicy->getMqttMessage(
            $row['value'],
            $row['itemtype'],
            $row['items_id']
         );
         $policyMessage['taskId'] = $row['id'];
         $encodedMessage = json_encode($policyMessage, JSON_UNESCAPED_SLASHES);
         $mqttClient->publish("$topic/Task/" . $row['id'], $encodedMessage, 0, 1);
         $mqttClient->publish('/' . $topic, null, 0, 1);
      }
   }
}