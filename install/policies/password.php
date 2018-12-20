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

$category = 'Security > Authentication > Password';
return [
   [
      'name'                                => __('Password enabled', 'flyvemdm'),
      'symbol'                              => 'passwordEnabled',
      'group'                               => 'policies',
      'type'                                => 'dropdown',
      'type_data'                           => [
         "PASSWORD_NONE"   => __('No', 'flyvemdm'),
         "PASSWORD_PIN"    => __('Pin', 'flyvemdm'),
         "PASSWORD_PASSWD" => __('Password', 'flyvemdm'),
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Enable the password.', 'flyvemdm'),
      'default_value'                       => 'PASSWORD_NONE',
      'recommended_value'                   => 'PASSWORD_PIN',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Minimum password length', 'flyvemdm'),
      'symbol'                              => 'passwordMinLength',
      'group'                               => 'policies',
      'type'                                => 'int',
      'type_data'                           => [
         "min" => 0,
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Set the required number of characters for the password. For example, you can require PIN or passwords to have at least six characters.',
         'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '6',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Password quality', 'flyvemdm'),
      'symbol'                              => 'passwordQuality',
      'group'                               => 'policies',
      'type'                                => 'dropdown',
      'type_data'                           => [
         "PASSWORD_QUALITY_UNSPECIFIED"  => __('Unspecified', 'flyvemdm'),
         "PASSWORD_QUALITY_SOMETHING"    => __('Something', 'flyvemdm'),
         "PASSWORD_QUALITY_NUMERIC"      => __('Numeric', 'flyvemdm'),
         "PASSWORD_QUALITY_ALPHABETIC"   => __('Alphabetic', 'flyvemdm'),
         "PASSWORD_QUALITY_ALPHANUMERIC" => __('Alphanumeric', 'flyvemdm'),
         "PASSWORD_QUALITY_COMPLEX"      => __('Complex', 'flyvemdm'),
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Complexity of allowed password.',
         'flyvemdm'),
      'default_value'                       => 'PASSWORD_QUALITY_UNSPECIFIED',
      'recommended_value'                   => 'PASSWORD_QUALITY_UNSPECIFIED',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Minimum letters required in password',
         'flyvemdm'),
      'symbol'                              => 'passwordMinLetters',
      'group'                               => 'policies',
      'type'                                => 'int',
      'type_data'                           => [
         "min" => 0,
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('The minimum number of letters required in the password for all admins or a particular one.',
         'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '0',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Minimum lowercase letters required in password',
         'flyvemdm'),
      'symbol'                              => 'passwordMinLowerCase',
      'group'                               => 'policies',
      'type'                                => 'int',
      'type_data'                           => [
         "min" => 0,
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('The minimum number of lowercase letters required in the password for all admins or a particular one.',
         'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '1',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Minimum non-letter characters required in password',
         'flyvemdm'),
      'symbol'                              => 'passwordMinNonLetter',
      'group'                               => 'policies',
      'type'                                => 'int',
      'type_data'                           => [
         "min" => 0,
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('The minimum number of non-letter characters required in the password for all admins or a particular one.',
         'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '0',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Minimum numerical digits required in password',
         'flyvemdm'),
      'symbol'                              => 'passwordMinNumeric',
      'group'                               => 'policies',
      'type'                                => 'int',
      'type_data'                           => [
         "min" => 0,
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('The minimum number of numerical digits required in the password for all admins or a particular one.',
         'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '1',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Minimum symbols required in password',
         'flyvemdm'),
      'symbol'                              => 'passwordMinSymbols',
      'group'                               => 'policies',
      'type'                                => 'int',
      'type_data'                           => [
         "min" => 0,
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('The minimum number of symbols required in the password for all admins or a particular one.',
         'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '0',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Minimum uppercase letters required in password',
         'flyvemdm'),
      'symbol'                              => 'passwordMinUpperCase',
      'group'                               => 'policies',
      'type'                                => 'int',
      'type_data'                           => [
         "min" => 0,
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('The minimum number of uppercase letters required in the password for all admins or a particular one.',
         'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '1',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Maximum failed password attempts for wipe',
         'flyvemdm'),
      'symbol'                              => 'maximumFailedPasswordsForWipe',
      'group'                               => 'policies',
      'type'                                => 'int',
      'type_data'                           => [
         "min" => 0,
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Number of consecutive failed attempts of unlock the device to wipe.',
         'flyvemdm'),
      'default_value'                       => '0',
      'recommended_value'                   => '5',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],

   [
      'name'                                => __('Maximum time to lock (milliseconds)',
         'flyvemdm'),
      'symbol'                              => 'maximumTimeToLock',
      'group'                               => 'policies',
      'type'                                => 'int',
      'type_data'                           => [
         "min" => 0,
      ],
      'unicity'                             => 1,
      'plugin_flyvemdm_policycategories_id' => $category,
      'comment'                             => __('Maximum time to lock the device in milliseconds.',
         'flyvemdm'),
      'default_value'                       => '60000',
      'recommended_value'                   => '60000',
      'is_android_system'                   => '0',
      'android_min_version'                 => '3.0',
      'android_max_version'                 => '0',
      'apple_min_version'                   => '0',
      'apple_max_version'                   => '0',
   ],
];