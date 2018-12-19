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
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;
use Computer;

class PluginFlyvemdmGeolocation extends CommonTestCase {

   /**
    * @tags testPurgeComputer
    */
   public function testPurgeComputer() {
      $this->login('glpi', 'glpi');
      $this->given($computer = new Computer())
         ->and($computerData = [
            'name'        => 'computer',
            'entities_id' => $_SESSION['glpiactive_entity'],
         ],
            $computer->add($computerData),
            $this->boolean($computer->isNewItem())->isFalse(),
            $geolocationData = [
               'computers_id' => $computer->getID(),
               'latitude'     => '1',
               'longitude'    => '1',
               'entities_id'  => $computer->getField('entities_id'),
            ],
            $geolocation = $this->newTestedInstance(),
            $geolocation->add($geolocationData))
         ->then()
         ->boolean($computer->isNewItem())->isFalse("Computer not created")
         ->boolean($geolocation->isNewItem())->isFalse("Geolocation not created")
         ->then($computer->delete(['id' => $computer->getID()], 1))
         ->boolean($computer->getFromDB($computer->getID()))->isFalse("Computer not deleted")
         ->boolean($geolocation->getFromDB($geolocation->getID()))
         ->isFalse("Geolocation not deleted");
   }

}
