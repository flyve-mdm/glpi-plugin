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

$category = 'Deployment';
return [
   [
      'name'                                => __('Deploy application', 'flyvemdm'),
      'symbol'                              => 'deployApp',
      'group'                               => 'application',
      'type'                                => 'deployapp',
      'type_data'                           => '',
      'unicity'                             => 0,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Deploy an application on the device',
         'flyvemdm'),
      'default_value'                       => '',
      'recommended_value'                   => '',
      'is_android_policy'                   => '1',
      'is_android_system'                   => '0',
      'is_apple_policy'                     => '0',
   ],

   [
      'name'                                => __('Remove application', 'flyvemdm'),
      'symbol'                              => 'removeApp',
      'group'                               => 'application',
      'type'                                => 'removeapp',
      'type_data'                           => '',
      'unicity'                             => 0,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Uninstall an application on the device',
         'flyvemdm'),
      'default_value'                       => '',
      'recommended_value'                   => '',
      'is_android_policy'                   => '1',
      'is_android_system'                   => '0',
      'is_apple_policy'                     => '0',
   ],

   [
      'name'                                => __('Deploy file', 'flyvemdm'),
      'symbol'                              => 'deployFile',
      'group'                               => 'file',
      'type'                                => 'deployfile',
      'type_data'                           => '',
      'unicity'                             => 0,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Deploy a file on the device', 'flyvemdm'),
      'default_value'                       => '',
      'recommended_value'                   => '',
      'is_android_policy'                   => '1',
      'is_android_system'                   => '0',
      'is_apple_policy'                     => '0',
   ],

   [
      'name'                                => __('Remove file', 'flyvemdm'),
      'symbol'                              => 'removeFile',
      'group'                               => 'file',
      'type'                                => 'removefile',
      'type_data'                           => '',
      'unicity'                             => 0,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Uninstall a file on the device',
         'flyvemdm'),
      'default_value'                       => '',
      'recommended_value'                   => '',
      'is_android_policy'                   => '1',
      'is_android_system'                   => '0',
      'is_apple_policy'                     => '0',
   ],

   [
      'name'                                => __('Disable unknown sources', 'flyvemdm'),
      'symbol'                              => 'disableUnknownAppSources',
      'group'                               => 'phone',
      'type'                                => 'bool',
      'type_data'                           => '',
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Disable installation of apps from unknown sources',
         'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '0',
      'is_android_policy'                   => '1',
      'is_android_system'                   => '1',
      'is_apple_policy'                     => '0',
   ],
];