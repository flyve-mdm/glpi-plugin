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

/**
 * Entry point for installation process
 */
function plugin_flyvemdm_install() {
   global $DB;

   require_once(PLUGIN_FLYVEMDM_ROOT . "/install/installer.class.php");
   $installer = new PluginFlyvemdmInstaller();

   return $installer->install();
}

/**
 * Uninstalls the plugin
 * @return boolean True if success
 */
function plugin_flyvemdm_uninstall() {
   require_once(PLUGIN_FLYVEMDM_ROOT . "/install/installer.class.php");
   $installer = new PluginFlyvemdmInstaller();

   return $installer->uninstall();
}

/**
 * Second pass of initialization after all other initiaization of other plugins
 * Also force inclusion of this file
 */
function plugin_flyvemdm_postinit() {
   $plugin = new Plugin();
   if ($plugin->isActivated('orion')) {
      try {
         PluginOrionReport::registerItemtype(PluginFlyvemdmPackage::class);
      } catch (Exception $e) {
         Toolbox::logInFile('php-errors', $e->getMessage());
      }
   }
}

/**
 * @param string $type
 * @return array
 */
function plugin_flyvemdm_MassiveActions($type) {
   switch ($type) {
      case 'User':
         return [PluginFlyvemdmInvitation::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'InviteUser' =>
               __("Invite to enroll a device", 'flyvemdm')];
   }

   return [];
}

/**
 * Actions done when a profile_user is being purged
 * @param CommonDBTM $item
 */
function plugin_flyvemdm_hook_pre_profileuser_purge(CommonDBTM $item) {
   $config = Config::getConfigurationValues('flyvemdm', ['guest_profiles_id', 'registered_profiles_id']);
   $guestProfileId = $config['guest_profiles_id'];

   if ($item->getField('profiles_id') == $guestProfileId) {
      $invitation = new PluginFlyvemdmInvitation();
      if (!$invitation->deleteByCriteria(['users_id' => $item->getField('users_id')])) {
         $item->input = false;
      }
   }
}

/**
 * Define Dropdown tables to be managed in GLPI
 * @return array
 */
function plugin_flyvemdm_getDropdown() {
   $plugin = new Plugin();
   if ($plugin->isActivated('flyvemdm')) {
      // Table => Name
      return [
         PluginFlyvemdmWellknownpath::class  => __('Well known Android\'s FS paths', 'flyvemdm'),
         PluginFlyvemdmPolicyCategory::class => __('Policy Categories', 'flyvemdm')
      ];
   } else {
      return [];
   }
}

/**
 * @param string $itemtype
 * @return string
 */
function plugin_flyvemdm_addDefaultSelect($itemtype) {
   $selected = '';

   return $selected;
}

/**
 * Add a default WHERE in SELECT queries on itemtypes
 * @param string $itemtype Itemtype
 * @return string
 */
function plugin_Flyvemdm_addDefaultJoin($itemtype) {
   switch ($itemtype) {
      case PluginFlyvemdmGeolocation::class:
         return PluginFlyvemdmGeolocation::addDefaultJoin();

      case PluginFlyvemdmAgent::class:
         return PluginFlyvemdmAgent::addDefaultJoin();
   }
}

/**
 * Add a default WHERE in SELECT queries on itemtypes
 * @param string $itemtype Itemtype
 * @return string
 */
function plugin_Flyvemdm_addDefaultWhere($itemtype) {
   switch ($itemtype) {
      case PluginFlyvemdmGeolocation::class:
         return PluginFlyvemdmGeolocation::addDefaultWhere();

      case PluginFlyvemdmAgent::class:
         return PluginFlyvemdmAgent::addDefaultWhere();
   }

}

/**
 * @return array
 */
function plugin_flyvemdm_getDatabaseRelations() {
   return [
   ];
}

/**
 * @param CommonDBTM $item
 */
function plugin_flyvemdm_hook_entity_add(CommonDBTM $item) {
   if ($item instanceof Entity) {
      $entityConfig = new PluginFlyvemdmEntityConfig();
      $entityConfig->hook_entity_add($item);

      $fleet = new PluginFlyvemdmFleet();
      $fleet->hook_entity_add($item);
   }
}

/**
 * @param CommonDBTM $item
 */
function plugin_flyvemdm_hook_entity_purge(CommonDBTM $item) {
   if ($item instanceof Entity) {
      $itemtypes = [
         PluginFlyvemdmEntityConfig::class,
         PluginFlyvemdmInvitation::class,
         PluginFlyvemdmAgent::class,
         PluginFlyvemdmFleet::class,
         PluginFlyvemdmPackage::class,
         PluginFlyvemdmFile::class,
      ];

      foreach ($itemtypes as $itemtype) {
         $itemToPurge = new $itemtype();
         $itemToPurge->hook_entity_purge($item);
      }
   }
}

/**
 * hook when a computer is being purged
 *
 * @param CommonDBTM $item
 */
function plugin_flyvemdm_hook_computer_purge(CommonDBTM $item) {
   $geolocation = new PluginFlyvemdmGeolocation();
   $geolocation->hook_computer_purge($item);
   $agent = new PluginFlyvemdmAgent();
   $agent->hook_computer_purge($item);
}

function plugin_flyvemdm_hook_pre_invitation_purge(CommonDBTM $item) {
   $success = true;

   $invitation = new PluginFlyvemdmInvitation();
   $success = $success && $invitation->hook_pre_invitation_purge($item);

   return $success;
}
