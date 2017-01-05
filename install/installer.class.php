<?php
/**
 LICENSE

Copyright (C) 2016 Teclib'
Copyright (C) 2010-2016 by the FusionInventory Development Team.

This file is part of Flyve MDM Plugin for GLPI.

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
You should have received a copy of the GNU Affero General Public License
along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 ------------------------------------------------------------------------------
 @author    Thierry Bugier Pineau
 @copyright Copyright (c) 2016 Flyve MDM plugin team
 @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 @link      https://github.com/flyvemdm/backend
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

//use ApkParser\Config;

/**
 *
 * @author tbugier
 * @since 0.1.0
 *
 */
class PluginStorkmdmInstaller {

   const SERVICE_PROFILE_NAME = 'Stork MDM service profile';

   const DEFAULT_CIPHERS_LIST = 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:AES128:AES256:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK';

   const BACKEND_MQTT_USER = 'storkmdm-backend';

   // Order of this array is mandatory due tu dependancies on install and uninstall
   protected static $itemtypesToInstall = array(
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
   );

   protected static $currentVersion = null;

   protected $migration;

   /**
    * Autoloader for installation
    */
   public function autoload($classname) {
      // useful only for installer GLPi autoloader already handles inc/ folder
      $filename = dirname(__DIR__) . '/inc/' . strtolower(str_replace('PluginStorkmdm', '', $classname)). '.class.php';
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

      spl_autoload_register(array(__CLASS__, 'autoload'));

      $this->migration = new Migration(PLUGIN_STORKMDM_VERSION);
      $this->migration->setVersion(PLUGIN_STORKMDM_VERSION);

      // Load non-itemtype classes
      require_once PLUGIN_STORKMDM_ROOT . '/inc/notifiable.class.php';

      // adding DB model from sql file
      // TODO : migrate in-code DB model setup here
      if (self::getCurrentVersion() == '') {
         // Setup DB model
         $version = str_replace('.', '-', PLUGIN_STORKMDM_VERSION);

         $version = "";
         $dbFile = PLUGIN_STORKMDM_ROOT . "/install/mysql/plugin_storkmdm_empty.sql";
         if (!$DB->runFile($dbFile)) {
            $this->migration->displayWarning("Error creating tables : " . $DB->error(), true);
            return false;
         }

         $this->createInitialConfig();
      } else {
         if ($this->endsWith(PLUGIN_STORKMDM_VERSION, "-dev") || (version_compare(self::getCurrentVersion(), PLUGIN_STORKMDM_VERSION) != 0) ) {
            // TODO : Upgrade (or downgrade)
            $this->upgrade(self::getCurrentVersion());
         }
      }

      $this->migration->executeMigration();

      $this->createDirectories();
      $this->createFirstAccess();
      $this->createServiceProfileAccess();
      $this->createRegisteredProfileAccess();
      $this->createInactiveRegisteredProfileAccess();
      $this->createGuestProfileAccess();
      $this->createServiceUserAccount();
      $this->createPolicies();
      $this->createNotificationTargetInvitation();
      $this->createNotificationTargetAccountvalidation();
      $this->createJobs();

      Config::setConfigurationValues('storkmdm', array('version' => PLUGIN_STORKMDM_VERSION));

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
         $profile->fields["name"] = $DB->escape(__($name, "storkmdm"));
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
      if (! file_exists(STORKMDM_PACKAGE_PATH)) {
         if (! mkdir(STORKMDM_PACKAGE_PATH, 0770, true)) {
            $this->migration->displayWarning("Cannot create " . STORKMDM_PACKAGE_PATH . " directory");
         } else {
            if (! $htAccessHandler = fopen(STORKMDM_PACKAGE_PATH . "/.htaccess", "w")) {
               fwrite($htAccessHandler, "allow from all\n") or $this->migration->displayWarning("Cannot create .htaccess file in packages directory");
               fclose($htAccessHandler);
            } else {
               // TODO : echo and flush a success message for this operation
            }
         }
      }

      if (! file_exists(STORKMDM_FILE_PATH)) {
         if (! mkdir(STORKMDM_FILE_PATH, 0770, true)) {
            $this->migration->displayWarning("Cannot create " . STORKMDM_FILE_PATH . " directory");
         } else {
            if (! $htAccessHandler = fopen(STORKMDM_FILE_PATH . "/.htaccess", "w")) {
               fwrite($htAccessHandler, "allow from all\n") or $this->migration->displayWarning("Cannot create .htaccess file in packages directory");
               fclose($htAccessHandler);
            } else {
               // TODO : echo and flush a success message for this operation
            }
         }
      }
   }

   public static function getCurrentVersion() {
      if (self::$currentVersion === NULL) {
         $config = \Config::getConfigurationValues("storkmdm", array('version'));
         if (!isset($config['version'])) {
            self::$currentVersion = '';
         } else {
            self::$currentVersion = $config['version'];
         }
      }
      return self::$currentVersion;
   }

