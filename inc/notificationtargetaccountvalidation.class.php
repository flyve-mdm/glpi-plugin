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
 * @since 0.1.33
 */
class PluginStorkmdmNotificationTargetAccountvalidation extends NotificationTarget {

   const EVENT_SELF_REGISTRATION       = 'plugin_flyvemdm_self_registration';
   const EVENT_TRIAL_EXPIRATION_REMIND = 'plugin_flyvemdm_trial_will_expire';

   /**
    *
    * @param number $nb
    * @return translated
    */
   static function getTypeName($nb=0) {
      return _n('Account validation', 'Account validations', $nb);
   }

   /**
    * Define plugins notification events
    * @return Array Events ids => names
    */
   public function getEvents() {
      return array(
            self::EVENT_SELF_REGISTRATION => __('User registration', 'storkmdm')
      );
   }

   /**
    * @param NotificationTarget $target
    */
   public static function addEvents($target) {
         Plugin::loadLang('storkmdm');
         $target->events[self::EVENT_SELF_REGISTRATION] = __('User registration', 'storkmdm');
   }

   /**
    * Get available tags for plugins notifications
    */
   public function getTags() {
      $tagCollection = array(
            'storkmdm.registration_url'      => __('Account validation URL', 'storkmdm'),
            'storkmdm.webapp_url'            => __('URL to the web application', 'storkmdm'),
            'storkmdm.activation_delay'      => __('Account activation delay', 'storkmdm'),
            'storkmdm.trial_duration'        => __('Duration of a trial account', 'storkmdm'),
      );

      foreach ($tagCollection as $tag => $label) {
         $this->addTagToList(array('tag'    => $tag,
               'label'  => $label,
               'value'  => true,
               'events' => NotificationTarget::TAG_FOR_ALL_EVENTS));
      }
   }

   /**
    * @param NotificationTarget $event
    * @param array $options
    */
   public static function getAdditionalDatasForTemplate(NotificationTarget $event) {
      switch ($event->raiseevent) {
         case self::EVENT_SELF_REGISTRATION:
            $config = Config::getConfigurationValues('storkmdm', array('webapp_url'));
            if (isset($event->obj)) {
               $accountValidation = $event->obj;
               $accountValidationId = $accountValidation->getID();
               $validationToken = $accountValidation->getField('validation_pass');
               $validationUrl = $config['webapp_url'] . "?id=$accountValidationId&validate=$validationToken";

               $activationDelay = new DateInterval($accountValidation->getActivationDelay());
               $activationDelay = $activationDelay->format('%d');
               $activationDelay.= " " . _n('day', 'days', $activationDelay, 'storkmdm');

               $trialDuration = new DateInterval($accountValidation->getTrialDuration());
               $trialDuration = $trialDuration->format('%d');
               $trialDuration.= " " . _n('day', 'days', $trialDuration, 'storkmdm');

               // Fill the template
               $event->datas['##storkmdm.registration_url##'] = $validationUrl;
               $event->datas['##storkmdm.webapp_url##'] = $config['webapp_url'];
               $event->datas['##storkmdm.activation_delay##'] = $activationDelay;
               $event->datas['##storkmdm.activation_delay##'] = $trialDuration;
            }
            break;

         case self::EVENT_TRIAL_EXPIRATION_REMIND:
            break;
      }
   }

   /**
    * Return all the targets for this notification
    * Values returned by this method are the ones for the alerts
    * Can be updated by implementing the getAdditionnalTargets() method
    * Can be overwitten (like dbconnection)
    * @param $entity the entity on which the event is raised
    */
   public function getNotificationTargets($entity) {
      $this->addTarget(Notification::USER, __('Registered user', 'storkmdm'));
   }

   /**
    *
    * @param  array $data
    * @param  array $options
    */
   public function getSpecificTargets($data, $options) {
      if ($data['type'] == Notification::USER_TYPE) {
         switch ($data['items_id']) {
            case Notification::USER:
               if ($this->obj->getType() == 'PluginStorkmdmAccountvalidation') {
                  $this->addToAddressesList([
                        'users_id' => $this->obj->getField('users_id')
                  ]);
               }
               break;
         }
      }
   }
}
