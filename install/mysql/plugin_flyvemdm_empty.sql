#Storkmdm Dump database on 2016-04-26 15:57


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_agents
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_agents`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_agents` (
  `id`                        int(11)                                           NOT NULL AUTO_INCREMENT,
  `name`                      varchar(255)                                      NOT NULL DEFAULT '',
  `version`                   varchar(255)                                      NOT NULL DEFAULT '',
  `computers_id`              int(11)                                           NOT NULL DEFAULT '0',
  `users_id`                  int(11)                                           NOT NULL DEFAULT '0',
  `wipe`                      int(1)                                            NOT NULL DEFAULT '0',
  `lock`                      int(1)                                            NOT NULL DEFAULT '0',
  `enroll_status`             enum('enrolled','unenrolling','unenrolled')       NOT NULL DEFAULT 'enrolled',
  `entities_id`               int(11)                                           NOT NULL DEFAULT '0',
  `plugin_flyvemdm_fleets_id` int(11)                                           DEFAULT NULL,
  `last_report`               datetime                                          DEFAULT NULL,
  `last_contact`              datetime                                          DEFAULT NULL,
  `is_online`                 tinyint(1)                                        NOT NULL DEFAULT '0',
  `certificate`               text                                              NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_entityconfigs
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_entityconfigs`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_entityconfigs` (
  `id`                        int(11)                                           NOT NULL DEFAULT '0',
  `entities_id`               int(11)                                           NOT NULL DEFAULT '0',
  `enroll_token`              varchar(255)                                      DEFAULT NULL,
  `agent_token_life`          varchar(255)                                      DEFAULT 'P7D',
  `support_name`              varchar(255)                                      DEFAULT NULL,
  `support_phone`             varchar(255)                                      DEFAULT NULL,
  `support_website`           varchar(255)                                      DEFAULT NULL,
  `support_email`             varchar(255)                                      DEFAULT NULL,
  `support_address`           text                                              NOT NULL,
  `managed`                   int(1)                                            NOT NULL DEFAULT '0',
  `download_url`              varchar(255)                                      NOT NULL DEFAULT '',
  `device_limit`              int(11)                                           NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_files
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_files`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_files` (
  `id`                        int(11)      NOT NULL AUTO_INCREMENT,
  `name`                      varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `source`                    varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `entities_id`               int(11) NOT  NULL DEFAULT '0',
  `version`                   int(11) NOT  NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_fleets
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_fleets`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_fleets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `is_recursive` int(1) NOT NULL DEFAULT '0',
  `is_default` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_geolocations
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_geolocations`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_geolocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computers_id` int(11) NOT NULL DEFAULT '0',
  `latitude` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `longitude` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_mqttacls
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_mqttacls`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_mqttacls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_flyvemdm_mqttusers_id` int(11) NOT NULL DEFAULT '0',
  `topic` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `access_level` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_flyvemdm_mqttusers_id`,`topic`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_mqttlogs
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_mqttlogs`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_mqttlogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime DEFAULT NULL,
  `direction` varchar(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'I for received message, O for sent message',
  `topic` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `message` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `topic` (`topic`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Received MQTT messages log';


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_mqttusers
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_mqttusers`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_mqttusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_packages
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_packages`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `alias` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `version_code` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `icon` text COLLATE utf8_unicode_ci NOT NULL,
  `filename` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL DEFAULT '0',
  `entities_id` int(11) NOT NULL DEFAULT '0',
  `dl_filename` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_wellknownpaths
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_wellknownpaths`;
CREATE TABLE IF NOT EXISTS `glpi_plugin_flyvemdm_wellknownpaths` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `glpi_plugin_flyvemdm_wellknownpaths` VALUES (1, '%SDCARD%',      '', 1);
INSERT INTO `glpi_plugin_flyvemdm_wellknownpaths` VALUES (2, '%DOCUMENTS%',   '', 0);
INSERT INTO `glpi_plugin_flyvemdm_wellknownpaths` VALUES (3, '%PHOTOS%',      '', 0);
INSERT INTO `glpi_plugin_flyvemdm_wellknownpaths` VALUES (4, '%MUSIC%',       '', 0);


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_policycategories
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_policycategories`;
CREATE TABLE `glpi_plugin_flyvemdm_policycategories` (
  `id`                                         int(11)        NOT NULL    AUTO_INCREMENT,
  `name`                                       varchar(255)   NOT NULL    DEFAULT '',
  `plugin_flyvemdm_policycategories_id`        int(11)        NOT NULL    DEFAULT '0',
  `completename`                               text           DEFAULT NULL,
  `comment`                                    text           DEFAULT NULL,
  `level`                                      int(11)        NOT NULL    DEFAULT '0',
  `sons_cache`                                 longtext       DEFAULT NULL,
  `ancestors_cache`                            longtext       DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `unicity` (`plugin_flyvemdm_policycategories_id`, `name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `glpi_plugin_flyvemdm_policycategories` VALUES (1, 'Security', '0', 'Security', '', 1, NULL, NULL);
INSERT INTO `glpi_plugin_flyvemdm_policycategories` VALUES (2, 'Authentication', '1', 'Security > Authentication', '', '2', NULL, NULL);
INSERT INTO `glpi_plugin_flyvemdm_policycategories` VALUES (3, 'Password', '2', 'Security > Authentication > Password', '', '3', NULL, NULL);
INSERT INTO `glpi_plugin_flyvemdm_policycategories` VALUES (4, 'Encryption', '1', 'Security > Encryption', '', '2', NULL, NULL);
INSERT INTO `glpi_plugin_flyvemdm_policycategories` VALUES (5, 'Peripherals', '1', 'Security > Peripherals', '', '2', NULL, NULL);
INSERT INTO `glpi_plugin_flyvemdm_policycategories` VALUES (6, 'Deployment', '0', 'Deployment', '', '1', NULL, NULL);


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_policies
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_policies`;
CREATE TABLE `glpi_plugin_flyvemdm_policies` (
  `id`                                         int(11)        NOT NULL AUTO_INCREMENT,
  `name`                                       varchar(255)   NOT NULL DEFAULT '',
  `group`                                      varchar(255)   NOT NULL DEFAULT '',
  `symbol`                                     varchar(255)   NOT NULL DEFAULT '',
  `type`                                       varchar(255)   NOT NULL DEFAULT '',
  `type_data`                                  text           DEFAULT NULL,
  `unicity`                                    tinyint(1)     NOT NULL DEFAULT '1',
  `plugin_flyvemdm_policycategories_id`        int(11)        NOT NULL DEFAULT '0',
  `comment`                                    text           DEFAULT NULL,
  `default_value`                              varchar(255)   NOT NULL DEFAULT '',
  `recommended_value`                          varchar(255)   NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_tasks
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_tasks`;
CREATE TABLE `glpi_plugin_flyvemdm_tasks` (
  `id`                                         int(11)      NOT NULL AUTO_INCREMENT,
  `plugin_flyvemdm_fleets_id`                  int(11)      NOT NULL DEFAULT '0',
  `plugin_flyvemdm_policies_id`                int(11)      NOT NULL DEFAULT '0',
  `value`                                      varchar(255) NOT NULL DEFAULT '',
  `itemtype`                                   varchar(255) DEFAULT NULL,
  `items_id`                                   int(11)      NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_invitations
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_invitations`;
CREATE TABLE `glpi_plugin_flyvemdm_invitations` (
  `id`                               int(11)                   NOT NULL AUTO_INCREMENT,
  `invitation_token`                 varchar(255)              NOT NULL DEFAULT '',
  `users_id`                         int(11)                   NOT NULL DEFAULT '0',
  `entities_id`                      int(11)                   NOT NULL DEFAULT '0',
  `documents_id`                     int(11)                   NOT NULL DEFAULT '0',
  `status`                           enum('pending','done')    NOT NULL DEFAULT 'pending',
  `expiration_date`                  datetime                  NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_mqttupdatequeues
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_mqttupdatequeues`;
CREATE TABLE `glpi_plugin_flyvemdm_mqttupdatequeues` (
  `id`                               int(11)                   NOT NULL AUTO_INCREMENT,
  `group`                            varchar(255)              NOT NULL DEFAULT '',
  `plugin_flyvemdm_fleets_id`        int(11)                   NOT NULL DEFAULT '0',
  `date`                             datetime                  NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status`                           enum('queued','done')     NOT NULL DEFAULT 'queued',
  PRIMARY KEY (`id`),
  INDEX `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_invitationlogs
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_invitationlogs`;
CREATE TABLE `glpi_plugin_flyvemdm_invitationlogs` (
  `id`                               int(11)                   NOT NULL AUTO_INCREMENT,
  `plugin_flyvemdm_invitations_id`   int(11)                   NOT NULL DEFAULT '0',
  `date_creation`                    datetime                  NOT NULL DEFAULT '0000-00-00 00:00:00',
  `event`                            varchar(255)              NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  INDEX `status` (`date_creation`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- Export de la structure de table glpi-flyvemdm. glpi_plugin_flyvemdm_taskstatuses
DROP TABLE IF EXISTS `glpi_plugin_flyvemdm_taskstatuses`;
CREATE TABLE `glpi_plugin_flyvemdm_taskstatuses` (
  `id`                                  int(11) NOT NULL AUTO_INCREMENT,
  `name`                                varchar(255) NOT NULL DEFAULT '',
  `date_creation`                       datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `date_mod`                            datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `plugin_flyvemdm_agents_id`           int(11) NOT NULL DEFAULT '0',
  `plugin_flyvemdm_tasks_id`  int(11) NOT NULL DEFAULT '0',
  `status`                              varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