   /**
    * Give all rights on the plugin to the profile of the current user
    */
   protected function createFirstAccess() {
      $profileRight = new ProfileRight();

      $profileRight->updateProfileRights($_SESSION['glpiactiveprofile']['id'], array(
            PluginStorkmdmProfile::$rightname         => PluginStorkmdmProfile::RIGHT_STORKMDM_USE,
            PluginStorkmdmInvitation::$rightname      => CREATE | READ | UPDATE | DELETE | PURGE,
            PluginStorkmdmAgent::$rightname           => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginStorkmdmFleet::$rightname           => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginStorkmdmPackage::$rightname         => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginStorkmdmFile::$rightname            => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginStorkmdmGeolocation::$rightname     => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginStorkmdmPolicy::$rightname          => READ,
            PluginStorkmdmPolicyCategory::$rightname  => READ,
            PluginStorkmdmWellknownpath::$rightname   => ALLSTANDARDRIGHT,
            PluginStorkmdmEntityconfig::$rightname    => READ
                                                         | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_DEVICE_COUNT_LIMIT
                                                         | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_APP_DOWNLOAD_URL
                                                         | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_INVITATION_TOKEN_LIFE,
            PluginStorkmdmInvitationLog::$rightname   => READ,
      ));
   }

   protected function createServiceProfileAccess() {
      // create profile for service account (provides the API key allowing self account cezation for registered users)
      $profileId = self::getOrCreateProfile(
            self::SERVICE_PROFILE_NAME,
            __("service StorkMDM user's profile. Created by Stork MDM - do NOT modify this comment.", "storkmdm")
            );
      Config::setConfigurationValues('storkmdm', array('service_profiles_id' => $profileId));
      $profileRight = new ProfileRight();
      $profileRight->updateProfileRights($profileId, array(
            Entity::$rightname                     => CREATE | UPDATE,
            User::$rightname                       => CREATE,
            Profile::$rightname                    => READ
      ));
   }

   /**
    * Setup rights for registered users profile
    */
   protected function createRegisteredProfileAccess() {
      // create profile for registered users
      $profileId = self::getOrCreateProfile(
            __("Stork MDM registered users", "storkmdm"),
            __("registered StorkMDM users. Created by Stork MDM - do NOT modify this comment.", "storkmdm")
      );
      Config::setConfigurationValues('storkmdm', array('registered_profiles_id' => $profileId));
      $profileRight = new ProfileRight();
      $profileRight->updateProfileRights($profileId, array(
            PluginStorkmdmAgent::$rightname           => READ | UPDATE | DELETE | PURGE | READNOTE | UPDATENOTE, // No create right
            PluginStorkmdmInvitation::$rightname      => ALLSTANDARDRIGHT,
            PluginStorkmdmFleet::$rightname           => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginStorkmdmPackage::$rightname         => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginStorkmdmFile::$rightname            => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginStorkmdmGeolocation::$rightname     => READ | PURGE,
            PluginStorkmdmWellknownpath::$rightname   => READ,
            PluginStorkmdmPolicy::$rightname          => READ,
            PluginStorkmdmPolicyCategory::$rightname  => READ,
            PluginStorkmdmProfile::$rightname         => PluginStorkmdmProfile::RIGHT_STORKMDM_USE,
            PluginStorkmdmEntityconfig::$rightname    => READ
                                                         | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_APP_DOWNLOAD_URL
                                                         | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_INVITATION_TOKEN_LIFE,
            PluginStorkmdmInvitationlog::$rightname   => READ,
            Config::$rightname                        => READ,
            User::$rightname                          => ALLSTANDARDRIGHT,
            Profile::$rightname                       => CREATE,
            Entity::$rightname                        => CREATE,
            Computer::$rightname                      => READ,
            Software::$rightname                      => READ,
            NetworkPort::$rightname                   => READ,
            CommonDropdown::$rightname                => READ,
      ));
      $profile = new Profile();
      $profile->update([
            'id'                 => $profileId,
            '_password_update'   => 1
      ]);
   }

   /**
    * Setup rights for inactive registered users profile
    */
   protected function createInactiveRegisteredProfileAccess() {
      // create profile for registered users
      $profileId = self::getOrCreateProfile(
            __("Stork MDM inactive registered users", "storkmdm"),
            __("inactive registered StorkMDM users. Created by Stork MDM - do NOT modify this comment.", "storkmdm")
            );
      Config::setConfigurationValues('storkmdm', array('inactive_registered_profiles_id' => $profileId));
   }

   protected function createGuestProfileAccess() {
      // create profile for guest users
      $profileId = self::getOrCreateProfile(
            __("Stork MDM guest users", "storkmdm"),
            __("guest StorkMDM users. Created by Stork MDM - do NOT modify this comment.", "storkmdm")
      );
      Config::setConfigurationValues('storkmdm', array('guest_profiles_id' => $profileId));
      $profileRight = new ProfileRight();
      $profileRight->updateProfileRights($profileId, array(
            PluginStorkmdmAgent::$rightname           => READ | CREATE,
            PluginStorkmdmFile::$rightname           => READ,
            PluginStorkmdmPackage::$rightname           => READ,
      ));
   }

