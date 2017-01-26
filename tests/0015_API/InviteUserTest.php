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

class InviteUserTest extends ApiRestRegisteredUserTestCase
{

   public static function setUpBeforeClass() {
      parent::setupBeforeClass();
      // enable api config
      $config = new Config;
      $config->update(
            array('id'                                => 1,
                  'use_mailing'                       => 1,
            ));

   }

   public function SuccessfulInvitationsProvider() {
      return [
            "guest_user" => [
                  "data" => [
                        "_useremails" => "aguest@localhost.local"
                  ],
            ],
            "registered_user_himself" => [
                  "data" => [
                        "_useremails" => self::$login
                  ],
            ]
      ];
   }

   public function BadInvitationsProvider() {
      return [
            "invalid_email" => [
                  "data" => [
                        "_useremails" => "invalid"
                  ],
            ],
      ];
   }

   /**
    * @dataProvider BadInvitationsProvider
    * @param unknown $data
    */
   public function testFailingInvitation($data) {
      $body = [
            'input'  => $data
      ];

      $res = $this->doHttpRequest('POST', 'PluginFlyvemdmInvitation/',
            [
                  'headers'   => [
                        'Session-Token' => self::$sessionToken
                  ],
                  'body'      => json_encode($body, JSON_UNESCAPED_SLASHES),
            ]
            );
      $this->assertEquals(400, $res->getStatusCode(), $this->last_error);
      $response = json_decode($res->getBody(), JSON_OBJECT_AS_ARRAY);
   }

   /**
    * @dataProvider SuccessfulInvitationsProvider
    */
   public function testSuccessfulInvitation($data) {
      $body = [
            'input'  => $data
      ];

      $res = $this->doHttpRequest('POST', 'PluginFlyvemdmInvitation/',
            [
                  'headers'   => [
                        'Session-Token' => self::$sessionToken
                  ],
                  'body'      => json_encode($body, JSON_UNESCAPED_SLASHES),
            ]
      );
      $this->assertEquals(201, $res->getStatusCode(), $this->last_error);
      $response = json_decode($res->getBody(), JSON_OBJECT_AS_ARRAY);
      $this->assertArrayHasKey('id', $response);

      // Check the invitation is actually created
      $invitationId = $response['id'];
      $invitation = new PluginFlyvemdmInvitation();
      $invitation->getFromDB($invitationId);
      $this->assertFalse($invitation->isNewItem());

      // Check the invitation is pending
      $this->assertEquals('pending', $invitation->getField('status'));

      // Check the invitation has an expriation date
      // TODO

      // Check the notifications email is queued
      $queuedMail = new QueuedMail();
      $queuedMail->getFromDBByQuery("WHERE `itemtype`='PluginFlyvemdmInvitation' AND `items_id`='$invitationId'");
      $this->assertFalse($queuedMail->isNewItem());

      // Check a QR code document has been created
      $document = new Document();
      $document->getFromDB($invitation->getField('documents_id'));
      $this->assertFalse($document->isNewItem());
   }

}