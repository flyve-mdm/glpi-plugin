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
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

$category = 'Mobile Device Management';
$mdm = [
];

// TODO Specific category because we will have new features related to geolocation
// - disable geolocation reporting with time intervals
// - disable geolocation reporting with geographic areas
$category = 'Mobile Device Management > Geolocation';
$mdmGeolocation = [
   [
      'name'                                => __('Periodic geolocation', 'flyvemdm'),
      'symbol'                              => 'periodicGeolocation',
      'group'                               => 'encryption',
      'type'                                => 'int',
      'type_data'                           => [
         "min" => 0,
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Get geolocation with a specified periodicity (in seconds) and sends it to the server.',
         'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '0',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],
];

return array_merge($mdm, $mdmGeolocation);