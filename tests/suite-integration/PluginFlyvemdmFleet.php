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

namespace tests\units;

use Flyvemdm\Tests\Src\TestingCommonTools;
use Glpi\Test\CommonTestCase;

class PluginFlyvemdmFleet extends CommonTestCase {

   /**
    * @var string
    */
   private $minAndroidVersion = '2.0.0';

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->setupGLPIFramework();
      $this->login('glpi', 'glpi');
   }

   public function afterTestMethod($method) {
      parent::afterTestMethod($method);
      \Session::destroy();
   }

   /**
    * @tags testDeleteDefaultFleet
    */
   public function testDeleteDefaultFleet() {
      $fleet = $this->newTestedInstance();
      $entityId = 1;
      $fleet->add([
         'name'        => 'fleet for delete',
         'entities_id' => $entityId,
         'is_default'  => 1,
      ]);
      $this->boolean($fleet->getFromDBByQuery("WHERE `is_default`='1' AND `entities_id`='$entityId'"))
         ->isTrue();

      $result = $fleet->delete(['id' => $fleet->getID()]);
      $this->boolean($result)->isTrue();
   }

   /**
    * @tags testAddAgentToFleet
    */
   public function testAddAgentToFleet() {
      // Create an invitation
      $guestEmail = $this->getUniqueEmail();
      $invitation = $this->createInvitation($guestEmail);
      $user = new \User();
      $user->getFromDB($invitation->getField(\User::getForeignKeyField()));

      // Enroll
      $serial = $this->getUniqueString();
      $agent = $this->enrollFromInvitation(
         $user, [
            'entities_id'       => $_SESSION['glpiactive_entity'],
            '_email'            => $guestEmail,
            '_invitation_token' => $invitation->getField('invitation_token'),
            '_serial'           => $serial,
            'csr'               => '',
            'firstname'         => 'John',
            'lastname'          => 'Doe',
            'version'           => $this->minAndroidVersion,
            'type'              => 'android',
            'inventory'         => TestingCommonTools::AgentXmlInventory($serial),
         ]
      );
      $this->boolean($agent->isNewItem())
         ->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Create fleet
      $fleet = $this->newTestedInstance();
      $fleet->add([
         'name' => $this->getUniqueString(),
      ]);
      $this->boolean($fleet->isNewItem())->isFalse();

      $fleetFk = \PluginFlyvemdmFleet::getForeignKeyField();
      $result = $agent->update([
         'id'     => $agent->getID(),
         $fleetFk => $fleet->getID(),
      ]);

      $this->boolean($result)->isTrue();

      // Apply a policy to the fleet
      $policyData = new \PluginFlyvemdmPolicy();
      $policyData->getFromDBBySymbol('disableGPS');
      $policyFk = $policyData::getForeignKeyField();

      $Task = new \PluginFlyvemdmTask();
      $Task->add([
         $policyFk => $policyData->getID(),
         $fleetFk  => $fleet->getID(),
         'value'   => '0',
      ]);

      // Check the policy is applied
      $this->boolean($Task->isNewItem())
         ->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Purge the fleet
      $this->boolean($fleet->delete(['id' => $fleet->getID()], 1))->isTrue();
      $fleetId = $fleet->getID();
      $rows = $agent->find("`$fleetFk`='$fleetId'");
      $this->integer(count($rows))->isEqualTo(0);

      // Check the policies are unlinked to the fleet
      $rows = $Task->find("`$fleetFk`='$fleetId'");
      $this->integer(count($rows))->isEqualTo(0);
   }

   /**
    * Create a new invitation
    *
    * @param $guestEmail
    * @return \PluginFlyvemdmInvitation
    */
   private function createInvitation($guestEmail) {
      $invitation = new \PluginFlyvemdmInvitation();
      $invitation->add([
         'entities_id' => $_SESSION['glpiactive_entity'],
         '_useremails' => $guestEmail,
      ]);
      $this->boolean($invitation->isNewItem())->isFalse();

      return $invitation;
   }

   /**
    *
    * Try to enroll an device by creating an agent. If the enrollment fails
    * the agent returned will not contain an ID. To ensore the enrollment succeeded
    * use isNewItem() method on the returned object.
    *
    * @param \User $user
    * @param array $input enrollment data for agent creation
    *
    * @return \PluginFlyvemdmAgent The agent instance
    */
   private function enrollFromInvitation(\User $user, array $input) {
      // Close current session
      $_REQUEST['user_token'] = \User::getToken($user->getID(), 'api_token');
      \Session::destroy();
      $this->setupGLPIFramework();

      // login as invited user
      $this->boolean($this->login('', '', false))->isTrue();
      unset($_REQUEST['user_token']);

      // Try to enroll
      $agent = new \PluginFlyvemdmAgent();
      $agent->add($input);

      return $agent;
   }
}
