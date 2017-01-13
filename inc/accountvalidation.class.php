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
 * @since 1.0.2
 */
class PluginStorkmdmAccountvalidation extends CommonDBTM
{

   /**
    * Delay to activate an account; see DateInterval format
    * @var string
    */
   const ACTIVATION_DELAY = '1';

   /**
    * Trial duration; see DateInterval format
    * @var string
    */
   const TRIAL_LIFETIME       = '90';

   /**
    * delay after beginning of a trial for first remind; in days
    * @var string
    */
   const TRIAL_REMIND_1       = '75';

   /**
    * delay after beginning of a trial for second remind; in days
    * @var string
    */
   const TRIAL_REMIND_2       = '85';

   /**
    * delay after end of a trial for last remind; in days
    * @var string
    */
   const TRIAL_POST_REMIND    = '5';

   /**
    * Localized name of the type
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb=0) {
      return _n('Account validation', 'Account validations', $nb, "storkmdm");
   }

   /**
    *
    * @return boolean
    */
   public static function canCreate() {
      return false;
   }

   /**
    *
    * @return boolean
    */
   public static function canUpdate() {
      $config = Config::getConfigurationValues('storkmdm', array('service_profiles_id'));
      $serviceProfileId = $config['service_profiles_id'];
      if ($serviceProfileId === null) {
         return false;
      }

      if ($_SESSION['glpiactiveprofile']['id'] != $serviceProfileId) {
         return false;
      }

      return true;
   }

   public function getActivationDelay() {
      return 'P' . static::ACTIVATION_DELAY . 'D';
   }

   public function getTrialDuration() {
      return 'P' . static::TRIAL_LIFETIME . 'D';
   }

   /**
    *
    * {@inheritDoc}
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      global $DB;

      $ok = false;
      $accountValidation = new static();
      $table = static::getTable();

      do {
         $validationPass = bin2hex(openssl_random_pseudo_bytes(32));
         $query  = "SELECT COUNT(*)
                    FROM `$table`
                    WHERE `validation_pass` = '$validationPass'";
         $result = $DB->query($query);

         if ($DB->result($result, 0, 0) == 0) {
            $input['validation_pass'] = $validationPass;
            $ok = true;
         }
      } while (!$ok);

      $input['validation_pass'] = $validationPass;

      return $input;
   }

   public function validateForRegisteredUser($input) {
      if (!isset($input['_validate']) || empty($input['_validate'])) {
         Session::addMessageAfterRedirect(__('Validation token missing', 'storkmdm'));
         return false;
      }

      if ($input['_validate'] != $this->fields['validation_pass']) {
         Session::addMessageAfterRedirect(__('Validation token is invalid', 'storkmdm'));
         return false;
      }

      // Check the token is still valid
      $currentDateTime = new DateTime($_SESSION["glpi_currenttime"]);
      $expirationDateTime = new DateTime($this->getField('date_creation'));
      $expirationDateTime->add(new DateInterval($this->getActivationDelay()));
      if ($expirationDateTime < $currentDateTime) {
         Session::addMessageAfterRedirect(__('Validation token expired', 'storkmdm'));
         return false;
      }

      // The validation pass is valid
      $config = Config::getConfigurationValues('storkmdm', array(
            'inactive_registered_profiles_id',
      ));

      $userId     = $this->fields['users_id'];
      $profileId  = $config['inactive_registered_profiles_id'];
      $entityId   = $this->fields['assigned_entities_id'];
      $profile_user = new Profile_User();
      $profile_user->getFromDBByQuery("WHERE `users_id` = '$userId'
                                       AND `profiles_id` = '$profileId'
                                       AND `entities_id` = '$entityId'");
      if ($profile_user->isNewItem()) {
         Session::addMessageAfterRedirect(__('Failed to find your account', 'storkmdm'));
         return false;
      }

      $profile_user2 = new Profile_User();
      if ($profile_user2->add(array(
            'users_id'     => $userId,
            'profiles_id'  => $this->fields['profiles_id'],
            'entities_id'  => $entityId,
            'is_recursive' => $profile_user->getField('is_recursive'),
      ))) {
         // If add succeeded, then delete inactive profile
         $profile_user->delete(array(
               'id'        => $profile_user->getID(),
         ));
      }

      $config = Config::getConfigurationValues('storkmdm', array('inactive_registered_profiles_id'));

      $input['validation_pass']  = '';
      $endTrialDateTime = $currentDateTime->add(new DateInterval('P' . self::TRIAL_LIFETIME . 'D'));
      $input['date_end_trial'] = $endTrialDateTime->format('Y-m-d H:i:s');

      return $input;
   }

   /**
    *
    * {@inheritDoc}
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {

      // Check the user is using the service profile
      $config = Config::getConfigurationValues('storkmdm', array('service_profiles_id'));
      $serviceProfileId = $config['service_profiles_id'];
      if ($serviceProfileId === null) {
         return false;
      }

      if ($_SESSION['glpiactiveprofile']['id'] == $serviceProfileId) {
         return $this->validateForRegisteredUser($input);
      }

      return $input;
   }

   /**
    *
    * {@inheritDoc}
    * @see CommonDBTM::post_updateItem()
    */
   public function post_updateItem($history = 1) {
      if (array_search('validation_pass', $this->updates) !== false) {
         // Trial begins
         NotificationEvent::raiseEvent(
               PluginStorkmdmNotificationTargetAccountvalidation::EVENT_TRIAL_BEGIN,
               $this,
               array('entities_id' => $this->getField('assigned_entities_id'))
         );
      }
   }