   /**
    * Create policies in DB
    */
   protected function createPolicies() {
      global $DB;

      $policy = new PluginStorkmdmPolicy();
      $policyTable = PluginStorkmdmPolicy::getTable();
      foreach(self::getPolicies() as $policyData) {
         $symbol = $policyData['symbol'];
         $rows = $policy->find("`symbol`='$symbol'");

         if (count($rows) == 0) {
            // Create only non existing policy objects
            $policyData['type_data'] = json_encode($policyData['type_data'], JSON_UNESCAPED_SLASHES);
            $policy->add($policyData);
         } else {
            // Update default value and recommended value for existing policy objects
            $policy2 = new PluginStorkmdmPolicy();
            $policy2->getFromDBBySymbol($symbol);
            $policy2->update(array(
                  'id'                 => $policy2->getID(),
                  'default_value'      => $policyData['default_value'],
                  'recommended_value'  => $policyData['recommended_value'],
            ));
         }
      }
   }

   /**
    * Create service account
    */
   protected static function createServiceUserAccount() {
      $user = new User();

      $config = Config::getConfigurationValues('storkmdm', array('service_profiles_id'));
      $profile = new Profile();
      $profile->getFromDB($config['service_profiles_id']);

      if (!$user->getIdByName(PluginStorkmdmConfig::SERVICE_ACCOUNT_NAME)) {
         if (!$user->add([
               'name'            => PluginStorkmdmConfig::SERVICE_ACCOUNT_NAME,
               'comment'         => 'StorkMDM service account',
               'firstname'       => 'Plugin Storkmdm',
               'password'        => '42',
               'personal_token'  => User::getUniquePersonalToken(),
               '_profiles_id'    => $profile->getID(),
               'language'        => $_SESSION['glpilanguage']     // Propagate language preference to service account
         ])) {
            die ('Could not create the service account');
         }
      }
   }

