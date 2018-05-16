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
 * @author    Domingo Oropeza
 * @copyright Copyright © 2018 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */
namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmMosquittoAuth extends CommonTestCase {

   private $mqttUser = null;

   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testAuthenticate':
            $this->mqttUser = new \PluginFlyvemdmMqttUser();
            $this->mqttUser->add([
               'user'      => 'john',
               'password'  => 'doe',
            ]);
            break;
      }
   }

   public function afterTestMethod($method) {
      switch ($method) {
         case 'testAuthenticate':
            $this->mqttUser->delete($this->mqttUser->fields, 1);
            break;
      }
   }

   public function providerAuthenticate() {
      return [
         [
            'input' => [
               'username' => 'foo',
               'password' => 'bar',
            ],
            'repoteIp' => '127.0.0.1',
            'expected' => [
               'httpCode' => '404',
            ]
         ],
         [
            'input' => [
               'username' => 'john',
               'password' => 'doe',
            ],
            'repoteIp' => '127.0.0.1',
            'expected' => [
               'httpCode' => '200',
            ]
         ],
         [
            'input' => [
               'username' => 'john',
               'password' => 'bar',
            ],
            'repoteIp' => '127.0.0.1',
            'expected' => [
               'httpCode' => '404',
            ]
         ],
         [
            'input' => [
               'username' => 'john',
               'password' => 'doe',
            ],
            'repoteIp' => '10.0.0.1',
            'expected' => [
               'httpCode' => '403',
            ]
         ],
      ];
   }

   /**
    * @dataProvider providerAuthenticate
    */
   public function testAuthenticate($input, $remoteIp, $expected) {
      $backupServer = $_SERVER;
      $_SERVER['REMOTE_ADDR'] = $remoteIp;

      $instance = new \PluginFlyvemdmMosquittoAuth();
      $output = $instance->authenticate($input);
      $this->integer((int) $output)->isEqualTo($expected['httpCode']);

      $_SERVER = $backupServer;
   }
}