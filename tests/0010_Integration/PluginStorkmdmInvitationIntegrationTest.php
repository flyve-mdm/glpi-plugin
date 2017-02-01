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
use Flyvemdm\Test\ApiRestTestCase;

class PluginFlyvemdmInvitationIntegrationTest extends ApiRestTestCase {
   /**
    * The current session token
    * @var string
    */
   protected static $sessionToken;

   /**
    * Entity ID of the registered user
    * @var integer
    */
   protected static $entityId;

   /**
    *
    * @var string
    */
   protected static $guestEmail;

   /**
    * email of an administrator
    * @var string
    */
   protected static $adminEmail;


   public static function setupBeforeClass() {
      parent::setupBeforeClass();

      self::$adminEmail = 'glpi@localhost.local';
      self::login('glpi', 'glpi', true);

      $user = new User();
      $user->getFromDBByName('glpi');
      $user->update([
            'id'           => $user->getID(),
            'name'         => self::$adminEmail,
            '_useremails'  => array(self::$adminEmail),
      ]);

      self::$guestEmail = 'guest0001@localhost.local';
   }

   public function SuccessfulInvitationsProvider() {
      return [
            "guest_user" => [
                  "data" => [
                        "_useremails" => "aguest@localhost.local",
                  ],
            ],
            "admin_user_himself" => [
                  "data" => [
                        "_useremails" => 'glpi@localhost.local',
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
    * login as a registered user
    */
   public function testInitGetSessionToken() {
      $this->initSessionByCredentials('glpi@localhost.local', 'glpi');
      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      self::$sessionToken = $this->restResponse['session_token'];
      self::$entityId = $_SESSION['glpiactive_entity'];
   }

   /**
    *
    */
   public function testCreateInvitation() {
      $body = json_encode([
            'input' => [
                  'entities_id'  => self::$entityId,
                  '_useremails'  => self::$guestEmail,
            ]
      ]);

      $this->invitation('post', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      $invitation = new PluginFlyvemdmInvitation();
      $invitation->getFromDB($this->restResponse['id']);
      return $invitation;
   }

   /**
    * @depends testCreateInvitation
    * @param PluginFlyvemdmInvitation $invitation
    */
   public function testUserCreated($invitation) {
      $user = new User();
      $this->assertTrue($user->getFromDB($invitation->getField('users_id')));
      return $user;
   }

   /**
    * @depends testUserCreated
    * @param User $referenceUser
    */
   public function testUserHasEmail($referenceUser) {
      $user = new User();
      $user->getFromDBbyEmail(self::$guestEmail, '');
      $this->assertEquals($referenceUser->getID(), $user->getID());
   }

   /**
    * @depends testCreateInvitation
    * @param PluginFlyvemdmInvitation $firstInvitation
    */
   public function testCreateInvitationForTheSameUser($firstInvitation) {
      $body = json_encode([
            'input' => [
                  'entities_id'  => self::$entityId,
                  '_useremails'  => self::$guestEmail,
            ]
      ]);

      $this->invitation('post', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      $invitation = new PluginFlyvemdmInvitation();
      $invitation->getFromDB($this->restResponse['id']);
      return $invitation;
   }

   /**
    * @depends testCreateInvitation
    * @depends testCreateInvitationForTheSameUser
    * @param PluginFlyvemdmInvitation $invitation
    * @param PluginFlyvemdmInvitation $secondInvntation
    */
   public function testSecondInvitationHasSameUserThanFirstOne($invitation, $secondInvntation) {
      $this->assertEquals($invitation->getField('users_id'), $secondInvntation->getField('users_id'));
   }

   /**
    * @dataProvider BadInvitationsProvider
    * @param unknown $data
    */
   public function testFailingInvitation($data) {
      $body = json_encode([
            'input'  => $data
      ]);

      $this->invitation('post', self::$sessionToken, $body);
      $this->assertGreaterThanOrEqual(400, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(500, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

   }

   /**
    * @dataProvider SuccessfulInvitationsProvider
    */
   public function testSuccessfulInvitation($data) {
      $body = json_encode([
            'input'  => $data
      ]);

      $this->invitation('post', self::$sessionToken, $body);
      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      $this->assertArrayHasKey('id', $this->restResponse);

      // Check the invitation is actually created
      $invitationId = $this->restResponse['id'];
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
