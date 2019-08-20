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
 * @copyright Copyright © 2018 Teclib
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmFleet extends CommonTestCase {

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->login('glpi', 'glpi');
   }

   public function afterTestMethod($method) {
      parent::afterTestMethod($method);
      $this->terminateSession();
   }

   /**
    * @tags testDeleteDefaultFleet
    */
   public function testDeleteDefaultFleet() {
      // Creating an entity automatically creates a default fleet
      $entity = new \Entity();
      $entityId = $entity->import(['completename' => 'delete default fleet']);

      $fleet = $this->newTestedInstance();
      $request = [
         'AND' => [
            'is_default' => '1',
            \Entity::getForeignKeyField() => $entityId,
         ]
      ];
      $this->boolean($fleet->getFromDBByCrit($request))
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
      $userId = $invitation->getField(\User::getForeignKeyField());

      // Enroll
      $serial = $this->getUniqueString();
      $input = [
         'entities_id'        => $_SESSION['glpiactive_entity'],
         '_email'             => $guestEmail,
         '_invitation_token'  => $invitation->getField('invitation_token'),
         '_serial'            => $serial,
         'csr'                => '',
         'firstname'          => 'John',
         'lastname'           => 'Doe',
         'version'            => \PluginFlyvemdmAgent::MINIMUM_ANDROID_VERSION . '.0',
         'type'               => 'android',
         'inventory'          => CommonTestCase::AgentXmlInventory($serial),
         'notification_type'  => 'mqtt',
         'notification_token' => '',
      ];
      $agent = $this->enrollFromInvitation($userId, $input);
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
         $policyFk            => $policyData->getID(),
         'itemtype_applied'   => $fleet->getType(),
         'items_id_applied'   => $fleet->getID(),
         'value'              => '0',
      ]);

      // Check the policy is applied
      $this->boolean($Task->isNewItem())
         ->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Purge the fleet
      $this->boolean($fleet->delete(['id' => $fleet->getID()], 1))->isTrue();
      $fleetType = $fleet->getType();
      $fleetId = $fleet->getID();
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`$fleetFk`='$fleetId'";
      } else {
         $condition = [
            $fleetFk => $fleetId,
         ];
      }
      $rows = $agent->find($condition);
      $this->integer(count($rows))->isEqualTo(0);

      // Check the policies are unlinked to the fleet
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $condition = "`itemtype_applied`='$fleetType' AND `items_id_applied`='$fleetId'";
      } else {
         $condition = [
            'itemtype_applied' => $fleetType,
            'items_id_applied' => $fleetId,
         ];
      }
      $rows = $Task->find($condition);
      $this->integer(count($rows))->isEqualTo(0);
   }
}
