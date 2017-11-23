<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2017 by the FusionInventory Development Team.
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
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *
 * @author tbugier
 * @since 0.1.0
 *
 */
class PluginFlyvemdmInstaller {

   const DEFAULT_CIPHERS_LIST = 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:AES128:AES256:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK';

   const BACKEND_MQTT_USER = 'flyvemdm-backend';

   // Order of this array is mandatory due tu dependancies on install and uninstall
   protected static $itemtypesToInstall = [
         'mqttuser',                      // Must be before config because config creates a mqtt user for the plugin
         'mqttacl',                       // Must be before config because config creates a mqtt ACL for the plugin
         'config',
         'entityconfig',
         'mqttlog',
         'agent',
         'package',
         'file',
         'fleet',
         'profile',
         'notificationtargetinvitation',
         'geolocation',
         'policy',
         'policycategory',
         'fleet_policy',
         'wellknownpath',
         'invitation',
         'invitationlog',
   ];

   protected static $currentVersion = null;

   protected $migration;

   /**
    * Autoloader for installation
    */
   public function autoload($classname) {
      // useful only for installer GLPI autoloader already handles inc/ folder
      $filename = dirname(__DIR__) . '/inc/' . strtolower(str_replace('PluginFlyvemdm', '', $classname)). '.class.php';
      if (is_readable($filename) && is_file($filename)) {
         include_once($filename);
         return true;
      }
   }

   /**
    *
    * Install the plugin
    *
    * @return boolean true (assume success, needs enhancement)
    *
    */
   public function install() {
      global $DB;

      spl_autoload_register([__CLASS__, 'autoload']);

      $this->migration = new Migration(PLUGIN_FLYVEMDM_VERSION);
      $this->migration->setVersion(PLUGIN_FLYVEMDM_VERSION);

      // Load non-itemtype classes
      require_once PLUGIN_FLYVEMDM_ROOT . '/inc/notifiable.class.php';

      // adding DB model from sql file
      // TODO : migrate in-code DB model setup here
      if (self::getCurrentVersion() == '') {
         // Setup DB model
         $dbFile = PLUGIN_FLYVEMDM_ROOT . "/install/mysql/plugin_flyvemdm_empty.sql";
         if (!$DB->runFile($dbFile)) {
            $this->migration->displayWarning("Error creating tables : " . $DB->error(), true);
            return false;
         }
         $this->createInitialConfig();
      } else {
         if ($this->endsWith(PLUGIN_FLYVEMDM_VERSION, "-dev") || (version_compare(self::getCurrentVersion(), PLUGIN_FLYVEMDM_VERSION) != 0)) {
            // TODO : Upgrade (or downgrade)
            $this->upgrade(self::getCurrentVersion());
         }
      }

      $this->migration->executeMigration();

      $this->createDirectories();
      $this->createFirstAccess();
      $this->createGuestProfileAccess();
      $this->createAgentProfileAccess();
      $this->createDefaultFleet();
      $this->createPolicies();
      $this->createNotificationTargetInvitation();
      $this->createJobs();
      $this->createRootEntityConfig();

      Config::setConfigurationValues('flyvemdm', ['version' => PLUGIN_FLYVEMDM_VERSION]);

      return true;
   }

   /**
    * Find a profile having the given comment, or create it
    * @param string $name    Name of the profile
    * @param string $comment Comment of the profile
    * @return integer profile ID
    */
   protected static function getOrCreateProfile($name, $comment) {
      global $DB;

      $comment = $DB->escape($comment);
      $profile = new Profile();
      $profiles = $profile->find("`comment`='$comment'");
      $row = array_shift($profiles);
      if ($row === null) {
         $profile->fields["name"] = $DB->escape(__($name, "flyvemdm"));
         $profile->fields["comment"] = $comment;
         $profile->fields["interface"] = "central";
         if ($profile->addToDB() === false) {
            die("Error while creating users profile : $name\n\n" . $DB->error());
         }
         return $profile->getID();
      } else {
         return $row['id'];
      }
   }

   public function createDirectories() {
      // Create directory for uploaded applications
      if (! file_exists(FLYVEMDM_PACKAGE_PATH)) {
         if (! mkdir(FLYVEMDM_PACKAGE_PATH, 0770, true)) {
            $this->migration->displayWarning("Cannot create " . FLYVEMDM_PACKAGE_PATH . " directory");
         } else {
            if (! $htAccessHandler = fopen(FLYVEMDM_PACKAGE_PATH . "/.htaccess", "w")) {
               fwrite($htAccessHandler, "allow from all\n") or $this->migration->displayWarning("Cannot create .htaccess file in packages directory\n");
               fclose($htAccessHandler);
            }
         }
      }

      // Create directory for uploaded files
      if (! file_exists(FLYVEMDM_FILE_PATH)) {
         if (! mkdir(FLYVEMDM_FILE_PATH, 0770, true)) {
            $this->migration->displayWarning("Cannot create " . FLYVEMDM_FILE_PATH . " directory");
         } else {
            if (! $htAccessHandler = fopen(FLYVEMDM_FILE_PATH . "/.htaccess", "w")) {
               fwrite($htAccessHandler, "allow from all\n") or $this->migration->displayWarning("Cannot create .htaccess file in files directory\n");
               fclose($htAccessHandler);
            }
         }
      }

      // Create cache directory for the template engine
      if (! file_exists(FLYVEMDM_TEMPLATE_CACHE_PATH)) {
         if (! mkdir(FLYVEMDM_TEMPLATE_CACHE_PATH, 0770, true)) {
            $this->migration->displayWarning("Cannot create " . FLYVEMDM_TEMPLATE_CACHE_PATH . " directory");
         }
      }
   }

