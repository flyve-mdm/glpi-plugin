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

class ComputerPurgeCleanupsGeolocationTest extends RegisteredUserTestCase
{

   public function testInitCreateComputer() {
      $computer = new Computer();
      $computer->add([
            'name'         => 'computer',
            'entities_id'  => $_SESSION['glpiactive_entity'],
      ]);
      $this->assertFalse($computer->isNewItem());

      return $computer;
   }

   /**
    * @depends testInitCreateComputer
    * @param Computer $computer
    * @return PluginStorkmdmGeolocation
    */
   public function testInitCreateGeolocationEntries(Computer $computer) {
      $geolocation = new PluginStorkmdmGeolocation();
      $geolocation->add([
            'computers_id' => $computer->getID(),
            'latitude'     => '1',
            'longitude'    => '1',
            'entities_id'  => $computer->getField('entities_id'),
      ]);

      $this->assertFalse($geolocation->isNewItem());

      return $geolocation;
   }

   /**
    * @depends testInitCreateComputer
    * @depends testInitCreateGeolocationEntries
    */
   public function testPurgeComputer(Computer $computer, PluginStorkmdmGeolocation $geolocation) {
      $compuerId = $computer->getID();
      $geolocationId = $geolocation->getID();
      $computer->delete([
            'id'  => $compuerId
      ], true);
      $computer = new Computer();
      $geolocation = new PluginStorkmdmGeolocation();
      $computer->getFromDB($compuerId);
      $geolocation->getFromDB($geolocationId);

      $this->assertTrue($computer->isNewItem());
      $this->assertTrue($geolocation->isNewItem());
   }
}