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
 * @author    the flyvemdm plugin team
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

function plugin_flyvemdm_update_to_dev(Migration $migration) {
   global $DB;

   $migration->setVersion(PLUGIN_FLYVEMDM_VERSION);

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
   $migration->addField($table, 'users_id', 'integer', ['after' => 'computers_id']);
   $migration->addField($table, 'is_online', 'integer', ['after' => 'last_contact']);
   $migration->addKey($table, 'entities_id', 'entities_id');
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
   if (!$DB->query($query)) {
      plugin_flyvemdm_upgrade_error($migration);
   }
   $migration->addKey($table, 'plugin_flyvemdm_agents_id', 'plugin_flyvemdm_agents_id');
   $migration->addKey($table, 'plugin_flyvemdm_tasks_id', 'plugin_flyvemdm_tasks_id');

   // Rename and update fleet_policy into task
   $table = 'glpi_plugin_flyvemdm_tasks';
   $migration->renameTable('glpi_plugin_flyvemdm_fleets_policies', $table);
   $migration->changeField($table, 'plugin_flyvemdm_fleets_policies_id', 'plugin_flyvemdm_tasks_id',
      'integer');
   $migration->addKey($table, 'plugin_flyvemdm_fleets_id', 'plugin_flyvemdm_fleets_id');
   $migration->addKey($table, 'plugin_flyvemdm_policies_id', 'plugin_flyvemdm_policies_id');

   // update Policy table
   $table = 'glpi_plugin_flyvemdm_policies';
   $migration->addField($table, 'is_android_policy', 'bool', ['after' => 'recommended_value']);
   $migration->addField($table, 'is_apple_policy', 'bool', ['after' => 'is_android_policy']);
   $migration->addKey($table, 'group', 'group');
   $migration->addKey($table, 'plugin_flyvemdm_policycategories_id', 'plugin_flyvemdm_policycategories_id');
   // All policies exist for Android
   $migration->addPostQuery("UPDATE `$table` SET `is_android_policy` = '1'");

   // update Applications table
   $table = 'glpi_plugin_flyvemdm_packages';
   $migration->addField($table, 'parse_status', "enum('pending', 'parsed', 'failed')",
      ['after' => 'dl_filename', 'default' => 'pending']);
   $migration->addKey($table, 'entities_id', 'entities_id');
   $migration->addPostQuery("UPDATE `$table` SET `parse_status` = 'parsed'");
   $migration->addPostQuery("UPDATE `$table` SET `filename` = CONCAT('" . addslashes(GLPI_DOC_DIR) . "', `filename`)");
   $migration->addfield($table, 'plugin_orion_tasks_id', 'integer', ['after' => 'dl_filename']);

   $table = 'glpi_plugin_flyvemdm_files';
   $migration->addKey($table, 'entities_id', 'entities_id');

   $table = 'glpi_plugin_flyvemdm_fleets';
   $migration->addKey($table, 'entities_id', 'entities_id');

   $table = 'glpi_plugin_flyvemdm_geolocations';
   $migration->addKey($table, 'computers_id', 'computers_id');

   $table = 'glpi_plugin_flyvemdm_mqttacls';
   $migration->addKey($table, 'plugin_flyvemdm_mqttusers_id', 'plugin_flyvemdm_mqttusers_id');

   $table = 'glpi_plugin_flyvemdm_policycategories';
   $migration->addKey($table, 'plugin_flyvemdm_policycategories_id', 'plugin_flyvemdm_policycategories_id');

   $table = 'glpi_plugin_flyvemdm_invitationlogs';
   $migration->addKey($table, 'glpi_plugin_flyvemdm_invitationlogs', 'invitations_id');

   $table = 'glpi_plugin_flyvemdm_invitations';
   $migration->addKey($table, 'users_id', 'users_id');
   $migration->addKey($table, 'entities_id', 'entities_id');
   $migration->addKey($table, 'documents_id', 'documents_id');

   $table = 'glpi_plugin_flyvemdm_mqttupdatequeues';
   $migration->dropKey($table, 'status');
}
