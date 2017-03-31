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

class HandleIncomingMqttMessageTest extends RegisteredUserTestCase
{
   protected static $entity;

   protected static $invitation;

   protected static $guestEmail;

   protected static $guestUser;

   protected static $agent;

   protected static $defaultFleet;

   protected static $fleet;

   public static function setUpBeforeClass() {
      parent::setUpBeforeClass();

      self::login('glpi', 'glpi', true);
      self::$entity = new Entity();
      self::$entity->add([
            'name'   => "to be deleted",
      ]);
      $entityId = self::$entity->getID();

      self::$guestEmail = 'a.user@localhost.local';

      // create invitation
      self::$invitation = new PluginFlyvemdmInvitation();
      self::$invitation->add([
            'entities_id'  => $entityId,
            '_useremails'  => self::$guestEmail,
      ]);

      self::$guestUser = new User();
      self::$guestUser->getFromDB(self::$invitation->getField('users_id'));

      Session::destroy();
      self::setupGLPIFramework();

      // Login as guest user
      $_REQUEST['user_token'] = User::getToken(self::$invitation->getField('users_id'), 'api_token');
      self::login('', '', false);
      unset($_REQUEST['user_token']);

      // enroll an agent
      self::$agent = new PluginFlyvemdmAgent();
      self::$agent->add([
            'entities_id'        => $entityId,
            '_email'             => self::$guestEmail,
            '_invitation_token'  => self::$invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'Doe',
            'version'            => '1.0.0',
      ]);

      Session::destroy();
      self::setupGLPIFramework();

      // login as super admin
      self::login('glpi', 'glpi', true);

      //find default fleet
      self::$defaultFleet = new PluginFlyvemdmFleet();
      self::$defaultFleet->getFromDBByQuery("WHERE `entities_id` = '$entityId' AND `is_default` <> '0'");

      // create a fleet
      self::$fleet = new PluginFlyvemdmFleet();
      self::$fleet->add([
            'name'         => 'a fleet',
            'entities_id'  => $entityId,
      ]);

      //move the agent to the fleet
      self::$agent->update([
            'id'                          => self::$agent->getID(),
            'plugin_flyvemdm_fleets_id'   => self::$fleet->getID(),
      ]);

   }

   /**
    *
    * Check the agent is marked online when the backend is notified about
    *
    * @return void
    */
   public function testDeviceGoesOnline() {
      $this->DeviceOnlineStatus(self::$agent, 'yes', 1);
   }

   /**
    * Check the device is marked offline when the backend is notified about
    *
    * @depends testDeviceGoesOnline
    *
    * @return void
    */
   public function testDeviceGoesOffline() {
      $this->DeviceOnlineStatus(self::$agent, 'no', 0);
   }

   protected function DeviceOnlineStatus($agent, $mqttStatus, $expectedStatus) {
      $topic = $agent->getTopic() . '/Status/Online';

      // prepare mock
      $message = ['online'   => $mqttStatus];
      $messageEncoded = json_encode($message, JSON_OBJECT_AS_ARRAY);
      $mqttStub = $this->getMockBuilder(sskaje\mqtt\MQTT::class)
                       ->disableOriginalConstructor()
                       ->getMock();
      $publishStub = $this->getMockBuilder(sskaje\mqtt\Message\PUBLISH::class)
                          ->disableOriginalConstructor()
                          ->setMethods(['getTopic', 'getMessage'])
                          ->getMock();
      $publishStub->method('getTopic')
                  ->willReturn($topic);
      $publishStub->method('getMessage')
                  ->willReturn($messageEncoded);

      $mqttHandler = PluginFlyvemdmMqtthandler::getInstance();
      $mqttHandler->publish($mqttStub, $publishStub);

      // refresh the agent
      $agent->getFromDB($agent->getID());
      $this->assertEquals($expectedStatus, $agent->getField('is_online'));
   }

}