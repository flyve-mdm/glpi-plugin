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
namespace Glpi\Tests;

use APIRest;
use Exception;

class ApiRestTestCase extends CommonTestCase {
   protected $http_client;
   protected $base_uri = "";
   protected $last_error = "";

   protected $restResponse = null;
   protected $restHttpCode = 0;
   protected $restHeaders = [];

   protected static $backupServer;
   protected static $backupGet;

   public static function setupBeforeClass() {
      global $CFG_GLPI;

      parent::setupBeforeClass();
      self::resetState();

      // Backup $_SERVER before changing it for API tests
      self::$backupServer = $_SERVER;
      self::$backupGet = $_GET;
   }

   public function setUp() {
      parent::setUp();
      self::setupGLPIFramework();
   }

   protected function doHttpRequest($method = "get", $relative_uri = "", $params = []) {
      if (!empty($relative_uri)) {
         $params['headers']['Content-Type'] = "application/json";
      }
      $method = strtolower($method);
      if (in_array($method, ['get', 'post', 'delete', 'put', 'options', 'patch'])) {
         try {
            return $this->http_client->{$method}($this->base_uri.$relative_uri,
                                                 $params);
         } catch (Exception $e) {
            if ($e->hasResponse()) {
               $this->last_error = $e->getResponse();
               return $e->getResponse();
            }
         }
      }
   }

   /**
    * emulate a HTTP request by a direct call to the API
    *
    * @param string $method
    * @param string $relative_uri
    * @param array  $reqHeaders
    * @param string $body
    * @param array  $params
    */
   protected function emulateRestRequest($method = 'get', $relativeUri = '', $reqHeaders = [], $body = '', $params = []) {
      // Prepare $_SERVER vars
      $_SERVER["REMOTE_ADDR"] = '127.0.0.1';
      $_SERVER['REQUEST_METHOD'] = strtoupper($method);
      $_SERVER['PATH_INFO'] = '/' . $relativeUri;
      $_SERVER['REQUEST_URI'] = '/apirest.php' . $_SERVER['PATH_INFO'];
      $_SERVER['QUERY_STRING'] = http_build_query($params);
      $_SERVER["SCRIPT_FILENAME"] = 'apirest.php';
      $_GET = $params;
      foreach ($reqHeaders as $headerName => $headerValue) {
         $headerName = str_replace('-', '_', $headerName);
         $headerName = 'HTTP_' . strtoupper($headerName);
         $_SERVER[$headerName] = $headerValue;
      }

      if (!isset($reqHeaders['content_type'])) {
         $_SERVER['CONTENT_TYPE'] = 'application/json';
      } else {
         $_SERVER['CONTENT_TYPE'] = $reqHeaders['content_type'];
      }

      if (isset($reqHeaders['authorization'])) {
         if (strpos($reqHeaders['authorization'], 'Basic ') === 0) {
            $credentials = str_replace('Basic ', '', $reqHeaders['authorization']);
            list($user, $pass) = explode(':', base64_decode($credentials, true));
            $_SERVER['PHP_AUTH_USER'] = $user;
            $_SERVER['PHP_AUTH_PW'] = $pass;
         }
      }

      $response = [];
      $httpCode = 0;
      $headers = [];
      $apiRest = $this->getMockForItemtype(APIRest::class, ['getHttpBody', 'returnResponse']);
      $apiRest->method('returnResponse')
              ->willReturnCallback(function ($_response, $_httpCode = 200, $_headers = [])
                                   use (&$response, &$httpCode, &$headers) {
                  $response = $_response;
                  $httpCode = $_httpCode;
                  $headers = $_headers;
                  // Emulate exit
                  throw new ApiExitException();
              });
      $apiRest->method('getHttpBody')
              ->willReturn($body);

      try {
         $apiRest->call();
      } catch (ApiExitException $e) {
         // Emulated exit from the API
         $dummy = null; // To avoid complan of PHPCS;  there is actually nothing to do here
      }
      $this->terminateSession();

      $this->restHeaders = $headers;
      $this->restHttpCode = $httpCode;
      $this->restResponse = $response;
   }

   /**
    * Initialize a session from credentials
    *
    * @param string $name
    * @param string $pass
    */
   public function initSessionByCredentials($name, $pass, $appToken = null) {
      $userCredentials = $name . ':' . $pass;
      $userCredentials = base64_encode($userCredentials);

      $headers = ['authorization' => "Basic $userCredentials"];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }
      $this->emulateRestRequest('get', 'initSession', $headers);
   }

   /**
    * Initialize a session from a user token
    *
    * @param string $userToken
    * @param string $appToken
    */
   public function initSessionByUserToken($userToken, $appToken = null) {
      $headers = ['authorization' => "user_token $userToken"];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }
      $this->emulateRestRequest('get', 'initSession', $headers);
   }

   /**
    * Terminate a session
    *
    * @param string $sessionToken
    * @param string $appToken
    */
   protected function killSession($sessionToken, $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }
      $this->emulateRestRequest('get', 'killSession', $headers);
      //Restart a session if previously closed by the API
      if (session_status() != PHP_SESSION_ACTIVE) {
         session_start();
         session_regenerate_id();
         session_id();
         //$_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
      }
      self::setupGLPIFramework();
   }

   /**
    * get profiles usable by the user
    *
    * @param string $sessionToken
    * @param string $appToken
    */
   protected function getMyProfiles($sessionToken, $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest('get', 'getMyProfiles', $headers);
   }

   /**
    * get active profile of the user
    *
    * @param string $sessionToken
    * @param string $appToken
    */
   protected function getActiveProfile($sessionToken, $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest('get', 'getMyProfiles', $headers);
   }

   /**
    * Change active profile
    *
    * @param string $sessionToken
    * @param string $profileId
    */
   protected function changeActiveProfile($sessionToken, $profileId, $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }
      $params = ['profiles_id' => $profileId];

      $this->emulateRestRequest('get', "changeActiveProfile", $headers, '', $params);
   }

   /**
    * get session data
    * @param unknown $sessionToken
    * @param unknown $appToken
    */
   protected function getFullSession($sessionToken, $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }
      $this->emulateRestRequest('get', "getFullSession", $headers);
   }

   protected function entity($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'entity', $headers, $body, $params);
   }

   public function tearDown() {
      parent::tearDown();
      // restore $_SERVER after changing it for API tests
      $_SERVER = self::$backupServer;
      $_GET = self::$backupGet;
      $this->restartSession();
   }
}