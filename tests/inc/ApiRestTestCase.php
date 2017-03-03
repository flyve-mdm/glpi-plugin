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
 @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

use GuzzleHttp\Exception\ClientException;

class ApiRestTestCase extends CommonTestCase {
   protected $http_client;
   protected $base_uri = "";
   protected $last_error = "";

   protected $restResponse = null;
   protected $restHttpCode = 0;
   protected $restHeaders = [];

   protected static $backupServer;

   public static function setupBeforeClass() {
      global $CFG_GLPI;

      parent::setupBeforeClass();
      self::resetState();

      // enable api config
      $config = new Config;
      $config->update(
            array('id'                                => 1,
                  'enable_api'                        => true,
                  'enable_api_login_credentials'      => true,
                  'enable_api_login_external_token'   => true
            )
      );
      $CFG_GLPI['enable_api'] = 1;
      $CFG_GLPI['enable_api_login_credentials'] = 1;
      $CFG_GLPI['enable_api_login_external_token'] = 1;

      // Backup $_SERVER before changing it for API tests
      self::$backupServer = $_SERVER;
   }

   public function setUp() {
      global $CFG_GLPI;

      parent::setUp();

      $this->http_client = new GuzzleHttp\Client();
      $this->base_uri    = trim($CFG_GLPI['url_base_api'], "/")."/";

   }

   protected function doHttpRequest($method = "get", $relative_uri = "", $params = array()) {
      if (!empty($relative_uri)) {
         $params['headers']['Content-Type'] = "application/json";
      }
      $method = strtolower($method);
      if (in_array($method, array('get', 'post', 'delete', 'put', 'options', 'patch'))) {
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
      $apiRest = $this->getMockForItemtype(APIRest::class, ['getHttpBodyStream', 'returnResponse']);
      $apiRest->method('returnResponse')
              ->willReturnCallback(function ($_response, $_httpCode = 200, $_headers = array())
                                   use (&$response, &$httpCode, &$headers) {
                  $response = $_response;
                  $httpCode = $_httpCode;
                  $headers = $_headers;
                  // Emulate exit
                  throw new ApiExitException();
              });
      $apiRest->method('getHttpBodyStream')
              ->willReturn($body);

      try {
         $apiRest->call();
      } catch (ApiExitException $e) {
         // Emulated exit from the API
         unset($e);
      }
      $this->restHeaders = $headers;
      $this->restHttpCode = $httpCode;
      $this->restResponse = $response;
   }

   public function tearDown() {
      parent::tearDown();
      // restore $_SERVER after changing it for API tests
      $_SERVER = self::$backupServer;

      //Restart a session if previously closed by the API
      if (session_status() != PHP_SESSION_ACTIVE) {
         session_start();
         //$_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
      }
   }
}