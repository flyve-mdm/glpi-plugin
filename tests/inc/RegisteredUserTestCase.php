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

use Glpi\Test\CommonTestCase;

class RegisteredUserTestCase extends CommonTestCase
{
   protected static $fixture;

   public static function setupBeforeClass() {
      parent::setupBeforeClass();
      self::resetState();
      self::setupGLPIFramework();

      //Fixture
      self::$fixture['registeredUserEmail']     = 'registereduser@localhost.local';
      self::$fixture['registeredUserPasswd']    = 'password';

      //       self::login('glpi', 'glpi', true);
      //       $user = new PluginFlyvemdmUser();
      //       $userId = $user->add([
      //          'name'      => self::$fixture['registeredUserEmail'],
      //          'password'  => self::$fixture['registeredUserPasswd'],
      //          'password2' => self::$fixture['registeredUserPasswd']
      //       ]);

      //       Session::destroy();
   }

   public function setUp() {
      self::setupGLPIFramework();
      //$this->assertTrue(self::login(self::$fixture['registeredUserEmail'], self::$fixture['registeredUserPasswd']));
      $this->assertTrue(self::login('glpi', 'glpi', true));
   }

}
