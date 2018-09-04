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

class PluginFlyvemdmUpgradeTodevelop {
   /**
    * @param Migration $migration
    */
   public function upgrade(Migration $migration) {
      global $DB;

      $migration->setVersion(PLUGIN_FLYVEMDM_VERSION);

      $profileRight = new ProfileRight();
      // Merge new rights into current profile
      $profiles_id = $_SESSION['glpiactiveprofile']['id'];
      $currentRights = ProfileRight::getProfileRights($profiles_id);
      $newRights = array_merge($currentRights, [
         PluginFlyvemdmFDroidMarket::$rightname       => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         PluginFlyvemdmFDroidApplication::$rightname  => READ | UPDATE | READNOTE | UPDATENOTE,
      ]);
      $profileRight->updateProfileRights($profiles_id, $newRights);

      // Create table for F-Droid application
      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_fdroidapplications` (
         `id`                    int(11)                                  NOT NULL AUTO_INCREMENT,
         `name`                  varchar(255)                             NOT NULL DEFAULT '',
         `package_name`                varchar(255)                       NOT NULL DEFAULT '',
         `entities_id`                 int(11)                            NOT NULL DEFAULT '0',
         `is_recursive`                tinyint(1)                         NOT NULL DEFAULT '0',
         `plugin_flyvemdm_fdroidmarkets_id` int(11)                       NOT NULL DEFAULT '0',
         `plugin_flyvemdm_packages_id` int(11)                            NOT NULL DEFAULT '0',
         `alias`                 varchar(255)                             NOT NULL DEFAULT '',
         `version`               varchar(255)                             NOT NULL DEFAULT '',
         `version_code`          varchar(255)                             NOT NULL DEFAULT '',
         `date_mod`              datetime                                 NOT NULL DEFAULT '0000-00-00 00:00:00',
         `desc`                  text                                     NOT NULL,
         `filename`              varchar(255)                             NOT NULL DEFAULT '',
         `filesize`              int(11)                                  NOT NULL DEFAULT '0',
         `import_status`         enum('no_import','to_import','imported') NOT NULL DEFAULT 'no_import',
         `is_available`          tinyint(1)                               NOT NULL DEFAULT '1' COMMENT 'Does the applciation e xists in the store ?',
         `is_auto_upgradable`    tinyint(1)                               NOT NULL DEFAULT '1' COMMENT 'Can we automatically download the upgrades ?',
         PRIMARY KEY (`id`),
         KEY `entities_id` (`entities_id`),
         KEY `plugin_flyvemdm_fdroidmarkets_id` (`plugin_flyvemdm_fdroidmarkets_id`),
         KEY `plugin_flyvemdm_packages_id` (`plugin_flyvemdm_packages_id`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
      ";
      $DB->query($query) or plugin_flyvemdm_upgrade_error($migration);

      // Update enum if needed
      $table = 'glpi_plugin_flyvemdm_fdroidapplications';
      $enumImportStatus = PluginFlyvemdmFDroidApplication::getEnumImportStatus();
      $currentEnumImportStatus = PluginFlyvemdmCommon::getEnumValues($table, 'import_status');
      if (count($currentEnumImportStatus) > 0) {
         // The field exists
         if (count($currentEnumImportStatus) != count($enumImportStatus)) {
            reset($enumImportStatus);
            $defaultValue = key($enumImportStatus);
            $enumImportStatus = "'" . implode("', '", array_keys($enumImportStatus)) . "'";
            $query = "ALTER TABLE `$table`
                     CHANGE COLUMN `import_status` `import_status`
                     ENUM($enumImportStatus)
                     NOT NULL DEFAULT '$defaultValue'";
            $DB->query($query) or plugin_flyvemdm_upgrade_error($migration);
         }
      } else {
         // The field does not exists
         reset($enumImportStatus);
         $defaultValue = key($enumImportStatus);
         $enumImportStatus = "'" . implode("', '", array_keys($enumImportStatus)) . "'";
         $query = "ALTER TABLE `$table`
               ADD COLUMN `import_status`
               ENUM($enumImportStatus)
               NOT NULL DEFAULT '$defaultValue'";
         $DB->query($query) or plugin_flyvemdm_upgrade_error($migration);
      }

      // Create table for F-Droid
      $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_fdroidmarkets` (
            `id`                          int(11)                                  NOT NULL AUTO_INCREMENT,
            `name`                        varchar(255)                             NOT NULL DEFAULT '',
            `entities_id`                 int(11)                                  NOT NULL DEFAULT '0',
            `is_recursive`                tinyint(1)                               NOT NULL DEFAULT '0',
            `url`                         varchar(255)                             NOT NULL DEFAULT '' COMMENT 'URL to index.xml of the market',
            PRIMARY KEY (`id`),
            KEY `entities_id` (`entities_id`)
         ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
         ";
      $DB->query($query) or plugin_flyvemdm_upgrade_error($migration);
   }
}