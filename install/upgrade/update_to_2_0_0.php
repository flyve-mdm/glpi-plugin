<?php

function update_to_2_0_0(Migration $migration) {
   global $DB;

   $migration->setVersion('2.0.0');

   ini_set("max_execution_time", "0");
   ini_set("memory_limit", "-1");

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

   $DB->query($query) or die("Could not create invitation logs table " . $DB->error());
   $migration->addKey($table, 'plugin_flyvemdm_invitations_id', 'invitations_id');

   $profileRight = new ProfileRight();

   // Merge new rights into current profile
   $currentRights = ProfileRight::getProfileRights($_SESSION['glpiactiveprofile']['id']);
   $newRights = array_merge($currentRights, [
         PluginFlyvemdmInvitation::$rightname      => CREATE | READ | UPDATE | DELETE | PURGE,
         PluginFlyvemdmInvitationlog::$rightname   => READ,
         PluginFlyvemdmGeolocation::$rightname     => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         PluginFlyvemdmTask::$rightname            => READ,
   ]);
   $profileRight->updateProfileRights($_SESSION['glpiactiveprofile']['id'], $newRights);

   $table = 'glpi_plugin_flyvemdm_agents';
   if (!$DB->fieldExists($table, 'enroll_status')) {
      $query = "ALTER TABLE `$table`
                ADD COLUMN `enroll_status` ENUM('enrolled', 'unenrolling', 'unenrolled') NOT NULL DEFAULT 'enrolled' AFTER `lock`";
      $DB->query($query) or die("Could upgrade table $table" . $DB->error());
   }
   $migration->addField($table, 'version', 'string', ['after' => 'name']);
   $migration->addKey($table, 'computers_id', 'computers_id');
   $migration->addKey($table, 'users_id', 'users_id');
   $migration->addKey($table, 'entities_id', 'entities_id');

   $table = 'glpi_plugin_flyvemdm_policies';
   $migration->addField($table, 'recommended_value', 'string', ['after' => 'default_value']);
   $migration->addKey($table, 'group', 'group');
   $migration->addKey($table, 'plugin_flyvemdm_policycategories_id', 'plugin_flyvemdm_policycategories_id');

   // remove download base URL setting
   Config::deleteConfigurationValues('flyvemdm', ['deploy_base_url']);

   Config::setConfigurationValues('flyvemdm', [
      'default_agent_url' => PLUGIN_FLYVEMDM_AGENT_DOWNLOAD_URL,
   ]);

   $config = Config::getConfigurationValues('flyvemdm', ['android_bugcollecctor']);
   if (!isset($config['android_bugcollecctor_url'])) {
      $config = [
         'android_bugcollecctor_url' => '',
         'android_bugcollector_login' => '',
         'android_bugcollector_passwd' => '',
      ];
      Config::setConfigurationValues('flyvemdm', $config);
   }

   $table = 'glpi_plugin_flyvemdm_entityconfigs';
   $migration->addKey($table, 'entities_id', 'entities_id');

   $table = 'glpi_plugin_flyvemdm_files';
   $migration->addKey($table, 'entities_id', 'entities_id');

   $table = 'glpi_plugin_flyvemdm_fleets';
   $migration->addField($table, 'is_recursive', 'bool');
   $migration->addKey($table, 'entities_id', 'entities_id');

   $table = 'glpi_plugin_flyvemdm_geolocations';
   $migration->addKey($table, 'computers_id', 'computers_id');

   $table = 'glpi_plugin_flyvemdm_mqttacls';
   $migration->addKey($table, 'plugin_flyvemdm_mqttusers_id',
      'plugin_flyvemdm_mqttusers_id');

   $table = 'glpi_plugin_flyvemdm_packages';
   $migration->addKey($table, 'entities_id', 'entities_id');

   $table = 'glpi_plugin_flyvemdm_policycategories';
   $migration->addKey($table, 'plugin_flyvemdm_policycategories_id', 'plugin_flyvemdm_policycategories_id');

   $table = 'glpi_plugin_flyvemdm_tasks';
   $migration->addKey($table, 'plugin_flyvemdm_fleets_id', 'plugin_flyvemdm_fleets_id');
   $migration->addKey($table, 'plugin_flyvemdm_policies_id', 'plugin_flyvemdm_policies_id');

   $table = 'glpi_plugin_flyvemdm_invitations';
   $migration->addKey($table, 'users_id', 'users_id');
   $migration->addKey($table, 'entities_id', 'entities_id');
   $migration->addKey($table, 'documents_id', 'documents_id');

   $table = 'glpi_plugin_flyvemdm_taskstatuses';
   $migration->addKey($table, 'agents_id', 'agents_id');
   $migration->addKey($table, 'tasks_id', 'tasks_id');
}
