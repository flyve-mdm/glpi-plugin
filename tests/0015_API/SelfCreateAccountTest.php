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

class SelfCreateAccountTest extends ApiRestTestCase
{

   protected $dataSet;
   protected static $login = "user@example.com";
   protected static $password = "password";

   public function accountCreationProvider() {
      return [
         "bad retyped password" => [
               "data"      => [
                     "name"      => "user@local.example.com",
                     "password"  => "my-password",
                     "password2" => "password",
               ],
               "expected"  => [
                     'status'    => 400,
               ]
         ],
         "bad email" => [
               "data"      => [
                     'name'      => "user",
                     'password'  => "my-password",
                     'password2' => "password",
               ],
               "expected"  => [
                     'status'    => 400,
               ]
         ],
         "OK" => [
               "data"      => [
                     'name'      => self::$login,
                     'password'  => self::$password,
                     'password2' => self::$password,
               ],
               "expected"  => [
                     'status'    => 201,
               ]
         ],
      ];
   }

   /**
    * @return string
    */
   public function testInitializeSessionCredentials() {
      $user = new User();
      $user->getFromDBbyName('storknologin');
      $this->assertFalse($user->isNewItem());
      $userToken = $user->getField('personal_token');

      $res = $this->doHttpRequest('GET', 'initSession/',
           ['headers' => [
               'Authorization' => "user_token $userToken"
           ]]
      );

      $this->assertEquals(200, $res->getStatusCode(), $this->last_error);

      $body = $res->getBody();
      $data = json_decode($body, true);
      $this->assertNotEquals(false, $data);
      $this->assertArrayHasKey('session_token', $data);

      return $data['session_token'];
   }

   /**
    * @dataProvider accountCreationProvider
    * @depends testInitializeSessionCredentials
    * @param unknown $sessionToken
    */
   public function testCreateAccount($data, $expected, $sessionToken) {
      $body = [
            'input'  => $data
      ];

      $res = $this->doHttpRequest('POST', 'PluginStorkmdmUser/',
            [
                  'headers'   => [
                        'Session-Token' => $sessionToken
                  ],
                  'body'      => json_encode($body, JSON_UNESCAPED_SLASHES),
            ]
      );
      $this->assertEquals($expected['status'], $res->getStatusCode(), $this->last_error);
      if ($expected['status'] < 300) {
         $response = json_decode($res->getBody(), JSON_OBJECT_AS_ARRAY);
         $this->assertArrayHasKey('id', $response);

         // Check the user exists
         $user = new User();
         $user->getFromDB($response['id']);
         $this->assertFalse($user->isNewItem());
      }
   }

   /**
    * @depends testInitializeSessionCredentials
    * @param unknown $sessionToken
    */
   public function testCannotCreateUser($sessionToken) {
      $body = [
            'input'  => [
                  'name'      => 'jdoe',
                  'password'  => 'password',
                  'password2' => 'password'
            ]
      ];

      $res = $this->doHttpRequest('POST', 'User/',
            [
                  'headers'   => [
                        'Session-Token' => "$sessionToken"
                  ],
                  'body'      => json_encode($body, JSON_UNESCAPED_SLASHES),
            ]
      );
      $this->assertEquals(400, $res->getStatusCode(), $this->last_error);

   }

   public function testLogin() {
      $res = $this->doHttpRequest('GET', 'initSession/',
            [
                  'headers'   => [
                        'Authorization' => 'Basic ' . base64_encode(self::$login . ':' . self::$password),
                  ],
            ]
            );
      $this->assertEquals(200, $res->getStatusCode(), $this->last_error);
      $response = json_decode($res->getBody(), JSON_OBJECT_AS_ARRAY);
      $this->assertArrayHasKey('session_token', $response);
   }
}