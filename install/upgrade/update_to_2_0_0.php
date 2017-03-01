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
              INDEX `status` (`date_creation`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

   $DB->query($query) or die("Could not create invitation logs table " . $DB->error());

   $profileRight = new ProfileRight();

   // Merge new rights into current profile
   $currentRights = ProfileRight::getProfileRights($_SESSION['glpiactiveprofile']['id']);
   $newRights = array_merge($currentRights, array(
         PluginFlyvemdmInvitation::$rightname      => CREATE | READ | UPDATE | DELETE | PURGE,
         PluginFlyvemdmInvitationlog::$rightname   => READ,
         PluginFlyvemdmGeolocation::$rightname     => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,

   ));
   $profileRight->updateProfileRights($_SESSION['glpiactiveprofile']['id'], $newRights);

   $table = PluginFlyvemdmAgent::getTable();
   if (! FieldExists($table, 'enroll_status')) {
      $query = "ALTER TABLE `glpi_plugin_flyvemdm_agents`
                ADD COLUMN `enroll_status` ENUM('enrolled', 'unenrolling', 'unenrolled') NOT NULL DEFAULT 'enrolled' AFTER `lock`";
      $DB->query($query) or die("Could upgrade table $table" . $DB->error());
   }

   $table = PluginFlyvemdmPolicy::getTable();
   $migration->addField($table, 'recommended_value', 'string', array('after' => 'default_value'));

   // remove download base URL setting
   Config::deleteConfigurationValues('flyvemdm', array('deploy_base_url'));

   // @since 0.6.0
   $migration->addField(PluginFlyvemdmAgent::getTable(), 'version', 'string', array('after' => 'name'));

   Config::setConfigurationValues('flyvemdm', array(
         'default_agent_url' => PLUGIN_FLYVEMDM_AGENT_DOWNLOAD_URL
   ));

   $config = Config::getConfigurationValues('flyvemdm', array('android_bugcollecctor'));
   if (!isset($config['android_bugcollecctor_url'])) {
      $config = [
            'android_bugcollecctor_url'      => '',
            'android_bugcollector_login'     => '',
            'android_bugcollector_passwd'    => '',
      ];
      Config::setConfigurationValues('flyvemdm', $config);
   }

   $migration->addField(PluginFlyvemdmFleet::getTable(), `is_recursive`, 'bool');
}