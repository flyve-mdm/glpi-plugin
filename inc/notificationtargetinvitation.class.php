<?php
/*
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
 @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmNotificationTargetInvitation extends NotificationTarget {

   const EVENT_GUEST_INVITATION = 'plugin_flyvemdm_invitation';
   const DEEPLINK = 'flyve://register?data=';

   /**
    * Define plugins notification events
    * @return Array Events ids => names
    */
   public function getEvents() {
      return array(
            self::EVENT_GUEST_INVITATION => __('Invitation', 'flyvemdm')
      );
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
      $tagCollection = array(
         'flyvemdm.download_app'    => __('Link to download the FlyveMDM Android application', 'flyvemdm'),
         'flyvemdm.qrcode'          => __('Enrollment QR code', 'flyvemdm'),
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
      global $CFG_GLPI;

      switch ($event->raiseevent) {
         case self::EVENT_GUEST_INVITATION:
            if (isset($event->obj)) {
               $invitation = $event->obj;

               // Get the document containing the QR code
               $document = new Document();
               $document->getFromDB($invitation->getField('documents_id'));

               // build the data of the deeplink
               $personalToken = User::getToken($invitation->getField('users_id'), 'api_token');
               $enrollRequest = [
                     'url'                => rtrim($CFG_GLPI["url_base_api"], '/'),
                     'user_token'         => $personalToken,
                     'invitation_token'   => $invitation->getField('invitation_token')
               ];

               $encodedRequest = PluginFlyvemdmNotificationTargetInvitation::DEEPLINK
                                 . base64_encode(json_encode($enrollRequest, JSON_UNESCAPED_SLASHES));

               // Fill the template
               $event->data['##flyvemdm.qrcode##'] = Document::getImageTag($document->getField('tag'));
               $event->data['##flyvemdm.enroll_url##'] = static::DEEPLINK . $encodedRequest;
               $event->obj->documents = array($document->getID());
               $entityConfig = new PluginFlyvemdmEntityconfig();
               $entityConfig->getFromDB($event->obj->getField('entities_id'));
               $event->data['##flyvemdm.download_app##'] = $entityConfig->getField('download_url');
            }
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
               if ($this->obj->getType() == 'PluginFlyvemdmInvitation') {
                  $this->addToRecipientsList([
                        'users_id' => $this->obj->getField('users_id')
                  ]);
               }
               break;
         }
      }
   }
}
