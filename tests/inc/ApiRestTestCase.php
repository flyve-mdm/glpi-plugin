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

use GuzzleHttp\Exception\ClientException;

class ApiRestTestCase extends CommonTestCase {
   protected $http_client;
   protected $base_uri = "";
   protected $last_error = "";

   public static function setupBeforeClass() {
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
   }

   protected function setUp() {
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

}