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

   // Merge new rights into registered user profile
   $config = Config::getConfigurationValues('flyvemdm', array('registered_profiles_id'));
   $profileId = $config['registered_profiles_id'];
   $currentRights = ProfileRight::getProfileRights($profileId);
   $newRights = array_merge($currentRights, array(
         PluginFlyvemdmAgent::$rightname           => READ | UPDATE | DELETE | PURGE | READNOTE | UPDATENOTE, // No create right
         PluginFlyvemdmInvitation::$rightname      => ALLSTANDARDRIGHT,
         PluginFlyvemdmInvitationlog::$rightname   => READ,
         Config::$rightname                        => READ,
         Computer::$rightname                      => READ,
         Software::$rightname                      => READ,
         Entity::$rightname                        => READ,
         NetworkPort::$rightname                   => READ,
         PluginFlyvemdmWellknownpath::$rightname   => READ,
         PluginFlyvemdmEntityconfig::$rightname    => READ
         | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL
         | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE,
   ));
   $profileRight->updateProfileRights($profileId, $newRights);
   $profile = new Profile();
   $profile->update([
         'id'                 => $profileId,
         '_password_update'   => 1
   ]);

   $table = PluginFlyvemdmAgent::getTable();
   if (! FieldExists($table, 'enroll_status')) {
      $query = "ALTER TABLE `$table`
                ADD COLUMN `enroll_status` ENUM('enrolled', 'unenrolling', 'unenrolled') NOT NULL DEFAULT 'enrolled' AFTER `lock`";
      $DB->query($query) or die("Could upgrade table $table" . $DB->error());
   }

   $table = PluginFlyvemdmPolicy::getTable();
   $migration->addField($table, 'recommended_value', 'string', array('after' => 'default_value'));

   // remove download base URL setting
   Config::deleteConfigurationValues('flyvemdm', array('deploy_base_url'));

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

   $query = "CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_accountvalidations` (
              `id`                                int(11)                  NOT NULL AUTO_INCREMENT,
              `users_id`                          int(11)                  NOT NULL DEFAULT '0',
              `assigned_entities_id`              int(11)                  NOT NULL DEFAULT '0',
              `profiles_id`                       int(11)                  NOT NULL DEFAULT '0',
              `validation_pass`                   varchar(255)             NOT NULL DEFAULT '',
              `date_creation`                     datetime                 NOT NULL DEFAULT '0000-00-00 00:00:00',
              `date_end_trial`                    datetime                 NOT NULL DEFAULT '0000-00-00 00:00:00',
              `is_trial_ended`                    tinyint(1)               NOT NULL DEFAULT '0',
              `is_reminder_1_sent`                tinyint(1)               NOT NULL DEFAULT '0',
              `is_reminder_2_sent`                tinyint(1)               NOT NULL DEFAULT '0',
              `is_post_reminder_sent`             tinyint(1)               NOT NULL DEFAULT '0',
               PRIMARY KEY (`id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

   $DB->query($query) or die("Could not create account validations table " . $DB->error());

   $migration->changeField(PluginFlyvemdmAccountvalidation::getTable(), 'is_reminder_sent', 'is_reminder_1_sent', 'bool');
   $migration->addField(PluginFlyvemdmAccountvalidation::getTable(), 'is_reminder_2_sent', 'bool');
   $migration->addField(PluginFlyvemdmAccountvalidation::getTable(), 'is_post_reminder_sent', 'bool');

   $migration->addField(PluginFlyvemdmFleet::getTable(), `is_recursive`, 'bool');
}