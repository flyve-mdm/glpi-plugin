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
   $table = PluginFlyvemdmEntityconfig::getTable();
   $migration->addField($table, 'support_name', 'text', ['after' => 'agent_token_life']);
   $migration->addField($table, 'support_phone', 'string', ['after' => 'support_name']);
   $migration->addField($table, 'support_website', 'string', ['after' => 'support_phone']);
   $migration->addField($table, 'support_email', 'string', ['after' => 'support_website']);
   $migration->addField($table, 'support_address', 'text', ['after' => 'support_email']);

   // update Agent table
   $table = PluginFlyvemdmAgent::getTable();
   $migration->addField($table, 'users_id', 'integer', ['after' => 'computers_id']);
   $migration->addField($table, 'is_online', 'integer', ['after' => 'last_contact']);
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
   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_taskstatuses` (
               `id`                                  int(11) NOT NULL AUTO_INCREMENT,
               `name`                                varchar(255) NOT NULL DEFAULT '',
               `date_creation`                       datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
               `date_mod`                            datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
               `plugin_flyvemdm_agents_id`           int(11) NOT NULL DEFAULT '0',
               `plugin_flyvemdm_tasks_id`            int(11) NOT NULL DEFAULT '0',
               `status`                              varchar(255) NOT NULL DEFAULT '',
               PRIMARY KEY (`id`)
             ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
   if (!$DB->query($query)) {
      plugin_flyvemdm_upgrade_error($migration);
   }

   // Rename and update fleet_policy into task
   $migration->renameTable('glpi_plugin_flyvemdm_fleets_policies', 'glpi_plugin_flyvemdm_tasks');
   $migration->changeField('glpi_plugin_flyvemdm_tasks', 'plugin_flyvemdm_fleets_policies_id', 'plugin_flyvemdm_tasks_id', 'integer');

   // update Policy table
   $table = PluginFlyvemdmPolicy::getTable();
   $migration->addField($table, 'is_android_policy', 'bool', ['after' => 'recommended_value']);
   $migration->addField($table, 'is_apple_policy', 'bool', ['after' => 'is_android_policy']);
   // All policies exist for Android
   $migration->addPostQuery("UPDATE `$table` SET `is_android_policy` = '1'");

}
