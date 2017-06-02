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
use Flyvemdm\Test\ApiRestTestCase;

class PluginFlyvemdmFleetIntegrationTest extends ApiRestTestCase {

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
    * enrolled agent
    * @var PluginFlyvemdmAgent
    */
   protected static $enrolledAgent;

   public static function setUpBeforeClass() {
      parent::setUpBeforeClass();

      self::login('glpi', 'glpi', true);

      $invitation = new PluginFlyvemdmInvitation();
      $invitationId = $invitation->add([
            'entities_id'  => self::$entityId,
            '_useremails'  => self::$guestEmail,
      ]);

      self::$enrolledAgent = new PluginFlyvemdmAgent();
      self::$enrolledAgent ->add([
            'entities_id'        => 0,
            '_email'             => self::$guestEmail,
            '_invitation_token'  => $invitation->getField('invitation_token'),
            '_serial'            => 'AZERTY',
            'csr'                => '',
            'firstname'          => 'John',
            'lastname'           => 'user',
            'version'            => '1.0.0',
      ]);
   }

   /**
    * login as a registered user
    */
   public function testInitGetSessionToken() {
      $this->initSessionByCredentials('glpi', 'glpi');
      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      self::$sessionToken = $this->restResponse['session_token'];
      self::$entityId = $_SESSION['glpiactive_entity'];
   }


   /**
    * @depends testInitGetSessionToken
    */
   public function testDeleteDefaultFleet() {
      $fleet = new PluginFlyvemdmFleet();
      $entityId = self::$entityId;
      $this->assertTrue($fleet->getFromDBByQuery("WHERE `is_default`='1' AND `entities_id`='$entityId' LIMIT 1"));
      $body = json_encode([
            'input' => [
                  'id'     => $fleet->getID(),
            ]
      ]);
      $this->fleet('delete', self::$sessionToken, $body);
      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

   }

   public function testAddFleet() {
      // The API automatically sets entites_id
      $body = json_encode([
            'input'  => [
                  'entities_id'     => self::$entityId,
                  'name'            => 'a fleet'
            ]]);
      $this->fleet('post', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      $fleet = new PluginFlyvemdmFleet();
      $fleet->getFromDB($this->restResponse['id']);
      return $fleet;
   }

   /**
    * @depends testAddFleet
    */
   public function testAddAgentToFleet(PluginFlyvemdmFleet $fleet) {
      $body = json_encode([
            'input'  => [
               'id'                          => self::$enrolledAgent->getID(),
               'plugin_flyvemdm_fleets_id'   => $fleet->getID()
            ]]);
      $this->agent('update', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      return $fleet;
   }

   /**
    * @depends testAddFleet
    * @depends testAddAgentToFleet
    */
   public function testApplyPolicyToFleet(PluginFlyvemdmFleet $fleet) {
      $policyData = new PluginFlyvemdmPolicy();
      $policyData->getFromDBBySymbol('disableGPS');

      $body = json_encode([
            'input'  => [
                  'plugin_flyvemdm_policies_id' => $policyData->getID(),
                  'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
                  'value'                       => '0',
            ]]);

      $this->fleet_policy('post', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
   }

   /**
    * @depends testAddAgentToFleet
    * @depends testApplyPolicyToFleet
    */
   public function testPurgeFleet(PluginFlyvemdmFleet $fleet) {
      $fleetId = $fleet->getID();
      $body = json_encode([
            'input'  => [
                  'id' => $fleetId,
            ]]);
      $this->fleet('delete', self::$sessionToken, $body);

      $this->assertGreaterThanOrEqual(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertLessThan(300, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Check there is no agent linked to the deleted fleet
      $agent = new PluginFlyvemdmAgent();
      $rows = $agent->find("`plugin_flyvemdm_fleets_id`='$fleetId'");
      $this->assertEquals(0, count($rows));

      return $fleet;
   }

   /**
    * @depends testPurgeFleet
    */
   public function testPolicyUnlinkedAfterPurge(PluginFlyvemdmFleet $fleet) {
      $task = new PluginFlyvemdmTask();
      $fleetId = $fleet->getID();
      $rows = $task->find("`plugin_flyvemdm_fleets_id`='$fleetId'");
      $this->assertEquals(0, count($rows));
   }

}