   /**
    * Remove accounts not activated and with expired validation token
    *
    * @param unknown $task
    * @return integer quantity of accounts removed
    */
   public static function cronCleanupAccountActivation($task) {
      $task->log("Delete expired account activations");

      // Compute the oldest items to keep
      // substract the interval twice to delay deletion of expired items
      $oldestAllowedItems = new DateTime($_SESSION["glpi_currenttime"]);
      $dateInterval = new DateInterval('P' . static::ACTIVATION_DELAY . 'D');
      $oldestAllowedItems->sub($dateInterval);
      $oldestAllowedItems->sub($dateInterval);
      $oldestAllowedItems = $oldestAllowedItems->format('Y-m-d H:i:s');

      $config = Config::getConfigurationValues('storkmdm', array('inactive_registered_profiles_id'));
      $profileId = $config['inactive_registered_profiles_id'];
      $accountValidation = new static();
      $rows = $accountValidation->find("`validation_pass` <> ''
                                        AND (`date_creation` < '$oldestAllowedItems' OR `date_creation` IS NULL)",
                                       '',
                                       '200');
      $volume = 0;
      foreach($rows as $id => $row) {
         $accountValidation->removeProfile($row['users_id'], $profileId, $row['assigned_entities_id']);
         if ($accountValidation->delete(array('id' => $id))) {
            $volume++;
         }
      }

      $task->setVolume($volume);

      return 1;
   }

   /**
    * Disable accounts with trial over
    *
    * @param unknown $task
    * @return integer quantity of accounts deactivated
    */
   public static function cronDisableExpiredTrial($task) {
      $task->log("Disable expired trial accounts");

      // Compute the oldest items to keep
      $currentDateTime = new DateTime($_SESSION["glpi_currenttime"]);
      $currentDateTime = $currentDateTime->format('Y-m-d H:i:s');
      $accountValidation = new static();
      $rows = $accountValidation->find("`validation_pass` = ''
                                        AND (`date_end_trial` < '$currentDateTime')
                                        AND `is_trial_ended` = '0'",
                                       '',
                                       '200');

      $volume = 0;
      foreach($rows as $id => $row) {
         $accountValidation->disableTrialAccount($row['users_id'], $row['profiles_id'], $row['assigned_entities_id']);
         if ($accountValidation->update(array('id' => $id, 'is_trial_ended' => '1'))) {
            $volume++;
         }
      }

      $task->setVolume($volume);

      return 1;
   }

