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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *
 * @since 0.1.0.33
 */
class PluginStorkmdmPolicyFactory {

   /**
    * Create an empty policy by type
    * @param string $type
    */
   public function createFromPolicy(PluginStorkmdmPolicy $policyData) {
      switch ($policyData->getField('type')) {
         case 'string':
            $policy = new PluginStorkmdmPolicyString($policyData);
            break;

         case 'bool':
            $policy = new PluginStorkmdmPolicyBoolean($policyData);
            break;

         case 'int':
            $policy = new PluginStorkmdmPolicyInteger($policyData);
            break;

         case 'dropdown':
            $policy = new PluginStorkmdmPolicyDropdown($policyData);
            break;

         case 'deployapp':
            $policy = new PluginStorkmdmPolicyDeployapplication($policyData);
            break;

         case 'removeapp':
            $policy = new PluginStorkmdmPolicyRemoveapplication($policyData);
            break;

         case 'deployfile':
            $policy = new PluginStorkmdmPolicyDeployfile($policyData);
            break;

         case 'removefile':
            $policy = new PluginStorkmdmPolicyRemovefile($policyData);
            break;

         default:
            return null;
      }

      return $policy;
   }

   /**
    * Create and returns a policy from DB
    * @param integer $id
    * @return PluginStorkmdmPolicyInterface
    */
   public function createFromDBByID($id) {
      $policyData = new PluginStorkmdmPolicy();
      if (!$policyData->getFromDB($id)) {
         return null;
      }

      return $this->createFromPolicy($policyData);
   }


}