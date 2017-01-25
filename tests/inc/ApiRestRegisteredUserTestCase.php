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

class ApiRestRegisteredUserTestCase extends ApiRestTestCase
{
   protected static $login          = 'registereduser@localhost.local';
   protected static $password       = 'password';
   protected static $sessionToken   = null;

   public static function setUpBeforeClass() {
      parent::setupBeforeClass();
      self::createRegisteredUser();
      self::loginAsRegisteredUser();
   }

   public function setUp() {
      parent::setUp();

   }

   protected static function createRegisteredUser() {
      global $CFG_GLPI;

      // Login as glpi
      $httpClient = new GuzzleHttp\Client();
      $res = $httpClient->get(
            trim($CFG_GLPI['url_base_api'], "/") . "/initSession",
            [
                  'headers' => [
                        'Authorization' => 'Basic ' . base64_encode('glpi:glpi'),
                  ]
            ]
      );
      $sessionToken = self::getSessionToken($res);

      // Create a registered user
      if ($sessionToken === null) {
         $res = null;
      } else {
         $body = [
               'input'  => [
                     'name'      => self::$login,
                     'password'  => self::$password,
                     'password2' => self::$password,
               ]
         ];
         $res = $httpClient->post(
               trim($CFG_GLPI['url_base_api'], "/") . "/PluginStorkmdmUser",
               [
                     'headers'   => [
                           'Session-Token'   => $sessionToken,
                           'Content-Type'    => 'application/json'
                     ],
                     'body'      => json_encode($body, JSON_UNESCAPED_SLASHES),
               ]
         );
      }
   }

   protected static function loginAsRegisteredUser() {
      global $CFG_GLPI;

      $httpClient = new GuzzleHttp\Client();
      $res = $httpClient->get(
           trim($CFG_GLPI['url_base_api'], "/") . "/initSession",
           [
                 'headers' => [
                       'Authorization' => 'Basic ' . base64_encode(self::$login . ':' . self::$password),
                 ]
           ]
      );
      if ($res !== null && $res->getStatusCode() == 200) {
         self::$sessionToken = self::getSessionToken($res);
      }
   }

   protected static function getSessionToken($res) {
      $sessionToken = null;
      if ($res !== null && $res->getStatusCode() == 200) {
         $response = json_decode($res->getBody(), JSON_OBJECT_AS_ARRAY);
         if ($response !== null) {
            $sessionToken = $response['session_token'];
         }
      }
      return $sessionToken;
   }
}