   public static function cronRemindTrialExpiration($task) {
      $task->log("Remind the trial incoming expiration");

      // Compute the dates for all reminders
      $currentDateTime = new DateTime($_SESSION["glpi_currenttime"]);
      $currentDateTime = $currentDateTime->format('Y-m-d H:i:s');

      $remindDateTime_1 = new DateTime($_SESSION["glpi_currenttime"]);
      $remindDateTime_1->add(new DateInterval('P' . (self::TRIAL_LIFETIME - self::TRIAL_REMIND_1) . 'D'));
      $remindDateTime_1 = $remindDateTime_1->format('Y-m-d H:i:s');

      $remindDateTime_2 = new DateTime($_SESSION["glpi_currenttime"]);
      $remindDateTime_2->add(new DateInterval('P' . (self::TRIAL_LIFETIME - self::TRIAL_REMIND_2) . 'D'));
      $remindDateTime_2 = $remindDateTime_2->format('Y-m-d H:i:s');

      $remindDateTime_3 = new DateTime($_SESSION["glpi_currenttime"]);
      $remindDateTime_3->sub(new DateInterval('P' . self::TRIAL_POST_REMIND . 'D'));
      $remindDateTime_3 = $remindDateTime_3->format('Y-m-d H:i:s');

      // Find activated accoutns (no validation_pass)
      $accountValidation = new static();
      $volume = 0;

      // Process first reminder
      $rows = $accountValidation->find("`validation_pass` = ''
                                        AND (`date_end_trial` < '$remindDateTime_1')
                                        AND `is_reminder_1_sent` = '0'",
                                       '',
                                       '100');
      foreach($rows as $id => $row) {
         $accountValidation = new static();
         $accountValidation->getFromDB($id);
         NotificationEvent::raiseEvent(
               PluginStorkmdmNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND_1,
               $accountValidation,
               array('entities_id' => $accountValidation->getField('assigned_entities_id'))
         );
         if ($accountValidation->update(array('id' => $id, 'is_reminder_1_sent' => '1'))) {
            $volume++;
         }
      }

      // Process second reminder
      $rows = $accountValidation->find("`validation_pass` = ''
                                        AND (`date_end_trial` < '$remindDateTime_2')
                                        AND `is_reminder_2_sent` = '0'",
                                        '',
                                        '100');
      foreach($rows as $id => $row) {
         $accountValidation = new static();
         $accountValidation->getFromDB($id);
         NotificationEvent::raiseEvent(
               PluginStorkmdmNotificationTargetAccountvalidation::EVENT_TRIAL_EXPIRATION_REMIND_2,
               $accountValidation,
               array('entities_id' => $accountValidation->getField('assigned_entities_id'))
         );
         if ($accountValidation->update(array('id' => $id, 'is_reminder_2_sent' => '1'))) {
            $volume++;
         }
      }

      // Process post expiration reminder
      $rows = $accountValidation->find("`validation_pass` = ''
                                        AND (`date_end_trial` < '$remindDateTime_3')
                                        AND `is_post_reminder_sent` = '0'
                                        AND `is_trial_ended` = '1'",
                                        '',
                                        '100');
      foreach($rows as $id => $row) {
         $accountValidation = new static();
         $accountValidation->getFromDB($id);
         NotificationEvent::raiseEvent(
               PluginStorkmdmNotificationTargetAccountvalidation::EVENT_POST_TRIAL_REMIND,
               $accountValidation,
               array('entities_id' => $accountValidation->getField('assigned_entities_id'))
         );
         if ($accountValidation->update(array('id' => $id, 'is_post_reminder_sent' => '1'))) {
            $volume++;
         }
      }

      $task->setVolume($volume);

      return 1;
   }

   /**
    * Is the demo mode enabled ?
    *
    * @return boolean true if demo mode is enabled
    */
   public function isDemoEnabled() {
      $config = Config::getConfigurationValues('storkmdm', array(
            'demo_mode',
            'webapp_url',
            'inactive_registered_profiles_id',
      ));

      if (!isset($config['demo_mode'])
            || !isset($config['webapp_url'])
            || !isset($config['inactive_registered_profiles_id'])) {
         return false;
      }

      if ($config['demo_mode'] == '0' || empty($config['webapp_url'])) {
         return false;
      }

      return true;
   }

   /**
    * Remove habilitation to an entity with a profile from a user
    *
    * @param integer $userId
    * @param integer $profileId
    * @param integer $entityId
    */
   protected function removeProfile($userId, $profileId, $entityId) {
      $profileUser = new Profile_User();
      $rows = $profileUser->find("`users_id` = '$userId'");
      if (count($rows) > 1) {
         $success = $profileUser->deleteByCriteria(array(
               'users_id'     => $userId,
               'entities_id'  => $entityId,
               'profiles_id'  => $profileId,
         ), true);
         $entity = new Entity();
         $entity->delete(array('id' => $entityId),  true);
      } else {
         $user = new User();
         $success = $user->delete(array('id' => $userId), true);
      }

      if ($success) {
         $entity = new Entity();
         $entity->delete(array('id' => $entityId),  true);
      }
   }

   /**
    * Diable a trial user account
    *
    * @param unknown $userId
    * @param unknown $profileId
    * @param unknown $entityId
    */
   protected function disableTrialAccount($userId, $profileId, $entityId) {
      $config = Config::getConfigurationValues('storkmdm', array('inactive_registered_profiles_id'));
      $inactiveProfileId = $config['inactive_registered_profiles_id'];
      $profile_user = new Profile_User();
      $profile_users = $profile_user->find("`entities_id` = '$entityId'
                                     AND `profiles_id` = '$profileId'");
      foreach ($profile_users as $profile_user) {
         $userId = $profile_user['users_id'];
         $profile_user2 = new Profile_User();
         if ($profile_user2->add(array(
               'users_id'     => $userId,
               'profiles_id'  => $inactiveProfileId,
               'entities_id'  => $entityId,
               'is_recursive' => $profile_user['is_recursive'],
         ))) {
            // If add succeeded, then delete active profile
            $oldProfile_user = new Profile_User();
            $oldProfile_user->delete(array(
                  'id'        => $profile_user['id'],
            ));
         }
      }
   }
}