   protected function getNotificationTargetInvitationEvents() {
      return array(
            PluginStorkmdmNotificationTargetInvitation::EVENT_GUEST_INVITATION => array(
                  'itemtype'        => PluginStorkmdmInvitation::class,
                  'name'            => __('User invitation', "storkmdm"),
                  'subject'         => __('You have been invited to join Flyve MDM', 'storkmdm'),
                  'content_text'    => __('Hi,\n\n

Please join the Flyve Mobile Device Management system by downloading
and installing the Flyve MDM application for Android from the following link.\n\n

##storkmdm.download_app##\n\n

If you\'re viewing this email from a computer flash the QR code you see below
with the Flyve MDM Application.\n\n

If you\'re viewing this email from your device to enroll then tap the
following link.\n\n

##storkmdm.enroll_url##\n\n

Regards,

', 'storkmdm'),
                  'content_html'    => __('Hi,\n\n

Please join the Flyve Mobile Device Management system by downloading
and installing the Flyve MDM application for Android from the following link.\n\n

##storkmdm.download_app##\n\n

<img src="cid:##storkmdm.qrcode##" alt="Enroll QRCode" title="Enroll QRCode" width="128" height="128">\n\n

Regards,

', 'storkmdm')
            )
      );
   }

   public function createNotificationTargetInvitation() {
      // Create the notification template
      $notification = new Notification();
      $template = new NotificationTemplate();
      $translation = new NotificationTemplateTranslation();
      $notificationTarget = new PluginStorkmdmNotificationTargetInvitation();

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
                  'content_html'             => $contentHtml
            ]);

            // Create the notification
            $notificationId = $notification->add([
                  'name'                     => addcslashes($data['name'], "'\""),
                  'comment'                  => '',
                  'entities_id'              => 0,
                  'is_recursive'             => 1,
                  'is_active'                => 1,
                  'itemtype'                 => $itemtype,
                  'notificationtemplates_id' => $templateId,
                  'event'                    => $event,
                  'mode'                     => 'mail'
            ]);

            $notificationTarget->add([
                  'items_id'           => Notification::USER,
                  'type'               => Notification::USER_TYPE,
                  'notifications_id'   => $notificationId
            ]);

         }
      }
   }

   protected function getNotificationTargetRegistrationEvents() {
      return array(
            PluginStorkmdmNotificationTargetAccountvalidation::EVENT_SELF_REGISTRATION => array(
                  'itemtype'        => PluginStorkmdmAccountvalidation::class,
                  'name'            => __('Self registration', "storkmdm"),
                  'subject'         => __('Please, activate your Flyve MDM account', 'storkmdm'),
                  'content_text'    => __('Hi,\n\n

You or someone else created an account on Flyve MDM with your email address.\n\n

If you did not created an account, please ignore this email.\n\n

If you created an acount, please activate it with the link below. It is active for ##storkmdm.activation_delay##.\n\n

##storkmdm.registration_url##\n\n

After activation of your account, please login here\n\n

##storkmdm.webapp_url##\n\n

Regards,

', 'storkmdm'),
                  'content_html'    => __('Hi,\n\n

You or someone else created an account on Flyve MDM with your email address.\n\n

If you did not created an account, please ignore this email.\n\n

If you created an acount, please activate it with the link below. It is active for ##storkmdm.activation_delay##.\n\n

##storkmdm.registration_url##\n\n

After activation of your account, please login here\n\n

##storkmdm.webapp_url##\n\n

Regards,

', 'storkmdm')
            ),
            PluginStorkmdmNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND => array(
                  'itemtype'        => PluginStorkmdmAccountvalidation::class,
                  'name'            => __('End of trial reminder', "storkmdm"),
                  'subject'         => __('Your Flyve MDM trial will end soon', 'storkmdm'),
                  'content_text'    => __('Hi,\n\n

Hi,\n\n

You created an account on the demo platform of Flyve MDM recently. Your trial period will end soon.\n\n

We hope you enjoyed Flyve MDM. If you want to use it, please contact us at contact@teclib.com.\n\n


Regards,

', 'storkmdm'),
                  'content_html'    => __('Hi,\n\n

Hi,\n\n

You created an account on the demo platform of Flyve MDM recently. Your trial period will end soon.\n\n

We hope you enjoyed Flyve MDM. If you want to use it, please contact us at <a href="mailto:contact@teclib.com">contact@teclib.com</a>.\n\n

Regards,

', 'storkmdm')
            )

      );
   }

   public function createNotificationTargetAccountvalidation() {
      // Create the notification template
      $notification = new Notification();
      $template = new NotificationTemplate();
      $translation = new NotificationTemplateTranslation();
      $notificationTarget = new PluginStorkmdmNotificationTargetInvitation();

      foreach ($this->getNotificationTargetRegistrationEvents() as $event => $data) {
         $itemtype = $data['itemtype'];
         if (count($template->find("`itemtype`='$itemtype' AND `name`='" . $data['name'] . "'")) < 1) {
            // Add template
            $templateId = $template->add([
                  'name'      => addcslashes($data['name'], "'\""),
                  'comment'   => '',
                  'itemtype'  => $itemtype
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
                  'content_html'             => $contentHtml
            ]);

            // Create the notification
            $notificationId = $notification->add([
                  'name'                     => addcslashes($data['name'], "'\""),
                  'comment'                  => '',
                  'entities_id'              => 0,
                  'is_recursive'             => 1,
                  'is_active'                => 1,
                  'itemtype'                 => $itemtype,
                  'notificationtemplates_id' => $templateId,
                  'event'                    => $event,
                  'mode'                     => 'mail'
            ]);

            $notificationTarget->add([
                  'items_id'           => Notification::USER,
                  'type'               => Notification::USER_TYPE,
                  'notifications_id'   => $notificationId
            ]);

         }
      }
   }

   protected function upgrade($fromVersion) {
      $toVersion   = str_replace('.', '-', PLUGIN_STORKMDM_VERSION);

      switch ($fromVersion) {
         default:
      }
      if ($this->endsWith(PLUGIN_STORKMDM_VERSION, "-dev")) {
         if (is_readable(__DIR__ . "/update_dev.php") && is_file(__DIR__ . "/update_dev.php")) {
            include __DIR__ . "/update_dev.php";
            if (function_exists('update_dev')) {
               update_dev($this->migration);
            }
         }
      }

      $this->createPolicies();
      $this->createJobs();
   }

   protected function createJobs() {

      CronTask::Register('PluginStorkmdmMqttupdatequeue', 'UpdateTopics', MINUTE_TIMESTAMP,
            array(
                  'comment'   => __('Update retained MQTT topics for fleet policies', 'storkmdm'),
                  'mode'      => CronTask::MODE_EXTERNAL
            ));

      CronTask::Register('PluginStorkmdmAccountvalidation', 'CleanupAccountActivation', 12 * HOUR_TIMESTAMP,
            array(
                  'comment'   => __('Remove expired account activations (demo mode)', 'storkmdm'),
                  'mode'      => CronTask::MODE_EXTERNAL
            ));

      CronTask::Register('PluginStorkmdmAccountvalidation', 'DisableExpiredTrial', 12 * HOUR_TIMESTAMP,
            array(
                  'comment'   => __('Disable expired accounts (demo mode)', 'storkmdm'),
                  'mode'      => CronTask::MODE_EXTERNAL
            ));

      CronTask::Register('PluginStorkmdmAccountvalidation', 'RemindTrialExpiration', 12 * HOUR_TIMESTAMP,
            array(
                  'comment'   => __('Remind imminent end of trial period (demo mode)', 'storkmdm'),
                  'mode'      => CronTask::MODE_EXTERNAL
            ));
   }

   /**
    * http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    * @param unknown $haystack
    * @param unknown $needle
    */
   protected function startsWith($haystack, $needle) {
      // search backwards starting from haystack length characters from the end
      return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
   }

   /**
    * http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    * @param unknown $haystack
    * @param unknown $needle
    */
   protected function endsWith($haystack, $needle) {
      // search forward starting from end minus needle length characters
      return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
   }

   /**
    * Uninstall the plugin
    * @return boolean true (assume success, needs enhancement)
    */
   public function uninstall() {
      $this->rrmdir(GLPI_PLUGIN_DOC_DIR . "/storkmdm");

      $this->deleteRelations();
      $this->deleteNotificationTargetInvitation();
      $this->deleteNotificationTargetAccountvalidation();
      $this->deleteProfileRights();
      $this->deleteProfiles();
      $this->deleteTables();
      $this->deleteDisplayPreferences();
      // Cron jobs deletion handled by GLPi

      $config = new Config();
      $config->deleteByCriteria(array('context' => 'storkmdm'));

      return true;
   }

   /**
    * Cannot use the method from PluginStorkmdmToolbox if the plugin is being uninstalled
    * @param string $dir
    */
   protected function rrmdir($dir) {
      if (file_exists($dir) && is_dir($dir)) {
         $objects = scandir($dir);
         foreach ( $objects as $object ) {
            if ($object != "." && $object != "..") {
               if (filetype($dir . "/" . $object) == "dir")
                  $this->rrmdir($dir . "/" . $object);
               else
                  unlink($dir . "/" . $object);
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
      global $CFG_GLPI;

      $MdmMqttUser = 'storkmdm-backend';
      $MdmMqttPassword = PluginStorkmdmMqttuser::getRandomPassword();

      // New config management provided by GLPi

      $instanceId = base64_encode(openssl_random_pseudo_bytes(64, $crypto_strong));
      $newConfig = [
            'mqtt_broker_address'            => '',
            'mqtt_broker_internal_address'   => '127.0.0.1',
            'mqtt_broker_port'               => '1883',
            'mqtt_broker_tls'                => '0',
            'mqtt_use_client_cert'           => '0',
            'mqtt_broker_tls_ciphers'        => self::DEFAULT_CIPHERS_LIST,
            'mqtt_user'                      => self::BACKEND_MQTT_USER,
            'mqtt_passwd'                    => $MdmMqttPassword,
            'instance_id'                    => $instanceId,
            'registered_profiles_id'         => '',
            'guest_profiles_id'              => '',
            'service_profiles_id'            => '',
            'debug_enrolment'                => '0',
            'debug_noexpire'                 => '0',
            'ssl_cert_url'                   => '',
            'default_device_limit'           => '0',
            'default_agent_url'              => PLUGIN_STORKMDM_AGENT_DOWNLOAD_URL,
            'android_bugcollecctor_url'      => '',
            'android_bugcollector_login'     => '',
            'android_bugcollector_passwd'    => '',
            'webapp_url'                     => '',
            'demo_mode'                      => '0',
            'inactive_registered_profiles_id'=> '',
      ];
      Config::setConfigurationValues("storkmdm", $newConfig);
      $this->createBackendMqttUser(self::BACKEND_MQTT_USER, $MdmMqttPassword);
   }

   /**
    * Create MQTT user for the backend and save credentials
    * @param unknown $MdmMqttUser
    * @param unknown $MdmMqttPassword
    */
   protected function createBackendMqttUser($MdmMqttUser, $MdmMqttPassword) {
      global $DB;

      // Create mqtt credentials for the plugin
      $mqttUser = new PluginStorkmdmMqttuser();

      // Check the MQTT user account for the plugin exists
      if (!$mqttUser->getFromDBByQuery("WHERE `user`='$MdmMqttUser'")) {
         // Create the MQTT user account for the plugin
         if (! $mqttUser->add([
               'user'            => $MdmMqttUser,
               'password'        => $MdmMqttPassword,
               'enabled'         => '1',
               '_acl'            => [[
                     'topic'           => '#',
                     'access_level'    => PluginStorkmdmMqttacl::MQTTACL_READ_WRITE
               ]],
         ])) {
            // Failed to create the account
            $this->migration->displayWarning('Unable to create the MQTT account for StorkMDM : ' . $DB->error());
         } else {
            // Check the ACL has been created
            $aclList = $mqttUser->getACLs();
            $mqttAcl = array_shift($aclList);
            if ($mqttAcl === null) {
               $this->migration->displayWarning('Unable to create the MQTT ACL for StorkMDM : ' . $DB->error());
            }

            // Save MQTT credentials in configuration
            Config::setConfigurationValues("storkmdm", array('mqtt_user'       => $MdmMqttUser, 'mqtt_passwd'     => $MdmMqttPassword));
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
      $text = '<p>' . addcslashes(str_replace('\n', '<br>', $text), "'\"") . '</p>';
      return $text;
   }

   static public function getPolicyCategories() {
      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $categories = [
            [
                  'name'                                 => __('Security', 'storkmdm'),
            ],
            [
                  'name'                                 => __('Authentication', 'storkmdm'),
            ],
            [
                  'name'                                 => __('Password', 'storkmdm'),
            ],
            [
                  'name'                                 => __('Encryption', 'storkmdm'),
            ],
            [
                  'name'                                 => __('Peripherals', 'storkmdm'),
            ],
            [
                  'name'                                 => __('Deployment', 'storkmdm'),
            ],
      ];

      // Restore user's locale
      Session::loadLanguage($currentLocale);

      return $categories;
   }

   /**
    * @return array policies to add in DB on install
    */
   static public function getPolicies() {
      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $policies = [
            [
                  'name'                                 => __('Password enabled', 'storkmdm'),
                  'symbol'                               => 'passwordEnabled',
                  'group'                                => 'policies',
                  'type'                                 => 'dropdown',
                  'type_data'                            => [
                        "PASSWORD_NONE"                  => __('No', 'storkmdm'),
                        "PASSWORD_PIN"                   => __('Pin', 'storkmdm'),
                        "PASSWORD_PASSWD"                => __('Password', 'storkmdm')
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('Password enabled description', 'storkmdm'),
                  'default_value'                        => 'PASSWORD_NONE',
                  'recommended_value'                    => 'PASSWORD_PIN',
            ],

            [
                  'name'                                 => __('Minimum password length', 'storkmdm'),
                  'symbol'                               => 'passwordMinLength',
                  'group'                                => 'policies',
                  'type'                                 => 'int',
                  'type_data'                            => [
                        "min"                            => 0,
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('Set the required number of characters for the password. For example, you can require PIN or passwords to have at least six characters', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '6',
            ],

            [
                  'name'                                 => __('Password quality', 'storkmdm'),
                  'symbol'                               => 'passwordQuality',
                  'group'                                => 'policies',
                  'type'                                 => 'dropdown',
                  'type_data'                            => [
                        "PASSWORD_QUALITY_UNSPECIFIED"   => __('Unspecified', 'storkmdm'),
                        "PASSWORD_QUALITY_SOMETHING"     => __('Something', 'storkmdm'),
                        "PASSWORD_QUALITY_NUMERIC"       => __('Numeric', 'storkmdm'),
                        "PASSWORD_QUALITY_ALPHABETIC"    => __('Alphabetic', 'storkmdm'),
                        "PASSWORD_QUALITY_ALPHANUMERIC"  => __('Alphanumeric', 'storkmdm'),
                        "PASSWORD_QUALITY_COMPLEX"       => __('Complex', 'storkmdm')
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('Complexity of allowed password', 'storkmdm'),
                  'default_value'                        => 'PASSWORD_QUALITY_UNSPECIFIED',
                  'recommended_value'                    => 'PASSWORD_QUALITY_UNSPECIFIED',
            ],

            [
                  'name'                                 => __('Minimum letters required in password', 'storkmdm'),
                  'symbol'                               => 'passwordMinLetters',
                  'group'                                => 'policies',
                  'type'                                 => 'int',
                  'type_data'                            => [
                        "min"                            => 0,
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of letters required in the password for all admins or a particular one', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Minimum lowercase letters required in password', 'storkmdm'),
                  'symbol'                               => 'passwordMinLowerCase',
                  'group'                                => 'policies',
                  'type'                                 => 'int',
                  'type_data'                            => [
                        "min"                            => 0,
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of lowercase letters required in the password for all admins or a particular one', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '1',
            ],

            [
                  'name'                                 => __('Minimum non-letter characters required in password', 'storkmdm'),
                  'symbol'                               => 'passwordMinNonLetter',
                  'group'                                => 'policies',
                  'type'                                 => 'int',
                  'type_data'                            => [
                        "min"                            => 0,
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of non-letter characters required in the password for all admins or a particular one', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Minimum numerical digits required in password', 'storkmdm'),
                  'symbol'                               => 'passwordMinNumeric',
                  'group'                                => 'policies',
                  'type'                                 => 'int',
                  'type_data'                            => [
                        "min"                            => 0,
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of numerical digits required in the password for all admins or a particular one', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '1',
            ],

            [
                  'name'                                 => __('Minimum symbols required in password', 'storkmdm'),
                  'symbol'                               => 'passwordMinSymbols',
                  'group'                                => 'policies',
                  'type'                                 => 'int',
                  'type_data'                            => [
                        "min"                            => 0,
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of symbols required in the password for all admins or a particular one', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Minimum uppercase letters required in password', 'storkmdm'),
                  'symbol'                               => 'passwordMinUpperCase',
                  'group'                                => 'policies',
                  'type'                                 => 'int',
                  'type_data'                            => [
                        "min"                            => 0,
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of uppercase letters required in the password for all admins or a particular one', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '1',
            ],

            [
                  'name'                                 => __('Maximum failed password attemps for wipe', 'storkmdm'),
                  'symbol'                               => 'MaximumFailedPasswordsForWipe',
                  'group'                                => 'policies',
                  'type'                                 => 'int',
                  'type_data'                            => [
                        "min"                            => 0,
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('Number of consecutive failed attemps of unlock the device to wipe', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '5',
            ],

            [
                  'name'                                 => __('Maximum time to lock (milliseconds)', 'storkmdm'),
                  'symbol'                               => 'MaximumTimeToLock',
                  'group'                                => 'policies',
                  'type'                                 => 'int',
                  'type_data'                            => [
                        "min"                            => 0,
                  ],
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 3,
                  'comment'                              => __('Maximum time to lock the device in milliseconds', 'storkmdm'),
                  'default_value'                        => '60000',
                  'recommended_value'                    => '60000',
            ],

            [
                  'name'                                 => __('Internal Storage encryption', 'storkmdm'),
                  'symbol'                               => 'storageEncryption',
                  'group'                                => 'encryption',
                  'type'                                 => 'bool',
                  'type_data'                            => '',
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 4,
                  'comment'                              => __('Force internal storage encryption', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Disable Camera', 'storkmdm'),
                  'symbol'                               => 'disableCamera',
                  'group'                                => 'camera',
                  'type'                                 => 'bool',
                  'type_data'                            => '',
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 5,
                  'comment'                              => __('Prevent usage of the Camera', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Deploy application', 'storkmdm'),
                  'symbol'                               => 'deployApp',
                  'group'                                => 'application',
                  'type'                                 => 'deployapp',
                  'type_data'                            => '',
                  'unicity'                              => 0,
                  'plugin_storkmdm_policycategories_id'  => 6,
                  'comment'                              => __('Deploy an application on the device', 'storkmdm'),
                  'default_value'                        => '',
                  'recommended_value'                    => '',
            ],

            [
                  'name'                                 => __('Remove application', 'storkmdm'),
                  'symbol'                               => 'removeApp',
                  'group'                                => 'application',
                  'type'                                 => 'removeapp',
                  'type_data'                            => '',
                  'unicity'                              => 0,
                  'plugin_storkmdm_policycategories_id'  => 6,
                  'comment'                              => __('Uninstall an application on the device', 'storkmdm'),
                  'default_value'                        => '',
                  'recommended_value'                    => '',
            ],

            [
                  'name'                                 => __('Deploy file', 'storkmdm'),
                  'symbol'                               => 'deployFile',
                  'group'                                => 'file',
                  'type'                                 => 'deployfile',
                  'type_data'                            => '',
                  'unicity'                              => 0,
                  'plugin_storkmdm_policycategories_id'  => 6,
                  'comment'                              => __('Deploy a file on the device', 'storkmdm'),
                  'default_value'                        => '',
                  'recommended_value'                    => '',
            ],

            [
                  'name'                                 => __('Remove file', 'storkmdm'),
                  'symbol'                               => 'removeFile',
                  'group'                                => 'file',
                  'type'                                 => 'removefile',
                  'type_data'                            => '',
                  'unicity'                              => 0,
                  'plugin_storkmdm_policycategories_id'  => 6,
                  'comment'                              => __('Uninstall a file on the device', 'storkmdm'),
                  'default_value'                        => '',
                  'recommended_value'                    => '',
            ],

            [
                  'name'                                 => __('Disable Wifi', 'storkmdm'),
                  'symbol'                               => 'disableWifi',
                  'group'                                => 'connectivity',
                  'type'                                 => 'bool',
                  'type_data'                            => '',
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 5,
                  'comment'                              => __('Disable wifi connectivity', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Disable Bluetooth', 'storkmdm'),
                  'symbol'                               => 'disableBluetooth',
                  'group'                                => 'connectivity',
                  'type'                                 => 'bool',
                  'type_data'                            => '',
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 5,
                  'comment'                              => __('Disable Bluetooth connectivity', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Disable GPS', 'storkmdm'),
                  'symbol'                               => 'disableGPS',
                  'group'                                => 'connectivity',
                  'type'                                 => 'bool',
                  'type_data'                            => '',
                  'unicity'                              => 1,
                  'plugin_storkmdm_policycategories_id'  => 5,
                  'comment'                              => __('Disable GPS', 'storkmdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

      ];

      // Restore user's locale
      Session::loadLanguage($currentLocale);

      return $policies;
   }

   protected function deleteNotificationTargetInvitation() {
      global $DB;

      // Define DB tables
      $tableTargets      = getTableForItemType('NotificationTarget');
      $tableNotification = getTableForItemType('Notification');
      $tableTranslations = getTableForItemType('NotificationTemplateTranslation');
      $tableTemplates    = getTableForItemType('NotificationTemplate');

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
         $query = "DELETE FROM `$tableTemplates`
                  WHERE `itemtype` = '$itemtype' AND `name`='" . $data['name'] . "'";
         $DB->query($query);

         // Delete notification targets
         $query = "DELETE FROM `$tableTargets`
                   WHERE `notifications_id` IN (
                   SELECT `id` FROM `$tableNotification` WHERE `itemtype` = '$itemtype' AND `event`='$event')";
         $DB->query($query);

         // Delete notifications
         $query = "DELETE FROM `$tableNotification`
                   WHERE `itemtype` = '$itemtype' AND `event`='$event'";
         $DB->query($query);
      }
   }

   protected function deleteNotificationTargetAccountvalidation() {
      global $DB;

      // Define DB tables
      $tableTargets      = getTableForItemType('NotificationTarget');
      $tableNotification = getTableForItemType('Notification');
      $tableTranslations = getTableForItemType('NotificationTemplateTranslation');
      $tableTemplates    = getTableForItemType('NotificationTemplate');

      foreach ($this->getNotificationTargetRegistrationEvents() as $event => $data) {
         $itemtype = $data['itemtype'];
         $name = $data['name'];
         //TODO : implement cleanup
         // Delete translations
         $query = "DELETE FROM `$tableTranslations`
                   WHERE `notificationtemplates_id` IN (
                   SELECT `id` FROM `$tableTemplates` WHERE `itemtype` = '$itemtype' AND `name`='$name')";
         $DB->query($query);

         // Delete notification templates
         $query = "DELETE FROM `$tableTemplates`
                   WHERE `itemtype` = '$itemtype' AND `name`='" . $data['name'] . "'";
         $DB->query($query);

         // Delete notification targets
         $query = "DELETE FROM `$tableTargets`
                   WHERE `notifications_id` IN (
                   SELECT `id` FROM `$tableNotification` WHERE `itemtype` = '$itemtype' AND `event`='$event')";
         $DB->query($query);

         // Delete notifications
         $query = "DELETE FROM `$tableNotification`
                   WHERE `itemtype` = '$itemtype' AND `event`='$event'";
         $DB->query($query);
      }
   }

   protected function deleteTables() {
      global $DB;

      $tables = array(
            PluginStorkmdmAgent::getTable(),
            PluginStorkmdmEntityconfig::getTable(),
            PluginStorkmdmFile::getTable(),
            PluginStorkmdmInvitationlog::getTable(),
            PluginStorkmdmFleet::getTable(),
            PluginStorkmdmFleet_Policy::getTable(),
            PluginStorkmdmGeolocation::getTable(),
            PluginStorkmdmInvitation::getTable(),
            PluginStorkmdmMqttacl::getTable(),
            PluginStorkmdmMqttlog::getTable(),
            PluginStorkmdmMqttupdatequeue::getTable(),
            PluginStorkmdmMqttuser::getTable(),
            PluginStorkmdmPackage::getTable(),
            PluginStorkmdmPolicy::getTable(),
            PluginStorkmdmPolicyCategory::getTable(),
            PluginStorkmdmWellknownpath::getTable(),
            PluginStorkmdmAccountvalidation::getTable(),
      );

      foreach ($tables as $table) {
         $DB->query("DROP TABLE IF EXISTS `$table`");
      }
   }

   protected  function deleteProfiles() {
      $config = Config::getConfigurationValues('storkmdm', array('registered_profiles_id', 'guest_profiles_id'));
      $registeredProfileId = $config['registered_profiles_id'];
      $guestProfileId = $config['guest_profiles_id'];

      $profile = new Profile();
      $profile->getFromDB($registeredProfileId);
      if (!$profile->deleteFromDB()) {
         // TODO : log or warn for not deletion of the profile
      } else {
         $profileUser= new Profile_User();
         $profileUser->deleteByCriteria(array('profiles_id' => $registeredProfileId), true);
      }

      $profile->getFromDB($guestProfileId);
      if (!$profile->deleteFromDB()) {
         // TODO : log or warn for not deletion of the profile
      } else {
         $profileUser= new Profile_User();
         $profileUser->deleteByCriteria(array('profiles_id' => $guestProfileId), true);
      }
   }

   protected function deleteProfileRights() {
      $rights = array(
            PluginStorkmdmAgent::$rightname,
            PluginStorkmdmFile::$rightname,
            PluginStorkmdmFleet::$rightname,
            PluginStorkmdmGeolocation::$rightname,
            PluginStorkmdmInvitation::$rightname,
            PluginStorkmdmInvitationlog::$rightname,
            PluginStorkmdmPackage::$rightname,
            PluginStorkmdmPolicy::$rightname,
            PluginStorkmdmProfile::$rightname,
            PluginStorkmdmWellknownpath::$rightname,
      );
      foreach ($rights as $right) {
         ProfileRight::deleteProfileRights(array($right));
         unset($_SESSION["glpiactiveprofile"][$right]);
      }
   }

   protected function deleteRelations() {
      $pluginItemtypes = array(
            'PluginStorkmdmAgent',
            'PluginStorkmdmEntityconfig',
            'PluginStorkmdmFile',
            'PluginStorkmdmFleet',
            'PluginStorkmdmGeolocation',
            'PluginStorkmdmInvitation',
            'PluginStorkmdmPackage',
            'PluginStorkmdmPolicy',
            'PluginStorkmdmPolicyCategory',
            'PluginStorkmdmWellknownpath'
      );
      foreach ($pluginItemtypes as $pluginItemtype) {
         foreach (array('Notepad', 'DisplayPreference', 'DropdownTranslation', 'Log', 'Bookmark') as $itemtype) {
            $item = new $itemtype();
            $item->deleteByCriteria(array('itemtype' => $pluginItemtype));
         }
      }
   }

   protected function deleteDisplayPreferences() {
      // To cleanup display preferences if any
      //$displayPreference = new DisplayPreference();
      //$displayPreference->deleteByCriteria(array("`num` >= " . PluginStorkmdmConfig::RESERVED_TYPE_RANGE_MIN . " AND `num` <= " . PluginStorkmdmConfig::RESERVED_TYPE_RANGE_MAX));
   }
}
