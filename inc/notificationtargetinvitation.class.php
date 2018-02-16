<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2018 Teclib'
 * Copyright © 2010-2018 by the FusionInventory Development Team.
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
 * @author    Thierry Bugier
 * @copyright Copyright © 2018 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmNotificationTargetInvitation extends NotificationTarget {

   const EVENT_GUEST_INVITATION = 'plugin_flyvemdm_invitation';
   const DEEPLINK = '?data=';

   /**
    * Define plugins notification events
    * @return array Events ids => names
    */
   public function getEvents() {
      return [
            self::EVENT_GUEST_INVITATION => __('Invitation', 'flyvemdm')
      ];
   }

   /**
    * @param NotificationTarget $target
    */
   public static function addEvents($target) {
         Plugin::loadLang('flyvemdm');
         $target->events[self::EVENT_GUEST_INVITATION] = __('Invitation', 'flyvemdm');
   }

   /**
    * Get available tags for plugins notifications
    */
   public function getTags() {
      $tagCollection = [
         'flyvemdm.download_app'    => __('Link to download the FlyveMDM Android application', 'flyvemdm'),
         'flyvemdm.qrcode'          => __('Enrollment QR code', 'flyvemdm'),
         'flyvemdm.enroll_url'      => __('Enrollment URL', 'flyvemdm'),
         'user.firstname'           => __('First name of the Flyve MDM fleets manager', 'flyvemdm'),
         'user.realname'            => __('Last name of the Flyve MDM fleets manager', 'flyvemdm'),
         'support.name'             => __('Name of the helpdesk', 'flyvemdm'),
         'support.phone'            => __('Phone number of the helpdesk', 'flyvemdm'),
         'support.website'          => __('Website if the helpdesk', 'flyvemdm'),
         'support.email'            => __('Email address of the helpdesk', 'flyvemdm'),
         'support.address'          => __('Address of the helpdesk', 'flyvemdm'),
      ];

      foreach ($tagCollection as $tag => $label) {
         $this->addTagToList(['tag'    => $tag,
               'label'  => $label,
               'value'  => true,
               'events' => NotificationTarget::TAG_FOR_ALL_EVENTS
         ]);
      }
   }

   /**
    * @param NotificationTarget $event
    */
   public static function getAdditionalDatasForTemplate(NotificationTarget $event) {
      global $CFG_GLPI;

      switch ($event->raiseevent) {
         case self::EVENT_GUEST_INVITATION:
            if (isset($event->obj)) {
               $invitation = $event->obj;

               // Get the document containing the QR code
               $document = new Document();
               $document->getFromDB($invitation->getField('documents_id'));

               // Get the general config of Flyve MDM
               $config = Config::getConfigurationValues('flyvemdm', ['invitation_deeplink']);

               // Get the entitiy configuration data
               $entityConfig = new PluginFlyvemdmEntityConfig();
               $entityConfig->getFromDBByCrit(['entities_id' => $event->obj->getField('entities_id')]);

               // Build the data of the deeplink
               $personalToken = User::getToken($invitation->getField('users_id'), 'api_token');
               $enrollmentData = [
                     'url'                => rtrim($CFG_GLPI['url_base_api'], '/'),
                     'user_token'         => $personalToken,
                     'invitation_token'   => $invitation->getField('invitation_token'),
                     'support_name'       => $entityConfig->getField('support_name'),
                     'support_phone'      => $entityConfig->getField('support_phone'),
                     'support_website'    => $entityConfig->getField('support_website'),
                     'support_email'      => $entityConfig->getField('support_email'),
                     //'support_address'    => $entityConfig->getField('support_address'),
               ];

               $encodedRequest = $config['invitation_deeplink'] . PluginFlyvemdmNotificationTargetInvitation::DEEPLINK
                                 . base64_encode(addcslashes(implode(';', $enrollmentData), '\;'));

               // Fill the template
               $event->data['##flyvemdm.qrcode##'] = Document::getImageTag($document->getField('tag'));
               $event->data['##flyvemdm.enroll_url##'] = $encodedRequest;

               // fill the application download URL tag
               $event->obj->documents = [$document->getID()];
               $event->data['##flyvemdm.download_app##'] = $entityConfig->getField('download_url');

               // fill the helpdesk information tags
               $event->data['##support.name##']    = $entityConfig->getField('support_name');
               $event->data['##support.phone##']   = $entityConfig->getField('support_phone');
               $event->data['##support.website##'] = $entityConfig->getField('support_website');
               $event->data['##support.address##'] = $entityConfig->getField('support_address');
               $event->data['##support.email##'] = $entityConfig->getField('support_email');

               // fill tags for the Fmyve MDM fleets manager
               if (isset($_SESSION['glpiID'])) {
                  $user = new User();
                  if ($user->getFromDB($_SESSION['glpiID'])) {
                     $event->data['##user.realname##'] = $user->getField('realname');
                     $event->data['##user.firstname##'] = $user->getField('firstname');
                     $event->data['##user.email##'] = $user->getDefaultEmail();
                  }
               }
            }
            break;
      }
   }

   /**
    * Return all the targets for this notification
    * Values returned by this method are the ones for the alerts
    * Can be updated by implementing the getAdditionnalTargets() method
    * Can be overwitten (like dbconnection)
    * @param integer $entity the entity on which the event is raised
    */
   public function addNotificationTargets($entity) {
      $this->addTarget(Notification::USER, __('Guest user', 'flyvemdm'));
   }

   /**
    *
    * @param  array $data
    * @param  array $options
    */
   public function addSpecificTargets($data, $options) {
      if ($data['type'] == Notification::USER_TYPE) {
         switch ($data['items_id']) {
            case Notification::USER :
               if ($this->obj->getType() == PluginFlyvemdmInvitation::class) {
                  $this->addToRecipientsList([
                        'users_id' => $this->obj->getField('users_id')
                  ]);
               }
               break;
         }
      }
   }
}
