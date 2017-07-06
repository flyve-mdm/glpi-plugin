<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2016 by the FusionInventory Development Team.
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
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

class PluginFlyvemdmNotificationTargetInvitationTest extends SuperAdminTestCase {

   /**
    * test
    */
   function testAdditionalDataForTemplate() {
      // create document
      $document                                    = new Document();
      $documentInput = [];
      $documentInput['entities_id']                = $_SESSION['glpiactive_entity'];
      $documentInput['is_recursive']               = '0';
      $documentInput['name']                       = addslashes(__('Enrollment QR code', 'flyvemdm'));
      $documentInput['_only_if_upload_succeed']    = false;
      $documentId = $document->add($documentInput);
      $this->assertFalse($document->isNewItem());

      // create notificatoinTarget
      $event = new NotificationTarget();
      $event->raiseevent                  = PluginFlyvemdmNotificationTargetInvitation::EVENT_GUEST_INVITATION;
      $event->obj                         = new PluginFlyvemdmInvitation();
      $event->obj->fields['documents_id'] = $documentId;

      //create entityConfig
      $entityConfig = new PluginFlyvemdmEntityConfig();
      $entityConfig->getFromDBByCrit(['entities_id' => $_SESSION['glpiactive_entity']]);
      $entityConfigInput = [];
      $entityConfigInput['id']               = $entityConfig->getID();
      $entityConfigInput['support_name']     = $this->getUniqueString();
      $entityConfigInput['support_phone']    = $this->getUniqueString();
      $entityConfigInput['support_website']  = $this->getUniqueString();
      $entityConfigInput['support_email']    = $this->getUniqueString();
      $entityConfigInput['support_address']  = $this->getUniqueString();
      $entityConfigInput['download_url']     = $this->getUniqueString();
      $entityConfig->update($entityConfigInput);

      // add name to the current user
      $user = new User();
      $userInput = [];
      $userInput['id']           = $_SESSION['glpiID'];
      $userInput['firstname']    = $this->getUniqueString();
      $userInput['realname']     = $this->getUniqueString();
      $userInput['_useremails']  = ['john.doe@localhost.local'];
      $userInput['is_default']   = 1;
      $user->update($userInput);

      $invitationNotification = new PluginFlyvemdmNotificationTargetInvitation();
      $invitationNotification->getAdditionalDatasForTemplate($event);
      $this->assertEquals($event->data['##flyvemdm.qrcode##'], Document::getImageTag($document->getField('tag')));
      $this->assertEquals($event->data['##flyvemdm.download_app##'], $entityConfigInput['download_url']);
      $this->assertNotEmpty($event->data['##flyvemdm.enroll_url##']);
      $this->assertEquals($event->data['##flyvemdm.download_app##'], $entityConfigInput['download_url']);
      $this->assertEquals($event->data['##user.firstname##'], $userInput['firstname']);
      $this->assertEquals($event->data['##user.realname##'], $userInput['realname']);
      $this->assertEquals($event->data['##user.email##'], implode('', $userInput['_useremails']));
      $this->assertEquals($event->data['##support.name##'], $entityConfigInput['support_name']);
      $this->assertEquals($event->data['##support.phone##'], $entityConfigInput['support_phone']);
      $this->assertEquals($event->data['##support.website##'], $entityConfigInput['support_website']);
      $this->assertEquals($event->data['##support.email##'], $entityConfigInput['support_email']);
      $this->assertEquals($event->data['##support.address##'], $entityConfigInput['support_address']);
   }
}
