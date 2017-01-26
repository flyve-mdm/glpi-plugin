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

/**
 *
 * @author tbugier
 * @since 0.1.0
 *
 */
class PluginFlyvemdmInstaller {

   const SERVICE_PROFILE_NAME = 'Flyve MDM service profile';

   const DEFAULT_CIPHERS_LIST = 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:ECDHE-RSA-RC4-SHA:ECDHE-ECDSA-RC4-SHA:AES128:AES256:RC4-SHA:HIGH:!aNULL:!eNULL:!EXPORT:!DES:!3DES:!MD5:!PSK';

   const BACKEND_MQTT_USER = 'flyvemdm-backend';

   const FLYVE_MDM_PRODUCT_WEBSITE     = 'www.flyve-mdm.com';

   const FLYVE_MDM_PRODUCT_GOOGLEPLUS  = 'https://plus.google.com/collection/c32TsB';

   const FLYVE_MDM_PRODUCT_TWITTER     = 'https://twitter.com/FlyveMDM';

   const FLYVE_MDM_PRODUCT_FACEBOOK    = 'https://www.facebook.com/Flyve-MDM-1625450937768377/';

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

      spl_autoload_register(array(__CLASS__, 'autoload'));

      $this->migration = new Migration(PLUGIN_FLYVEMDM_VERSION);
      $this->migration->setVersion(PLUGIN_FLYVEMDM_VERSION);

      // Load non-itemtype classes
      require_once PLUGIN_FLYVEMDM_ROOT . '/inc/notifiable.class.php';

      // adding DB model from sql file
      // TODO : migrate in-code DB model setup here
      if (self::getCurrentVersion() == '') {
         // Setup DB model
         $version = str_replace('.', '-', PLUGIN_FLYVEMDM_VERSION);

         $version = "";
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
      $this->createServiceProfileAccess();                     // Demo mode
      $this->createRegisteredProfileAccess();                  // Demo mode
      $this->createInactiveRegisteredProfileAccess();          // Demo mode
      $this->createGuestProfileAccess();
      $this->createServiceUserAccount();                       // Demo mode
      $this->createPolicies();
      $this->createNotificationTargetInvitation();
      $this->createSocialMediaIcons();                         // Demo mode
      $this->createNotificationTargetAccountvalidation();      // Demo mode
      $this->createJobs();
      $this->createDemoModeJobs();                             // Demo mode

      Config::setConfigurationValues('flyvemdm', array('version' => PLUGIN_FLYVEMDM_VERSION));

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
   }