   public static function getCurrentVersion() {
      if (self::$currentVersion === NULL) {
         $config = \Config::getConfigurationValues("flyvemdm", ['version']);
         if (!isset($config['version'])) {
            self::$currentVersion = '';
         } else {
            self::$currentVersion = $config['version'];
         }
      }
      return self::$currentVersion;
   }

   protected function createRootEntityConfig() {
      $entityConfig = new PluginFlyvemdmEntityconfig();
      $entityConfig->getFromDBByCrit([
         'entities_id' => '0'
      ]);
      if ($entityConfig->isNewItem()) {
         $entityConfig->add([
               'id'                 => '0',
               'entities_id'        => '0',
               'download_url'       => PLUGIN_FLYVEMDM_AGENT_DOWNLOAD_URL,
               'agent_token_life'   => PluginFlyvemdmAgent::DEFAULT_TOKEN_LIFETIME,
         ]);
      }
   }

   /**
    * Give all rights on the plugin to the profile of the current user
    */
   protected function createFirstAccess() {
      $profileRight = new ProfileRight();

      $newRights = [
         PluginFlyvemdmProfile::$rightname         => PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE,
         PluginFlyvemdmInvitation::$rightname      => ALLSTANDARDRIGHT,
         PluginFlyvemdmAgent::$rightname           => READ | UPDATE | PURGE | READNOTE | UPDATENOTE,
         PluginFlyvemdmFleet::$rightname           => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         PluginFlyvemdmPackage::$rightname         => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         PluginFlyvemdmFile::$rightname            => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         PluginFlyvemdmGeolocation::$rightname     => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         PluginFlyvemdmPolicy::$rightname          => READ,
         PluginFlyvemdmPolicyCategory::$rightname  => READ,
         PluginFlyvemdmWellknownpath::$rightname   => ALLSTANDARDRIGHT,
         PluginFlyvemdmEntityconfig::$rightname    => READ
                                                      | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT
                                                      | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL
                                                      | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE,
         PluginFlyvemdmInvitationLog::$rightname   => READ,
         PluginFlyvemdmTaskstatus::$rightname      => READ,
      ];

      $profileRight->updateProfileRights($_SESSION['glpiactiveprofile']['id'], $newRights);

      $_SESSION['glpiactiveprofile'] = $_SESSION['glpiactiveprofile'] + $newRights;
   }

   protected function createDefaultFleet() {
      $fleet = new PluginFlyvemdmFleet();
      if (!$fleet->getFromDBByQuery("WHERE `is_default` = '1' AND `entities_id` = '0'")) {
         $fleet->add([
               'name'         => __("not managed fleet", 'flyvemdm'),
               'entities_id'  => '0',
               'is_recursive' => '1',
               'is_default'   => '1',
         ]);
      }
   }

   /**
    * Create a profile for guest users
    */
   protected function createGuestProfileAccess() {
      // create profile for guest users
      $profileId = self::getOrCreateProfile(
            __("Flyve MDM guest users", "flyvemdm"),
            __("guest Flyve MDM users. Created by Flyve MDM - do NOT modify this comment.", "flyvemdm")
      );
      Config::setConfigurationValues('flyvemdm', ['guest_profiles_id' => $profileId]);
      $profileRight = new ProfileRight();
      $profileRight->updateProfileRights($profileId, [
         PluginFlyvemdmAgent::$rightname        => READ | CREATE,
         PluginFlyvemdmFile::$rightname         => READ,
         PluginFlyvemdmPackage::$rightname      => READ,
      ]);
   }

   /**
    * Create a profile for agent user accounts
    */
   protected function createAgentProfileAccess() {
      // create profile for guest users
      $profileId = self::getOrCreateProfile(
            __("Flyve MDM device agent users", "flyvemdm"),
            __("device agent  Flyve MDM users. Created by Flyve MDM - do NOT modify this comment.", "flyvemdm")
            );
      Config::setConfigurationValues('flyvemdm', ['agent_profiles_id' => $profileId]);
      $profileRight = new ProfileRight();
      $profileRight->updateProfileRights($profileId, [
         PluginFlyvemdmAgent::$rightname        => READ,
         PluginFlyvemdmFile::$rightname         => READ,
         PluginFlyvemdmPackage::$rightname      => READ,
         PluginFlyvemdmEntityconfig::$rightname => READ,
      ]);
   }

