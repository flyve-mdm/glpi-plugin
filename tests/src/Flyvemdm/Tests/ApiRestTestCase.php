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
 * @copyright Copyright © 2018 Teclib
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */
namespace Flyvemdm\Tests;

use Glpi\Tests\ApiRestTestCase as GlpiApiRestTestCase;

class ApiRestTestCase extends GlpiApiRestTestCase
{

   protected function agent($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmAgent', $headers, $body, $params);
   }

   protected function invitation($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmInvitation', $headers, $body, $params);
   }

   protected function fleet($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmFleet', $headers, $body, $params);
   }

   protected function file($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmFile', $headers, $body, $params);
   }

   /**
    * @deprecated
    * @param $method
    * @param $sessionToken
    * @param string $body
    * @param array $params
    * @param string|null $appToken
    */
   protected function fleet_policy($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmFleet_Policy', $headers, $body, $params);
   }

   protected function task($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmTask', $headers, $body, $params);
   }

   protected function geolocation($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmGeolocation', $headers, $body, $params);
   }

   protected function invitationLog($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmInvitationLog', $headers, $body, $params);
   }

   protected function mqttLog($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmMqttLog', $headers, $body, $params);
   }

   protected function package($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmPackage', $headers, $body, $params);
   }

   protected function policy($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmPolicy', $headers, $body, $params);
   }

   protected function wellknownpath($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmWellknownpath', $headers, $body, $params);
   }

   protected function entityconfig($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmEntityconfig', $headers, $body, $params);
   }

   protected function accountvalidation($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmAccountValidation', $headers, $body, $params);
   }

   protected function captcha($method, $sessionToken, $body = '', $params = [], $appToken = null) {
      $headers = ['Session-Token' => $sessionToken];
      if ($appToken !== null) {
         $headers['App-Token'] = $appToken;
      }

      $this->emulateRestRequest($method, 'PluginFlyvemdmdemoCaptcha', $headers, $body, $params);
   }

}