   public static function getCurrentVersion() {
      if (self::$currentVersion === NULL) {
         $config = \Config::getConfigurationValues("flyvemdm", array('version'));
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

      $newRights = array(
            PluginFlyvemdmProfile::$rightname         => PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE,
            PluginFlyvemdmInvitation::$rightname      => CREATE | READ | UPDATE | DELETE | PURGE,
            PluginFlyvemdmAgent::$rightname           => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
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
      );

      $profileRight->updateProfileRights($_SESSION['glpiactiveprofile']['id'], $newRights);

      $_SESSION['glpiactiveprofile'] = $_SESSION['glpiactiveprofile'] + $newRights;
   }

   protected function createServiceProfileAccess() {
      // create profile for service account (provides the API key allowing self account cezation for registered users)
      $profileId = self::getOrCreateProfile(
            self::SERVICE_PROFILE_NAME,
            __("service FlyveMDM user's profile. Created by Flyve MDM - do NOT modify this comment.", "flyvemdm")
            );
      Config::setConfigurationValues('flyvemdm', array('service_profiles_id' => $profileId));
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
            __("Flyve MDM registered users", "flyvemdm"),
            __("registered FlyveMDM users. Created by Flyve MDM - do NOT modify this comment.", "flyvemdm")
      );
      Config::setConfigurationValues('flyvemdm', array('registered_profiles_id' => $profileId));
      $profileRight = new ProfileRight();
      $profileRight->updateProfileRights($profileId, array(
            PluginFlyvemdmAgent::$rightname           => READ | UPDATE | DELETE | PURGE | READNOTE | UPDATENOTE, // No create right
            PluginFlyvemdmInvitation::$rightname      => ALLSTANDARDRIGHT,
            PluginFlyvemdmFleet::$rightname           => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginFlyvemdmPackage::$rightname         => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginFlyvemdmFile::$rightname            => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            PluginFlyvemdmGeolocation::$rightname     => READ | PURGE,
            PluginFlyvemdmWellknownpath::$rightname   => READ,
            PluginFlyvemdmPolicy::$rightname          => READ,
            PluginFlyvemdmPolicyCategory::$rightname  => READ,
            PluginFlyvemdmProfile::$rightname         => PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE,
            PluginFlyvemdmEntityconfig::$rightname    => READ
                                                         | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL
                                                         | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE,
            PluginFlyvemdmInvitationlog::$rightname   => READ,
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
            __("Flyve MDM inactive registered users", "flyvemdm"),
            __("inactive registered FlyveMDM users. Created by Flyve MDM - do NOT modify this comment.", "flyvemdm")
            );
      Config::setConfigurationValues('flyvemdm', array('inactive_registered_profiles_id' => $profileId));
   }

   protected function createGuestProfileAccess() {
      // create profile for guest users
      $profileId = self::getOrCreateProfile(
            __("Flyve MDM guest users", "flyvemdm"),
            __("guest FlyveMDM users. Created by Flyve MDM - do NOT modify this comment.", "flyvemdm")
      );
      Config::setConfigurationValues('flyvemdm', array('guest_profiles_id' => $profileId));
      $profileRight = new ProfileRight();
      $profileRight->updateProfileRights($profileId, array(
            PluginFlyvemdmAgent::$rightname           => READ | CREATE,
            PluginFlyvemdmFile::$rightname           => READ,
            PluginFlyvemdmPackage::$rightname           => READ,
      ));
   }

   /**
    * Create policies in DB
    */
   protected function createPolicies() {
      global $DB;

      $policy = new PluginFlyvemdmPolicy();
      $policyTable = PluginFlyvemdmPolicy::getTable();
      foreach (self::getPolicies() as $policyData) {
         $symbol = $policyData['symbol'];
         $rows = $policy->find("`symbol`='$symbol'");

         if (count($rows) == 0) {
            // Create only non existing policy objects
            $policyData['type_data'] = json_encode($policyData['type_data'], JSON_UNESCAPED_SLASHES);
            $policy->add($policyData);
         } else {
            // Update default value and recommended value for existing policy objects
            $policy2 = new PluginFlyvemdmPolicy();
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

      $config = Config::getConfigurationValues('flyvemdm', array('service_profiles_id'));
      $profile = new Profile();
      $profile->getFromDB($config['service_profiles_id']);

      if (!$user->getIdByName(PluginFlyvemdmConfig::SERVICE_ACCOUNT_NAME)) {
         if (!$user->add([
               'name'            => PluginFlyvemdmConfig::SERVICE_ACCOUNT_NAME,
               'comment'         => 'FlyveMDM service account',
               'firstname'       => 'Plugin Flyvemdm',
               'password'        => '42',
               'personal_token'  => User::getUniquePersonalToken(),
               '_profiles_id'    => $profile->getID(),
               'is_active'       => '0',
         ])) {
            die ('Could not create the service account');
         }
      }
   }

   protected function getNotificationTargetInvitationEvents() {
      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $notifications = array(
            PluginFlyvemdmNotificationTargetInvitation::EVENT_GUEST_INVITATION => array(
                  'itemtype'        => PluginFlyvemdmInvitation::class,
                  'name'            => __('User invitation', "flyvemdm"),
                  'subject'         => __('You have been invited to join Flyve MDM', 'flyvemdm'),
                  'content_text'    => __('Hi,

Please join the Flyve Mobile Device Management system by downloading
and installing the Flyve MDM application for Android from the following link.

##flyvemdm.download_app##

If you\'re viewing this email from a computer flash the QR code you see below
with the Flyve MDM Application.

If you\'re viewing this email from your device to enroll then tap the
following link.

##flyvemdm.enroll_url##

Regards,

', 'flyvemdm'),
                  'content_html'    => __('Hi,

Please join the Flyve Mobile Device Management system by downloading
and installing the Flyve MDM application for Android from the following link.

##flyvemdm.download_app##

<img src="cid:##flyvemdm.qrcode##" alt="Enroll QRCode" title="Enroll QRCode" width="128" height="128">

Regards,

', 'flyvemdm')
            )
      );

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
      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $notifications = array(
            PluginFlyvemdmNotificationTargetAccountvalidation::EVENT_SELF_REGISTRATION => array(
                  'itemtype'        => PluginFlyvemdmAccountvalidation::class,
                  'name'            => __('Self registration', "flyvemdm"),
                  'subject'         => __('Flyve MDM Account Activation', 'flyvemdm'),
                  'content_text'    => __('Hi there,

You or someone else created an account on Flyve MDM with your email address.

If you did not register for an account, please discard this email message, we apologize for any inconveniences.

If you created an account, please activate it with the link below. The link will be active for ##flyvemdm.activation_delay##.

##flyvemdm.registration_url##

After activating your account, please login and enjoy Flyve MDM for ##flyvemdm.trial_duration##, entering :

##flyvemdm.webapp_url##

Regards,

', 'flyvemdm') . $this->getTextMailingSignature(),
                  'content_html'    => __('Hi there,

You or someone else created an account on Flyve MDM with your email address.

If you did not register for an account, please discard this email message, we apologize for any inconveniences.

If you created an account, please activate it with the link below. The link will be active for ##flyvemdm.activation_delay##.

<a href="##flyvemdm.registration_url##">##flyvemdm.registration_url##</a>

After activating your account, please login and <span style="text-weight: bold">enjoy Flyve MDM for ##flyvemdm.trial_duration##</span>, entering :

<a href="##flyvemdm.webapp_url##">##flyvemdm.webapp_url##</a>

Regards,

', 'flyvemdm') . $this->getHTMLMailingSignature()
            ),
            PluginFlyvemdmNotificationTargetAccountvalidation::EVENT_TRIAL_BEGIN => array(
                  'itemtype'        => PluginFlyvemdmAccountvalidation::class,
                  'name'            => __('Account activated', "flyvemdm"),
                  'subject'         => __('Get started with Flyve MDM', 'flyvemdm'),
                  'content_text'    => __('Hi there,

Thank you for joining us, you have successfully activated your Flyve MDM account!

Flyve MDM is an open source Mobile Device Management Solution that allows you to manage and control the entire mobile fleet of your organization, in just a few clicks!
Install or delete applications remotely, send files, erase data and/or lock your device if you lose it, and enjoy many other functionalities that will make your daily life easier!

To use it during your 90 days trial, sign in to ##flyvemdm.webapp_url##, with your account’s login.

We would love to hear whether you think Flyve MDM helps fulfill your goals or what we can do to improve. If you have any questions about getting started, we would be happy to help. Just send us an email to contact@flyve-mdm.com!

You want to upgrade?

You can upgrade to a full and unlimited Flyve MDM account at any time during your trial. Contact directly our experts to discuss your project and get a tailor-made quotation for your business! Email us at: sales@flyve-mdm.com!

Regards,

', 'flyvemdm') . $this->getTextMailingSignature(),
                  'content_html'    => __('Hi there,

Thank you for joining us, you have successfully activated your Flyve MDM account!

Flyve MDM is an open source Mobile Device Management Solution that allows you to manage and control the entire mobile fleet of your organization, in just a few clicks!
Install or delete applications remotely, send files, erase data and/or lock your device if you lose it, and enjoy many other functionalities that will make your daily life easier!

To use it during your 90 days trial, sign in to <a href="##flyvemdm.webapp_url##">##flyvemdm.webapp_url##</a>, with your account’s login.

We would love to hear whether you think Flyve MDM helps fulfill your goals or what we can do to improve. If you have any questions about getting started, we would be happy to help. Just send us an email to <a href="contact@flyve-mdm.com">contact@flyve-mdm.com</a>!

<span style="font-weight: bold;">You want to upgrade?</span>

You can upgrade to a full and unlimited Flyve MDM account at any time during your trial. Contact directly our experts to discuss your project and get a tailor-made quotation for your business! Email us at: <a href="mailto:sales@flyve-mdm.com">sales@flyve-mdm.com</a>!

Regards,

', 'flyvemdm') . $this->getHTMLMailingSignature()
            ),
            PluginFlyvemdmNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND_1 => array(
                  'itemtype'        => PluginFlyvemdmAccountvalidation::class,
                  'name'            => __('First trial reminder', "flyvemdm"),
                  'subject'         => __('Your Flyve MDM trial will end soon! - Only ##flyvemdm.days_remaining## left!', 'flyvemdm'),
                  'content_text'    => __('Hi there,

Your 90 days trial for ##flyvemdm.webapp_url## is coming to an end in ##flyvemdm.days_remaining## and we deeply hope you have been enjoying the experience!

Ready to upgrade?

To continue enjoying Flyve MDM features, contact our experts and get a personalized advice and quotation at: sales@flyve-mdm.com!

Regards,

', 'flyvemdm') . $this->getTextMailingSignature(),
                  'content_html'    => __('Hi there,

Your 90 days trial for <a href="##flyvemdm.webapp_url##">##flyvemdm.webapp_url##</a> is coming to an end in ##flyvemdm.days_remaining## and we deeply hope you have been enjoying the experience!

<span style="font-weight: bold;">Ready to upgrade?</span>

To continue enjoying Flyve MDM features, contact our experts and get a personalized advice and quotation at: <a href="mailto:sales@flyve-mdm.com">sales@flyve-mdm.com</a>!

Regards,

', 'flyvemdm') . $this->getHTMLMailingSignature()
            ),
            PluginFlyvemdmNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND_2 => array(
                  'itemtype'        => PluginFlyvemdmAccountvalidation::class,
                  'name'            => __('Second trial reminder', "flyvemdm"),
                  'subject'         => __('Your free Flyve MDM trial expires in ##flyvemdm.days_remaining##!', 'flyvemdm'),
                  'content_text'    => __('Hi there,

We want to give you a heads-up that in ##flyvemdm.days_remaining## your Flyve MDM trial comes to an end!

We would love to keep you as a customer, and there is still time to upgrade to a full and unlimited paid plan.

Ready to upgrade?

To continue enjoying Flyve MDM features, contact our experts and get a personalized advice and quotation at: sales@flyve-mdm.com!

Regards,

', 'flyvemdm') . $this->getTextMailingSignature(),
                  'content_html'    => __('Hi there,

We want to give you a heads-up that <span style="font-weight: bold;">in ##flyvemdm.days_remaining## your Flyve MDM trial comes to an end!</span>

We would love to keep you as a customer, and there is still time to upgrade to a full and unlimited paid plan.

<span style="font-weight: bold;">Ready to upgrade?</span>

To continue enjoying Flyve MDM features, contact our experts and get a personalized advice and quotation at: <a href="mailto:sales@flyve-mdm.com">sales@flyve-mdm.com</a>!

Regards,

', 'flyvemdm') . $this->getHTMLMailingSignature()
            ),
            PluginFlyvemdmNotificationTargetAccountvalidation::EVENT_POST_TRIAL_REMIND => array(
                  'itemtype'        => PluginFlyvemdmAccountvalidation::class,
                  'name'            => __('End of trial reminder', "flyvemdm"),
                  'subject'         => __('Your free Flyve MDM trial has expired.', 'flyvemdm'),
                  'content_text'    => __('Hi there,

The trial period for Flyve MDM has ended!

We hope you enjoyed our solution and that it helped you increase your productivity, saving you time and energy!

Upgrade to the next level!

Upgrade to a full and unlimited Flyve MDM account right now and continue benefiting from its numerous features! Contact directly our experts to discuss your project and get a tailor-made quotation for your business!
Email us at: sales@flyve-mdm.com, we will be happy to hear from you!

Regards,

', 'flyvemdm') . $this->getTextMailingSignature(),
                  'content_html'    => __('Hi there,

<span style="font-weight: bold;">The trial period for Flyve MDM has ended!</span>

We hope you enjoyed our solution and that it helped you increase your productivity, saving you time and energy!

<span style="font-weight: bold;">Upgrade to the next level!</span>

Upgrade to a full and unlimited Flyve MDM account right now and continue benefiting from its numerous features! Contact directly our experts to discuss your project and get a tailor-made quotation for your business!
Email us at: <a href="mailto:sales@flyve-mdm.com">sales@flyve-mdm.com</a>, we will be happy to hear from you!

Regards,

', 'flyvemdm') . $this->getHTMLMailingSignature()
            ),
      );

      // Restore user's locale
      Session::loadLanguage($currentLocale);

      return $notifications;
   }

   public function createNotificationTargetAccountvalidation() {
      // Create the notification template
      $notification = new Notification();
      $template = new NotificationTemplate();
      $translation = new NotificationTemplateTranslation();
      $notificationTarget = new PluginFlyvemdmNotificationTargetInvitation();

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
                  'content_html'             => addcslashes($contentHtml, "'\"")
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
      $toVersion   = str_replace('.', '-', PLUGIN_FLYVEMDM_VERSION);

      switch ($fromVersion) {
         default:
      }
      if ($this->endsWith(PLUGIN_FLYVEMDM_VERSION, "-dev")) {
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
      CronTask::Register('PluginFlyvemdmMqttupdatequeue', 'UpdateTopics', MINUTE_TIMESTAMP,
            array(
                  'comment'   => __('Update retained MQTT topics for fleet policies', 'flyvemdm'),
                  'mode'      => CronTask::MODE_EXTERNAL
            ));
   }

   protected function createDemoModeJobs() {
      CronTask::Register('PluginFlyvemdmAccountvalidation', 'CleanupAccountActivation', 12 * HOUR_TIMESTAMP,
            array(
                  'comment'   => __('Remove expired account activations (demo mode)', 'flyvemdm'),
                  'mode'      => CronTask::MODE_EXTERNAL
            ));

      CronTask::Register('PluginFlyvemdmAccountvalidation', 'DisableExpiredTrial', 12 * HOUR_TIMESTAMP,
            array(
                  'comment'   => __('Disable expired accounts (demo mode)', 'flyvemdm'),
                  'mode'      => CronTask::MODE_EXTERNAL
            ));

      CronTask::Register('PluginFlyvemdmAccountvalidation', 'RemindTrialExpiration', 12 * HOUR_TIMESTAMP,
            array(
                  'comment'   => __('Remind imminent end of trial period (demo mode)', 'flyvemdm'),
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
      $this->rrmdir(GLPI_PLUGIN_DOC_DIR . "/flyvemdm");

      $this->deleteRelations();
      $this->deleteNotificationTargetInvitation();
      $this->deleteNotificationTargetAccountvalidation();
      $this->deleteProfileRights();
      $this->deleteProfiles();
      $this->deleteDisplayPreferences();
      $this->deleteSocialMediaIcons();                      // Demo mode
      $this->deleteTables();
      // Cron jobs deletion handled by GLPi

      $config = new Config();
      $config->deleteByCriteria(array('context' => 'flyvemdm'));

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
      global $CFG_GLPI;

      $MdmMqttUser = 'flyvemdm-backend';
      $MdmMqttPassword = PluginFlyvemdmMqttuser::getRandomPassword();

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
            'default_agent_url'              => PLUGIN_FLYVEMDM_AGENT_DOWNLOAD_URL,
            'android_bugcollecctor_url'      => '',
            'android_bugcollector_login'     => '',
            'android_bugcollector_passwd'    => '',
            'webapp_url'                     => '',
            'demo_mode'                      => '0',
            'demo_time_limit'                => '0',
            'inactive_registered_profiles_id'=> '',
      ];
      Config::setConfigurationValues("flyvemdm", $newConfig);
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
            Config::setConfigurationValues("flyvemdm", array('mqtt_user'       => $MdmMqttUser, 'mqtt_passwd'     => $MdmMqttPassword));
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('Password enabled description', 'flyvemdm'),
                  'default_value'                        => 'PASSWORD_NONE',
                  'recommended_value'                    => 'PASSWORD_PIN',
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('Set the required number of characters for the password. For example, you can require PIN or passwords to have at least six characters', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '6',
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('Complexity of allowed password', 'flyvemdm'),
                  'default_value'                        => 'PASSWORD_QUALITY_UNSPECIFIED',
                  'recommended_value'                    => 'PASSWORD_QUALITY_UNSPECIFIED',
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of letters required in the password for all admins or a particular one', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of lowercase letters required in the password for all admins or a particular one', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '1',
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of non-letter characters required in the password for all admins or a particular one', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of numerical digits required in the password for all admins or a particular one', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '1',
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of symbols required in the password for all admins or a particular one', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('The minimum number of uppercase letters required in the password for all admins or a particular one', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '1',
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('Number of consecutive failed attemps of unlock the device to wipe', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '5',
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
                  'plugin_flyvemdm_policycategories_id'  => 3,
                  'comment'                              => __('Maximum time to lock the device in milliseconds', 'flyvemdm'),
                  'default_value'                        => '60000',
                  'recommended_value'                    => '60000',
            ],

            [
                  'name'                                 => __('Internal Storage encryption', 'flyvemdm'),
                  'symbol'                               => 'storageEncryption',
                  'group'                                => 'encryption',
                  'type'                                 => 'bool',
                  'type_data'                            => '',
                  'unicity'                              => 1,
                  'plugin_flyvemdm_policycategories_id'  => 4,
                  'comment'                              => __('Force internal storage encryption', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Disable Camera', 'flyvemdm'),
                  'symbol'                               => 'disableCamera',
                  'group'                                => 'camera',
                  'type'                                 => 'bool',
                  'type_data'                            => '',
                  'unicity'                              => 1,
                  'plugin_flyvemdm_policycategories_id'  => 5,
                  'comment'                              => __('Prevent usage of the Camera', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Deploy application', 'flyvemdm'),
                  'symbol'                               => 'deployApp',
                  'group'                                => 'application',
                  'type'                                 => 'deployapp',
                  'type_data'                            => '',
                  'unicity'                              => 0,
                  'plugin_flyvemdm_policycategories_id'  => 6,
                  'comment'                              => __('Deploy an application on the device', 'flyvemdm'),
                  'default_value'                        => '',
                  'recommended_value'                    => '',
            ],

            [
                  'name'                                 => __('Remove application', 'flyvemdm'),
                  'symbol'                               => 'removeApp',
                  'group'                                => 'application',
                  'type'                                 => 'removeapp',
                  'type_data'                            => '',
                  'unicity'                              => 0,
                  'plugin_flyvemdm_policycategories_id'  => 6,
                  'comment'                              => __('Uninstall an application on the device', 'flyvemdm'),
                  'default_value'                        => '',
                  'recommended_value'                    => '',
            ],

            [
                  'name'                                 => __('Deploy file', 'flyvemdm'),
                  'symbol'                               => 'deployFile',
                  'group'                                => 'file',
                  'type'                                 => 'deployfile',
                  'type_data'                            => '',
                  'unicity'                              => 0,
                  'plugin_flyvemdm_policycategories_id'  => 6,
                  'comment'                              => __('Deploy a file on the device', 'flyvemdm'),
                  'default_value'                        => '',
                  'recommended_value'                    => '',
            ],

            [
                  'name'                                 => __('Remove file', 'flyvemdm'),
                  'symbol'                               => 'removeFile',
                  'group'                                => 'file',
                  'type'                                 => 'removefile',
                  'type_data'                            => '',
                  'unicity'                              => 0,
                  'plugin_flyvemdm_policycategories_id'  => 6,
                  'comment'                              => __('Uninstall a file on the device', 'flyvemdm'),
                  'default_value'                        => '',
                  'recommended_value'                    => '',
            ],

            [
                  'name'                                 => __('Disable Wifi', 'flyvemdm'),
                  'symbol'                               => 'disableWifi',
                  'group'                                => 'connectivity',
                  'type'                                 => 'bool',
                  'type_data'                            => '',
                  'unicity'                              => 1,
                  'plugin_flyvemdm_policycategories_id'  => 5,
                  'comment'                              => __('Disable wifi connectivity', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Disable Bluetooth', 'flyvemdm'),
                  'symbol'                               => 'disableBluetooth',
                  'group'                                => 'connectivity',
                  'type'                                 => 'bool',
                  'type_data'                            => '',
                  'unicity'                              => 1,
                  'plugin_flyvemdm_policycategories_id'  => 5,
                  'comment'                              => __('Disable Bluetooth connectivity', 'flyvemdm'),
                  'default_value'                        => '0',
                  'recommended_value'                    => '0',
            ],

            [
                  'name'                                 => __('Disable GPS', 'flyvemdm'),
                  'symbol'                               => 'disableGPS',
                  'group'                                => 'connectivity',
                  'type'                                 => 'bool',
                  'type_data'                            => '',
                  'unicity'                              => 1,
                  'plugin_flyvemdm_policycategories_id'  => 5,
                  'comment'                              => __('Disable GPS', 'flyvemdm'),
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
            PluginFlyvemdmAgent::getTable(),
            PluginFlyvemdmEntityconfig::getTable(),
            PluginFlyvemdmFile::getTable(),
            PluginFlyvemdmInvitationlog::getTable(),
            PluginFlyvemdmFleet::getTable(),
            PluginFlyvemdmFleet_Policy::getTable(),
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
            PluginFlyvemdmAccountvalidation::getTable(),
      );

      foreach ($tables as $table) {
         $DB->query("DROP TABLE IF EXISTS `$table`");
      }
   }

   protected  function deleteProfiles() {
      $config = Config::getConfigurationValues('flyvemdm', array('registered_profiles_id', 'guest_profiles_id'));
      $registeredProfileId = $config['registered_profiles_id'];
      $guestProfileId = $config['guest_profiles_id'];

      $profile = new Profile();
      $profile->getFromDB($registeredProfileId);
      if (!$profile->deleteFromDB()) {
         $this->migration->log("Could not delete registered profile", true);
      } else {
         $profileUser= new Profile_User();
         $profileUser->deleteByCriteria(array('profiles_id' => $registeredProfileId), true);
      }

      $profile->getFromDB($guestProfileId);
      if (!$profile->deleteFromDB()) {
         $this->migration->log("Could not delete guest profile", true);
      } else {
         $profileUser= new Profile_User();
         $profileUser->deleteByCriteria(array('profiles_id' => $guestProfileId), true);
      }
   }

   protected function deleteProfileRights() {
      $rights = array(
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
      );
      foreach ($rights as $right) {
         ProfileRight::deleteProfileRights(array($right));
         unset($_SESSION["glpiactiveprofile"][$right]);
      }
   }

   protected function deleteRelations() {
      $pluginItemtypes = array(
            'PluginFlyvemdmAgent',
            'PluginFlyvemdmEntityconfig',
            'PluginFlyvemdmFile',
            'PluginFlyvemdmFleet',
            'PluginFlyvemdmGeolocation',
            'PluginFlyvemdmInvitation',
            'PluginFlyvemdmPackage',
            'PluginFlyvemdmPolicy',
            'PluginFlyvemdmPolicyCategory',
            'PluginFlyvemdmWellknownpath'
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
      //$displayPreference->deleteByCriteria(array("`num` >= " . PluginFlyvemdmConfig::RESERVED_TYPE_RANGE_MIN . "
      //                                             AND `num` <= " . PluginFlyvemdmConfig::RESERVED_TYPE_RANGE_MAX));
   }

   protected function getHTMLMailingSignature() {
      $config = Config::getConfigurationValues('flyvemdm', [
            'social_media_twit',
            'social_media_gplus',
            'social_media_facebook',
      ]);

      $document = new Document();
      $document->getFromDB($config['social_media_twit']);
      $twitterTag = Document::getImageTag($document->getField('tag'));

      $document = new Document();
      $document->getFromDB($config['social_media_gplus']);
      $gplusTag = Document::getImageTag($document->getField('tag'));

      $document = new Document();
      $document->getFromDB($config['social_media_facebook']);
      $facebookTag = Document::getImageTag($document->getField('tag'));

      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $signature = __("Flyve MDM Team", 'flyvemdm') . "\n";
      $signature.= '<a href="' . self::FLYVE_MDM_PRODUCT_WEBSITE . '">' . self::FLYVE_MDM_PRODUCT_WEBSITE . "</a>\n";
      $signature.= '<a href="' . self::FLYVE_MDM_PRODUCT_FACEBOOK .'">'
                   . '<img src="cid:' . $facebookTag . '" alt="Facebook" title="Facebook" width="30" height="30">'
                   . '</a>'
                   . '&nbsp;<a href="' . self::FLYVE_MDM_PRODUCT_TWITTER . '">'
                   . '<img src="cid:' . $twitterTag . '" alt="Twitter" title="Twitter" width="30" height="30">'
                   . '</a>'
                   . '&nbsp;<a href="' . self::FLYVE_MDM_PRODUCT_GOOGLEPLUS . '">'
                   . '<img src="cid:' . $gplusTag . '" alt="Google+" title="Google+" width="30" height="30">'
                   .'</a>' . "\n";

      // Restore user's locale
      Session::loadLanguage($currentLocale);

      return $signature;
   }

   protected function getTextMailingSignature() {
      // Force locale for localized strings
      $currentLocale = $_SESSION['glpilanguage'];
      Session::loadLanguage('en_GB');

      $signature = __("Flyve MDM Team", 'flyvemdm') . "\n";
      $signature.= self::FLYVE_MDM_PRODUCT_WEBSITE . "\n";
      $signature.= self::FLYVE_MDM_PRODUCT_FACEBOOK . "\n"
                   . self::FLYVE_MDM_PRODUCT_GOOGLEPLUS . "\n"
                   . self::FLYVE_MDM_PRODUCT_TWITTER . "\n";

      // Restore user's locale
      Session::loadLanguage($currentLocale);

      return $signature;
   }

   /**
    * create documents for demo mode social media icons
    */
   protected function createSocialMediaIcons() {
      $config = Config::getConfigurationValues('flyvemdm', [
            'social_media_twit',
            'social_media_gplus',
            'social_media_facebook',
      ]);

      if (!isset($config['social_media_twit'])) {
         copy(PLUGIN_FLYVEMDM_ROOT . '/pics/flyve-twitter.jpg', GLPI_TMP_DIR . '/flyve-twitter.jpg');
         $input = array();
         $document = new Document();
         $input['entities_id']               = '0';
         $input['is_recursive']              = '1';
         $input['name']                      = __('Flyve MDM Twitter icon', 'flyvemdm');
         $input['_filename']                 = array('flyve-twitter.jpg');
         $input['_only_if_upload_succeed']   = true;
         if ($document->add($input)) {
            $config['social_media_twit']     = $document->getID();
         }
      }

      if (!isset($config['social_media_gplus'])) {
         copy(PLUGIN_FLYVEMDM_ROOT . '/pics/flyve-gplus.jpg', GLPI_TMP_DIR . '/flyve-gplus.jpg');
         $input = array();
         $document = new Document();
         $input['entities_id']               = '0';
         $input['is_recursive']              = '1';
         $input['name']                      = __('Flyve MDM Google Plus icon', 'flyvemdm');
         $input['_filename']                 = array('flyve-gplus.jpg');
         $input['_only_if_upload_succeed']   = true;
         if ($document->add($input)) {
            $config['social_media_gplus']    = $document->getID();
         }
      }

      if (!isset($config['social_media_facebook'])) {
         copy(PLUGIN_FLYVEMDM_ROOT . '/pics/flyve-facebook.jpg', GLPI_TMP_DIR . '/flyve-facebook.jpg');
         $input = array();
         $document = new Document();
         $input['entities_id']               = '0';
         $input['is_recursive']              = '1';
         $input['name']                      = __('Flyve MDM Facebook  icon', 'flyvemdm');
         $input['_filename']                 = array('flyve-facebook.jpg');
         $input['_only_if_upload_succeed']   = true;
         if ($document->add($input)) {
            $config['social_media_facebook'] = $document->getID();
         }
      }

      Config::setConfigurationValues('flyvemdm', $config);
   }

   protected function deleteSocialMediaIcons() {
      $config = Config::getConfigurationValues('flyvemdm', [
            'social_media_twit',
            'social_media_gplus',
            'social_media_facebook',
      ]);

      foreach ($config as $documentId) {
         $document = new Document();
         $document->delete(['id'    => $documentId], 1);
      }
   }
}