   /**
    * Create policies in DB
    */
   protected function createPolicies() {
      $policy = new PluginFlyvemdmPolicy();
      foreach (self::getPolicies() as $policyData) {
         $symbol = $policyData['symbol'];
         $rows = $policy->find("`symbol`='$symbol'");

         // Import the policy category or find the existing one
         $category = new PluginFlyvemdmPolicyCategory();
         $categoryId = $category->import([
            'completename' => $policyData['plugin_flyvemdm_policycategories_id']
         ]);
         $policyData['plugin_flyvemdm_policycategories_id'] = $categoryId;

         if (count($rows) == 0) {
            // Create only non existing policy objects
            $policyData['type_data'] = json_encode($policyData['type_data'], JSON_UNESCAPED_SLASHES);
            $policy->add($policyData);
         } else {
            // Update default value and recommended value for existing policy objects
            $policy2 = new PluginFlyvemdmPolicy();
            $policy2->getFromDBBySymbol($symbol);
            $policy2->update([
               'id'                                   => $policy2->getID(),
               'default_value'                        => $policyData['default_value'],
               'recommended_value'                    => $policyData['recommended_value'],
               'plugin_flyvemdm_policycategories_id'  => $categoryId
            ]);
         }
      }
   }

   protected function getNotificationTargetInvitationEvents() {
      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $notifications = [
            PluginFlyvemdmNotificationTargetInvitation::EVENT_GUEST_INVITATION => [
                  'itemtype'        => PluginFlyvemdmInvitation::class,
                  'name'            => __('User invitation', "flyvemdm"),
                  'subject'         => __('You have been invited to join Flyve MDM', 'flyvemdm'),
                  'content_text'    => __('Hi,

##user.firstname## ##user.realname## invited you to enroll your mobile device
in Flyve Mobile Device Managment (Flyve MDM). Flyve MDM allows administrators
to easily manage and administrate mobile devices. For more information,
please contact ##user.firstname## ##user.realname## to his email address
##user.email##.

Please join the Flyve Mobile Device Management system by downloading
and installing the Flyve MDM application for Android from the following link.

##flyvemdm.download_app##

If you\'re viewing this email from a computer flash the QR code you see below
with the Flyve MDM Application.

If you\'re viewing this email from your device to enroll then tap the
following link or copy it to your browser.

##flyvemdm.enroll_url##

Regards,

', 'flyvemdm'),
                  'content_html'    => __('Hi,

##user.firstname## ##user.realname## invited you to enroll your mobile device
in Flyve Mobile Device Managment (Flyve MDM). Flyve MDM allows administrators
to easily manage and administrate mobile devices. For more information,
please contact ##user.firstname## ##user.realname## to his email address
<a href="mailto:##user.email##?subject=Questions about Flyve MDM">
##user.email##</a>.

Please join the Flyve Mobile Device Management system by downloading
and installing the Flyve MDM application for Android from the following link.

<a href="##flyvemdm.download_app##">##flyvemdm.download_app##</a>

If you\'re viewing this email from a computer flash the QR code you see below
with the Flyve MDM Application.

If you\'re viewing this email from your device to enroll then tap the
following link or copy it to your browser.

<a href="##flyvemdm.enroll_url##">##flyvemdm.enroll_url##</a>

<img src="cid:##flyvemdm.qrcode##" alt="Enroll QRCode" title="Enroll QRCode" width="128" height="128">

Regards,

', 'flyvemdm')
            ]
      ];

      // Restore user's locale
      Session::loadLanguage($currentLocale);

      return $notifications;
   }

   public function createNotificationTargetInvitation() {
      // Create the notification template
      $notification = new Notification();
      $template = new NotificationTemplate();
      $translation = new NotificationTemplateTranslation();
      $notificationTarget = new PluginFlyvemdmNotificationTargetInvitation();
      $notification_notificationTemplate = new Notification_NotificationTemplate();

      foreach ($this->getNotificationTargetInvitationEvents() as $event => $data) {
         $itemtype = $data['itemtype'];
         if (count($template->find("`itemtype`='$itemtype' AND `name`='" . $data['name'] . "'")) < 1) {
            // Add template
            $templateId = $template->add([
                  'name'      => addcslashes($data['name'], "'\""),
                  'comment'   => '',
                  'itemtype'  => $itemtype,
            ]);

            // Add default translation
            if (!isset($data['content_html'])) {
               $contentHtml = self::convertTextToHtml($data['content_text']);
            } else {
               $contentHtml = self::convertTextToHtml($data['content_html']);
            }
            $translation->add([
                  'notificationtemplates_id' => $templateId,
                  'language'                 => '',
                  'subject'                  => addcslashes($data['subject'], "'\""),
                  'content_text'             => addcslashes($data['content_text'], "'\""),
                  'content_html'             => addcslashes(htmlentities($contentHtml), "'\""),
            ]);

            // Create the notification
            $notificationId = $notification->add([
                  'name'                     => addcslashes($data['name'], "'\""),
                  'comment'                  => '',
                  'entities_id'              => 0,
                  'is_recursive'             => 1,
                  'is_active'                => 1,
                  'itemtype'                 => $itemtype,
                  'event'                    => $event,
            ]);

            $notification_notificationTemplate->add([
                  'notifications_id'         => $notificationId,
                  'notificationtemplates_id' => $templateId,
                  'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            ]);

            $notificationTarget->add([
                  'items_id'           => Notification::USER,
                  'type'               => Notification::USER_TYPE,
                  'notifications_id'   => $notificationId
            ]);

         }
      }
   }

   /**
    * Upgrade the plugin to the current code version
    *
    * @param string $fromVersion
    */
   protected function upgrade($fromVersion) {
      switch ($fromVersion) {
         case '2.0.0':
            // Example : upgrade to version 3.0.0
            // $this->upgradeOneStep('3.0.0');
         case '3.0.0':
            // Example : upgrade to version 4.0.0
            // $this->upgradeOneStep('4.0.0');

         default:
      }
      if ($this->endsWith(PLUGIN_FLYVEMDM_VERSION, "-dev")) {
         $this->upgradeOneStep('dev');
      }

      $this->createDirectories();
      $this->createPolicies();
      $this->createJobs();
      $this->createAgentProfileAccess();
      $this->createGuestProfileAccess();
   }

   /**
    * Proceed to upgrade of the plugin to the given version
    *
    * @param string $toVersion
    */
   protected function upgradeOneStep($toVersion) {

      ini_set("max_execution_time", "0");
      ini_set("memory_limit", "-1");

      $suffix = str_replace('.', '_', $toVersion);
      $includeFile = __DIR__ . "/upgrade/update_to_$suffix.php";
      if (is_readable($includeFile) && is_file($includeFile)) {
         include_once $includeFile;
         $updateFunction = "plugin_flyvemdm_update_to_$suffix";
         if (function_exists($updateFunction)) {
            $this->migration->addNewMessageArea("Upgrade to $toVersion");
            $updateFunction($this->migration);
            $this->migration->displayMessage('Done');
         }
      }
   }

   protected function createJobs() {
      CronTask::Register(PluginFlyvemdmMqttupdatequeue::class, 'UpdateTopics', MINUTE_TIMESTAMP,
         [
            'comment'   => __('Update retained MQTT topics for fleet policies', 'flyvemdm'),
            'mode'      => CronTask::MODE_EXTERNAL
         ]);

      CronTask::Register(PluginFlyvemdmPackage::class, 'ParseApplication', MINUTE_TIMESTAMP,
         [
            'comment'   => __('Parse uploaded applications to collect metadata', 'flyvemdm'),
            'mode'      => CronTask::MODE_EXTERNAL
         ]);
   }

   /**
    * http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    * @param string $haystack
    * @param string $needle
    */
   protected function startsWith($haystack, $needle) {
      // search backwards starting from haystack length characters from the end
      return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
   }

   /**
    * http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    * @param string $haystack
    * @param string $needle
    */
   protected function endsWith($haystack, $needle) {
      // search forward starting from end minus needle length characters
      return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
   }

   /**
    * Uninstall the plugin
    * @return boolean true (assume success, needs enhancement)
    */
   public function uninstall() {
      $this->rrmdir(GLPI_PLUGIN_DOC_DIR . "/flyvemdm");

      $this->deleteRelations();
      $this->deleteNotificationTargetInvitation();
      $this->deleteProfileRights();
      $this->deleteProfiles();
      $this->deleteDisplayPreferences();
      $this->deleteTables();
      // Cron jobs deletion handled by GLPI

      $config = new Config();
      $config->deleteByCriteria(['context' => 'flyvemdm']);

      return true;
   }

   /**
    * Cannot use the method from PluginFlyvemdmToolbox if the plugin is being uninstalled
    * @param string $dir
    */
   protected function rrmdir($dir) {
      if (file_exists($dir) && is_dir($dir)) {
         $objects = scandir($dir);
         foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
               if (filetype($dir . "/" . $object) == "dir") {
                  $this->rrmdir($dir . "/" . $object);
               } else {
                  unlink($dir . "/" . $object);
               }
            }
         }
         reset($objects);
         rmdir($dir);
      }
   }

   /**
    * Generate default configuration for the plugin
    */
   protected function createInitialConfig() {
      $MdmMqttPassword = PluginFlyvemdmMqttuser::getRandomPassword();

      // New config management provided by GLPi
      $crypto_strong = null;
      $instanceId = base64_encode(openssl_random_pseudo_bytes(64, $crypto_strong));
      $newConfig = [
         'mqtt_broker_address'            => '',
         'mqtt_broker_internal_address'   => '127.0.0.1',
         'mqtt_broker_port'               => '1883',
         'mqtt_broker_tls_port'           => '8883',
         'mqtt_tls_for_clients'           => '0',
         'mqtt_tls_for_backend'           => '0',
         'mqtt_use_client_cert'           => '0',
         'mqtt_broker_tls_ciphers'        => self::DEFAULT_CIPHERS_LIST,
         'mqtt_user'                      => self::BACKEND_MQTT_USER,
         'mqtt_passwd'                    => $MdmMqttPassword,
         'instance_id'                    => $instanceId,
         'registered_profiles_id'         => '',
         'guest_profiles_id'              => '',
         'agent_profiles_id'              => '',
         'service_profiles_id'            => '',
         'debug_enrolment'                => '0',
         'debug_noexpire'                 => '0',
         'ssl_cert_url'                   => '',
         'default_device_limit'           => '0',
         'default_agent_url'              => PLUGIN_FLYVEMDM_AGENT_DOWNLOAD_URL,
         'android_bugcollecctor_url'      => '',
         'android_bugcollector_login'     => '',
         'android_bugcollector_passwd'    => '',
         'webapp_url'                     => '',
         'demo_mode'                      => '0',
         'demo_time_limit'                => '0',
         'inactive_registered_profiles_id'=> '',
         'computertypes_id'               => '0',
         'agentusercategories_id'         => '0',
         'invitation_deeplink'            => PLUGIN_FLYVEMDM_DEEPLINK
      ];
      Config::setConfigurationValues('flyvemdm', $newConfig);
      $this->createBackendMqttUser(self::BACKEND_MQTT_USER, $MdmMqttPassword);
   }

   /**
    * Create MQTT user for the backend and save credentials
    * @param string $MdmMqttUser
    * @param string $MdmMqttPassword
    */
   protected function createBackendMqttUser($MdmMqttUser, $MdmMqttPassword) {
      global $DB;

      // Create mqtt credentials for the plugin
      $mqttUser = new PluginFlyvemdmMqttuser();

      // Check the MQTT user account for the plugin exists
      if (!$mqttUser->getFromDBByQuery("WHERE `user`='$MdmMqttUser'")) {
         // Create the MQTT user account for the plugin
         if (! $mqttUser->add([
               'user'            => $MdmMqttUser,
               'password'        => $MdmMqttPassword,
               'enabled'         => '1',
               '_acl'            => [[
                     'topic'           => '#',
                     'access_level'    => PluginFlyvemdmMqttacl::MQTTACL_READ_WRITE
               ]],
         ])) {
            // Failed to create the account
            $this->migration->displayWarning('Unable to create the MQTT account for FlyveMDM : ' . $DB->error());
         } else {
            // Check the ACL has been created
            $aclList = $mqttUser->getACLs();
            $mqttAcl = array_shift($aclList);
            if ($mqttAcl === null) {
               $this->migration->displayWarning('Unable to create the MQTT ACL for FlyveMDM : ' . $DB->error());
            }

            // Save MQTT credentials in configuration
            Config::setConfigurationValues("flyvemdm", ['mqtt_user'       => $MdmMqttUser, 'mqtt_passwd'     => $MdmMqttPassword]);
         }
      }
   }


   /**
    * Generate HTML version of a text
    * Replaces \n by <br>
    * Encloses the text un <p>...</p>
    * Add anchor to URLs
    * @param string $text
    */
   protected static function convertTextToHtml($text) {
      $text = '<p>' . str_replace("\n\n", '</p><p>', $text) . '</p>';
      $text = '<p>' . str_replace("\n", '<br>', $text) . '</p>';
      return $text;
   }

   static public function getPolicyCategories() {
      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $categories = [
            [
                  'name'                                 => __('Security', 'flyvemdm'),
            ],
            [
                  'name'                                 => __('Authentication', 'flyvemdm'),
            ],
            [
                  'name'                                 => __('Password', 'flyvemdm'),
            ],
            [
                  'name'                                 => __('Encryption', 'flyvemdm'),
            ],
            [
                  'name'                                 => __('Peripherals', 'flyvemdm'),
            ],
            [
                  'name'                                 => __('Deployment', 'flyvemdm'),
            ],
      ];

      // Restore user's locale
      Session::loadLanguage($currentLocale);

      return $categories;
   }

   /**
    * Gets a reference array of policies installation or update of the plugin
    * the link to the policy category contains the complete name of the category
    * the caller is in charge of importing it and using the ID of the imported
    * category to set the foreign key.
    *
    * @return array policies to add in DB on install
    */
   static public function getPolicies() {

      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $policies = [
         [
            'name'                                 => __('Password enabled', 'flyvemdm'),
            'symbol'                               => 'passwordEnabled',
            'group'                                => 'policies',
            'type'                                 => 'dropdown',
            'type_data'                            => [
               "PASSWORD_NONE"                  => __('No', 'flyvemdm'),
               "PASSWORD_PIN"                   => __('Pin', 'flyvemdm'),
               "PASSWORD_PASSWD"                => __('Password', 'flyvemdm')
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('Enable the password', 'flyvemdm'),
            'default_value'                        => 'PASSWORD_NONE',
            'recommended_value'                    => 'PASSWORD_PIN',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Minimum password length', 'flyvemdm'),
            'symbol'                               => 'passwordMinLength',
            'group'                                => 'policies',
            'type'                                 => 'int',
            'type_data'                            => [
               "min"                            => 0,
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('Set the required number of characters for the password. For example, you can require PIN or passwords to have at least six characters', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '6',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Password quality', 'flyvemdm'),
            'symbol'                               => 'passwordQuality',
            'group'                                => 'policies',
            'type'                                 => 'dropdown',
            'type_data'                            => [
               "PASSWORD_QUALITY_UNSPECIFIED"   => __('Unspecified', 'flyvemdm'),
               "PASSWORD_QUALITY_SOMETHING"     => __('Something', 'flyvemdm'),
               "PASSWORD_QUALITY_NUMERIC"       => __('Numeric', 'flyvemdm'),
               "PASSWORD_QUALITY_ALPHABETIC"    => __('Alphabetic', 'flyvemdm'),
               "PASSWORD_QUALITY_ALPHANUMERIC"  => __('Alphanumeric', 'flyvemdm'),
               "PASSWORD_QUALITY_COMPLEX"       => __('Complex', 'flyvemdm')
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('Complexity of allowed password', 'flyvemdm'),
            'default_value'                        => 'PASSWORD_QUALITY_UNSPECIFIED',
            'recommended_value'                    => 'PASSWORD_QUALITY_UNSPECIFIED',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Minimum letters required in password', 'flyvemdm'),
            'symbol'                               => 'passwordMinLetters',
            'group'                                => 'policies',
            'type'                                 => 'int',
            'type_data'                            => [
               "min"                            => 0,
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('The minimum number of letters required in the password for all admins or a particular one', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Minimum lowercase letters required in password', 'flyvemdm'),
            'symbol'                               => 'passwordMinLowerCase',
            'group'                                => 'policies',
            'type'                                 => 'int',
            'type_data'                            => [
               "min"                            => 0,
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('The minimum number of lowercase letters required in the password for all admins or a particular one', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '1',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Minimum non-letter characters required in password', 'flyvemdm'),
            'symbol'                               => 'passwordMinNonLetter',
            'group'                                => 'policies',
            'type'                                 => 'int',
            'type_data'                            => [
               "min"                            => 0,
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('The minimum number of non-letter characters required in the password for all admins or a particular one', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Minimum numerical digits required in password', 'flyvemdm'),
            'symbol'                               => 'passwordMinNumeric',
            'group'                                => 'policies',
            'type'                                 => 'int',
            'type_data'                            => [
               "min"                            => 0,
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('The minimum number of numerical digits required in the password for all admins or a particular one', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '1',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Minimum symbols required in password', 'flyvemdm'),
            'symbol'                               => 'passwordMinSymbols',
            'group'                                => 'policies',
            'type'                                 => 'int',
            'type_data'                            => [
               "min"                            => 0,
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('The minimum number of symbols required in the password for all admins or a particular one', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Minimum uppercase letters required in password', 'flyvemdm'),
            'symbol'                               => 'passwordMinUpperCase',
            'group'                                => 'policies',
            'type'                                 => 'int',
            'type_data'                            => [
               "min"                            => 0,
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('The minimum number of uppercase letters required in the password for all admins or a particular one', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '1',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Maximum failed password attemps for wipe', 'flyvemdm'),
            'symbol'                               => 'MaximumFailedPasswordsForWipe',
            'group'                                => 'policies',
            'type'                                 => 'int',
            'type_data'                            => [
               "min"                            => 0,
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('Number of consecutive failed attemps of unlock the device to wipe', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '5',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Maximum time to lock (milliseconds)', 'flyvemdm'),
            'symbol'                               => 'MaximumTimeToLock',
            'group'                                => 'policies',
            'type'                                 => 'int',
            'type_data'                            => [
               "min"                            => 0,
            ],
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('Maximum time to lock the device in milliseconds', 'flyvemdm'),
            'default_value'                        => '60000',
            'recommended_value'                    => '60000',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Internal Storage encryption', 'flyvemdm'),
            'symbol'                               => 'storageEncryption',
            'group'                                => 'encryption',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Encryption',
            'comment'                              => __('Force internal storage encryption', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable Camera', 'flyvemdm'),
            'symbol'                               => 'disableCamera',
            'group'                                => 'camera',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Prevent usage of the Camera', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Deploy application', 'flyvemdm'),
            'symbol'                               => 'deployApp',
            'group'                                => 'application',
            'type'                                 => 'deployapp',
            'type_data'                            => '',
            'unicity'                              => 0,
            'plugin_flyvemdm_policycategories_id'  => 'Deployment',
            'comment'                              => __('Deploy an application on the device', 'flyvemdm'),
            'default_value'                        => '',
            'recommended_value'                    => '',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Remove application', 'flyvemdm'),
            'symbol'                               => 'removeApp',
            'group'                                => 'application',
            'type'                                 => 'removeapp',
            'type_data'                            => '',
            'unicity'                              => 0,
            'plugin_flyvemdm_policycategories_id'  => 'Deployment',
            'comment'                              => __('Uninstall an application on the device', 'flyvemdm'),
            'default_value'                        => '',
            'recommended_value'                    => '',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Deploy file', 'flyvemdm'),
            'symbol'                               => 'deployFile',
            'group'                                => 'file',
            'type'                                 => 'deployfile',
            'type_data'                            => '',
            'unicity'                              => 0,
            'plugin_flyvemdm_policycategories_id'  => 'Deployment',
            'comment'                              => __('Deploy a file on the device', 'flyvemdm'),
            'default_value'                        => '',
            'recommended_value'                    => '',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Remove file', 'flyvemdm'),
            'symbol'                               => 'removeFile',
            'group'                                => 'file',
            'type'                                 => 'removefile',
            'type_data'                            => '',
            'unicity'                              => 0,
            'plugin_flyvemdm_policycategories_id'  => 'Deployment',
            'comment'                              => __('Uninstall a file on the device', 'flyvemdm'),
            'default_value'                        => '',
            'recommended_value'                    => '',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable Wifi', 'flyvemdm'),
            'symbol'                               => 'disableWifi',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable wifi connectivity', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable Bluetooth', 'flyvemdm'),
            'symbol'                               => 'disableBluetooth',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable Bluetooth connectivity', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('use TLS', 'flyvemdm'),
            'symbol'                               => 'useTLS',
            'group'                                => 'MDM',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'MDM',
            'comment'                              => __('use TLS', 'flyvemdm'),
            'default_value'                        => '',
            'recommended_value'                    => '',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable roaming', 'flyvemdm'),
            'symbol'                               => 'disableRoaming',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable roaming', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable GPS', 'flyvemdm'),
            'symbol'                               => 'disableGPS',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable GPS', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '0',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable USB MTP', 'flyvemdm'),
            'symbol'                               => 'disableUsbMtp',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > USB',
            'comment'                              => __('Disable USB Media Transfert Protocol', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable USB PTP', 'flyvemdm'),
            'symbol'                               => 'disableUsbPtp',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > USB',
            'comment'                              => __('Disable USB Picture Transfert Protocol', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable USB ADB', 'flyvemdm'),
            'symbol'                               => 'disableUsbAdb',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > USB',
            'comment'                              => __('Disable USB Android Debug Bridge', 'flyvemdm'),
            'default_value'                        => '1',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable FM Radio', 'flyvemdm'),
            'symbol'                               => 'disableFmRadio',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable FM radio', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable Mobile line', 'flyvemdm'),
            'symbol'                               => 'disableMobileLine',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable Mobile line', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable Voice mail', 'flyvemdm'),
            'symbol'                               => 'disableVoiceMail',
            'group'                                => 'phone',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Phone',
            'comment'                              => __('Disable Voice mail', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable call auto answer', 'flyvemdm'),
            'symbol'                               => 'disableCallAutoAnswer',
            'group'                                => 'phone',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Phone',
            'comment'                              => __('Disable automatic answer to incoming phone calls', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable voice dictation', 'flyvemdm'),
            'symbol'                               => 'disableVoiceDictation',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable voice dictation', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable NFC', 'flyvemdm'),
            'symbol'                               => 'disableNfc',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable Near Field Contact', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable hotspot and tethering', 'flyvemdm'),
            'symbol'                               => 'disableHostpotTethering',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable hotspot and tethering', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable USB On-The-Go', 'flyvemdm'),
            'symbol'                               => 'disableUsbOnTheGo',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable USB On-The-Go', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable SMS and MMS', 'flyvemdm'),
            'symbol'                               => 'disableSmsMms',
            'group'                                => 'phone',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Phone',
            'comment'                              => __('Disable SMS and MMS', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable airplane mode', 'flyvemdm'),
            'symbol'                               => 'disableAirplaneMode',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('Disable airplane mode', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable status bar', 'flyvemdm'),
            'symbol'                               => 'disableStatusBar',
            'group'                                => 'ui',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > User interface',
            'comment'                              => __('Disable the status bar. Disabling the status bar blocks notifications, quick settings and other screen overlays that allow escaping from a single use device.', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable screen capture', 'flyvemdm'),
            'symbol'                               => 'disableScreenCapture',
            'group'                                => 'ui',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > User interface',
            'comment'                              => __('Disable screen capture. Disabling screen capture also prevents the content from being shown on display devices that do not have a secure video output.', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Reset password', 'flyvemdm'),
            'symbol'                               => 'resetPassword',
            'group'                                => 'policies',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Authentication > Password',
            'comment'                              => __('Force a new password for device unlock (the password needed to access the entire device) or the work profile challenge on the current user. This takes effect immediately.', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable speakerphone', 'flyvemdm'),
            'symbol'                               => 'disableSpeakerphone',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],

         [
            'name'                                 => __('Disable create VPN profiles', 'flyvemdm'),
            'symbol'                               => 'disableCreateVpnProfiles',
            'group'                                => 'connectivity',
            'type'                                 => 'bool',
            'type_data'                            => '',
            'unicity'                              => 1,
            'plugin_flyvemdm_policycategories_id'  => 'Security > Peripherals',
            'comment'                              => __('', 'flyvemdm'),
            'default_value'                        => '0',
            'recommended_value'                    => '0',
            'is_android_policy'                    => '1',
            'is_android_system'                    => '1',
            'is_apple_policy'                      => '0',
         ],
      ];

      // Restore user's locale
      Session::loadLanguage($currentLocale);

      return $policies;
   }

   protected function deleteNotificationTargetInvitation() {
      global $DB;

      // Define DB tables
      $tableTargets        = NotificationTarget::getTable();
      $tableNotification   = Notification::getTable();
      $tableTranslations   = NotificationTemplateTranslation::getTable();
      $tableTemplates      = NotificationTemplate::getTable();

      foreach ($this->getNotificationTargetInvitationEvents() as $event => $data) {
         $itemtype = $data['itemtype'];
         $name = $data['name'];
         //TODO : implement cleanup
         // Delete translations
         $query = "DELETE FROM `$tableTranslations`
                   WHERE `notificationtemplates_id` IN (
                   SELECT `id` FROM `$tableTemplates` WHERE `itemtype` = '$itemtype' AND `name`='$name')";
         $DB->query($query);

         // Delete notification templates
         $template = new NotificationTemplate();
         $template->deleteByCriteria(['itemtype' => $itemtype, 'name' => $name]);

         // Delete notification targets
         $query = "DELETE FROM `$tableTargets`
                   WHERE `notifications_id` IN (
                   SELECT `id` FROM `$tableNotification` WHERE `itemtype` = '$itemtype' AND `event`='$event')";
         $DB->query($query);

         // Delete notifications
         $notification = new Notification();
         $notification_notificationTemplate = new Notification_NotificationTemplate();
         $rows = $notification->find("`itemtype` = '$itemtype' AND `event` = '$event'");
         foreach ($rows as $row) {
            $notification_notificationTemplate->deleteByCriteria(['notifications_id' => $row['id']]);
            $notification->delete($row);
         }
      }
   }

   protected function deleteTables() {
      global $DB;

      $tables = [
         PluginFlyvemdmAgent::getTable(),
         PluginFlyvemdmEntityconfig::getTable(),
         PluginFlyvemdmFile::getTable(),
         PluginFlyvemdmInvitationlog::getTable(),
         PluginFlyvemdmFleet::getTable(),
         PluginFlyvemdmTask::getTable(),
         PluginFlyvemdmGeolocation::getTable(),
         PluginFlyvemdmInvitation::getTable(),
         PluginFlyvemdmMqttacl::getTable(),
         PluginFlyvemdmMqttlog::getTable(),
         PluginFlyvemdmMqttupdatequeue::getTable(),
         PluginFlyvemdmMqttuser::getTable(),
         PluginFlyvemdmPackage::getTable(),
         PluginFlyvemdmPolicy::getTable(),
         PluginFlyvemdmPolicyCategory::getTable(),
         PluginFlyvemdmWellknownpath::getTable(),
         PluginFlyvemdmTaskstatus::getTable(),
      ];

      foreach ($tables as $table) {
         $DB->query("DROP TABLE IF EXISTS `$table`");
      }
   }

   protected  function deleteProfiles() {
      $config = Config::getConfigurationValues('flyvemdm', ['guest_profiles_id']);

      foreach ($config as $profileId) {
         $profile = new Profile();
         $profile->getFromDB($profileId);
         if ($profile->deleteFromDB()) {
            $profileUser= new Profile_User();
            $profileUser->deleteByCriteria(['profiles_id' => $profileId], true);
         }
      }
   }

   protected function deleteProfileRights() {
      $rights = [
         PluginFlyvemdmAgent::$rightname,
         PluginFlyvemdmFile::$rightname,
         PluginFlyvemdmFleet::$rightname,
         PluginFlyvemdmGeolocation::$rightname,
         PluginFlyvemdmInvitation::$rightname,
         PluginFlyvemdmInvitationlog::$rightname,
         PluginFlyvemdmPackage::$rightname,
         PluginFlyvemdmPolicy::$rightname,
         PluginFlyvemdmProfile::$rightname,
         PluginFlyvemdmWellknownpath::$rightname,
      ];
      foreach ($rights as $right) {
         ProfileRight::deleteProfileRights([$right]);
         unset($_SESSION["glpiactiveprofile"][$right]);
      }
   }

   protected function deleteRelations() {
      $pluginItemtypes = [
         PluginFlyvemdmAgent::class,
         PluginFlyvemdmEntityconfig::class,
         PluginFlyvemdmFile::class,
         PluginFlyvemdmFleet::class,
         PluginFlyvemdmGeolocation::class,
         PluginFlyvemdmInvitation::class,
         PluginFlyvemdmPackage::class,
         PluginFlyvemdmPolicy::class,
         PluginFlyvemdmPolicyCategory::class,
         PluginFlyvemdmWellknownpath::class
      ];

      // Itemtypes from the core having relations to itemtypes of the plugin
      $itemtypes = [
         Notepad::class,
         DisplayPreference::class,
         DropdownTranslation::class,
         Log::class,
         Bookmark::class,
         SavedSearch::class
      ];
      foreach ($pluginItemtypes as $pluginItemtype) {
         foreach ($itemtypes as $itemtype) {
            if (class_exists($itemtype)) {
               $item = new $itemtype();
               $item->deleteByCriteria(['itemtype' => $pluginItemtype]);
            }
         }
      }
   }

   protected function deleteDisplayPreferences() {
      // To cleanup display preferences if any
      //$displayPreference = new DisplayPreference();
      //$displayPreference->deleteByCriteria(["`num` >= " . PluginFlyvemdmConfig::RESERVED_TYPE_RANGE_MIN . "
      //                                             AND `num` <= " . PluginFlyvemdmConfig::RESERVED_TYPE_RANGE_MAX]);
   }
}
