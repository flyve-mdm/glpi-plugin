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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *
 * @since 0.1.0.33
 */
class PluginFlyvemdmPolicyFactory {

   /**
    * @var Psr\Container\ContainerInterface
    */
   protected $container;


   public function __construct() {
      global $pluginFlyvemdmContainer;

      $this->container = $pluginFlyvemdmContainer;
   }

   /**
    * Create an empty policy by type
    *
    * @param PluginFlyvemdmPolicy $policyData
    * @return null|PluginFlyvemdmPolicyBase depending on the field type.
    */
   public function createFromPolicy(PluginFlyvemdmPolicy $policyData) {
      $parameters = ['policy' => $policyData];
      switch ($policyData->getField('type')) {
         case 'string':
            $policy = $this->container->make(PluginFlyvemdmPolicyString::class, $parameters);
            break;

         case 'bool':
            $policy = $this->container->make(PluginFlyvemdmPolicyBoolean::class, $parameters);
            break;

         case 'int':
            $policy = $this->container->make(PluginFlyvemdmPolicyInteger::class, $parameters);
            break;

         case 'dropdown':
            $policy = $this->container->make(PluginFlyvemdmPolicyDropdown::class, $parameters);
            break;

         case 'deployapp':
            $policy = $this->container->make(PluginFlyvemdmPolicyDeployapplication::class, $parameters);
            break;

         case 'removeapp':
            $policy = $this->container->make(PluginFlyvemdmPolicyRemoveapplication::class, $parameters);
            break;

         case 'deployfile':
            $policy = $this->container->make(PluginFlyvemdmPolicyDeployfile::class, $parameters);
            break;

         case 'removefile':
            $policy = $this->container->make(PluginFlyvemdmPolicyRemovefile::class, $parameters);
            break;

         default:
            return null;
      }

      return $policy;
   }

   /**
    * Create and returns a policy from DB
    *
    * @param integer $id
    * @return PluginFlyvemdmPolicyBase
    */
   public function createFromDBByID($id) {
      $policyData = $this->container->make(PluginFlyvemdmPolicy::class);
      if (!$policyData->getFromDB($id)) {
         return null;
      }

      return $this->createFromPolicy($policyData);
   }
}
