<?php

function update_to_2_0_0(Migration $migration) {
   global $DB;

   $migration->setVersion('2.0.0');

   ini_set("max_execution_time", "0");
   ini_set("memory_limit", "-1");

   // Create invitations table
   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_invitationlogs` (
              `id`                               int(11)                   NOT NULL AUTO_INCREMENT,
              `plugin_flyvemdm_invitations_id`   int(11)                   NOT NULL DEFAULT '0',
              `date_creation`                    datetime                  NOT NULL DEFAULT '0000-00-00 00:00:00',
              `event`                            varchar(255)              NOT NULL DEFAULT '',
              PRIMARY KEY (`id`),
              INDEX `plugin_flyvemdm_invitations_id` (`plugin_flyvemdm_invitations_id`),
              INDEX `date_creation` (`date_creation`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

   $DB->query($query) or die("Could not create invitation logs table " . $DB->error());

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

   $table = PluginFlyvemdmAgent::getTable();
   if (!$DB->fieldExists($table, 'enroll_status')) {
      $query = "ALTER TABLE `$table`
                ADD COLUMN `enroll_status` ENUM('enrolled', 'unenrolling', 'unenrolled') NOT NULL DEFAULT 'enrolled' AFTER `lock`";
      $DB->query($query) or die("Could upgrade table $table" . $DB->error());
   }
   $migration->addKey($table, 'computers_id', 'computers_id');
   $migration->addKey($table, 'users_id', 'users_id');
   $migration->addKey($table, 'entities_id', 'entities_id');

   $table = PluginFlyvemdmPolicy::getTable();
   $migration->addField($table, 'recommended_value', 'string', ['after' => 'default_value']);
   $migration->addKey($table, 'group', 'group');
   $migration->addKey($table, 'plugin_flyvemdm_policycategories_id', 'plugin_flyvemdm_policycategories_id');

   // remove download base URL setting
   Config::deleteConfigurationValues('flyvemdm', ['deploy_base_url']);

   $migration->addField(PluginFlyvemdmAgent::getTable(), 'version', 'string', ['after' => 'name']);

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

   $migration->addKey(PluginFlyvemdmEntityconfig::getTable(), 'entities_id', 'entities_id');
   $migration->addKey(PluginFlyvemdmFile::getTable(), 'entities_id', 'entities_id');

   $table = PluginFlyvemdmFleet::getTable();
   $migration->addField($table, 'is_recursive', 'bool');
   $migration->addKey($table, 'entities_id', 'entities_id');

   $migration->addKey(PluginFlyvemdmGeolocation::getTable(), 'computers_id', 'computers_id');
   $migration->addKey(PluginFlyvemdmMqttacl::getTable(), 'plugin_flyvemdm_mqttusers_id',
      'plugin_flyvemdm_mqttusers_id');

   $migration->addKey(PluginFlyvemdmPackage::getTable(), 'entities_id', 'entities_id');
   $migration->addKey(PluginFlyvemdmPolicyCategory::getTable(),
      'plugin_flyvemdm_policycategories_id', 'plugin_flyvemdm_policycategories_id');

   $table = PluginFlyvemdmTask::getTable();
   $migration->addKey($table, 'plugin_flyvemdm_fleets_id', 'plugin_flyvemdm_fleets_id');
   $migration->addKey($table, 'plugin_flyvemdm_policies_id', 'plugin_flyvemdm_policies_id');

   $table = PluginFlyvemdmInvitation::getTable();
   $migration->addKey($table, 'users_id', 'users_id');
   $migration->addKey($table, 'entities_id', 'entities_id');
   $migration->addKey($table, 'documents_id', 'documents_id');

   $migration->addKey(PluginFlyvemdmInvitationlog::getTable(), 'plugin_flyvemdm_invitations_id',
      'invitations_id');

   $table = PluginFlyvemdmTaskstatus::getTable();
   $migration->addKey($table, 'agents_id', 'agents_id');
   $migration->addKey($table, 'tasks_id', 'tasks_id');
}