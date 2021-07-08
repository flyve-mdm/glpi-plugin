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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

$category = 'Security > Phone';
return [
   [
      'name'                                => __('Set a SIM card PIN', 'flyvemdm'),
      'symbol'                              => 'setSimCardPin',
      'group'                               => 'phone',
      'type'                                => 'int',
      'type_data'                           => '',
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => 'Security > Phone',
      'comment'                             => __('Set a SIM card PIN', 'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '0',
      'is_android_system'                   => '1',
      'android_min_version'                 => '1.6',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Set a SIM card PIN2', 'flyvemdm'),
      'symbol'                              => 'setSimCardPin2',
      'group'                               => 'phone',
      'type'                                => 'int',
      'type_data'                           => '',
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => 'Security > Phone',
      'comment'                             => __('Set a SIM card PIN2', 'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '0',
      'is_android_system'                   => '1',
      'android_min_version'                 => '1.6',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],
];