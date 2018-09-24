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
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class Entity extends CommonTestCase {

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->login('glpi', 'glpi');
   }

   /**
    * @tags testDeleteEntity
    */
   public function testDeleteEntity() {
      $entity = $this->newTestedInstance();
      $entityId = $entity->add([
         'name' => 'to be deleted',
      ]);
      $guestEmail = 'a.user@localhost.local';
      $invitation = new \PluginFlyvemdmInvitation();
      $invitation->add([
         'entities_id' => $entityId,
         '_useremails' => $guestEmail,
      ]);
      $guestUser = new \User();
      $guestUser->getFromDB($invitation->getField('users_id'));
      $_REQUEST['user_token'] = \User::getToken($invitation->getField('users_id'), 'api_token');
      $this->login('', '', false);

      unset($_REQUEST['user_token']);

      $agent = $this->newMockInstance(\PluginFlyvemdmAgent::class);
      $this->calling($agent)->notify->doesNothing;
      $agent->add([
         'entities_id'       => $entityId,
         '_email'            => $guestEmail,
         '_invitation_token' => $invitation->getField('invitation_token'),
         '_serial'           => 'AZERTY',
         'csr'               => '',
         'firstname'         => 'John',
         'lastname'          => 'Doe',
         'version'           => '1.0.0',
      ]);
      $this->login('glpi', 'glpi');
      $defaultFleet = new \PluginFlyvemdmFleet();
      $defaultFleet->getDefaultFleet($entityId);
      $fleet = $this->newMockInstance(\PluginFlyvemdmFleet::class, '\MyMock');
      $fleet->getMockController()->post_addItem = function () {};
      $this->calling($fleet)->notify->doesNothing;
      $fleet->add([
         'name'        => 'a fleet',
         'entities_id' => $entityId,
      ]);
      $package = $this->createDummyPackage($entityId);
      $file = $this->createDummyFile($entityId);

      $entity->delete(['id' => $entity->getID()]);
      $this->boolean($invitation->getFromDB($invitation->getID()))->isFalse();
      $this->boolean($defaultFleet->getFromDB($defaultFleet->getID()))->isFalse();
      $this->boolean($fleet->getFromDB($fleet->getID()))->isFalse();
      $this->boolean($agent->getFromDB($agent->getID()))->isFalse();
      $this->boolean($package->getFromDB($package->getID()))->isFalse();
      $this->boolean($file->getFromDB($file->getID()))->isFalse();

      $entityConfig = new \PluginFlyvemdmEntityConfig();
      $this->integer(count($entityConfig->find("`entities_id` = '$entityId'")))->isEqualTo(0);
   }
}
