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

/**
 * @since 0.1.0
 */
class PluginFlyvemdmMenu extends CommonGLPI {
   static $rightname = 'plugin_flyve_config';

   const TEMPLATE = 'menu.html.twig';

   /**
    * Displays the menu name
    * @return string the menu name
    */
   public static function getMenuName() {
      return __('Flyve MDM');
   }

   /**
    * Can the user globally view an item ?
    * @return boolean
    */
   static function canView() {
      $can_display = false;
      $profile     = new PluginFlyvemdmProfile();

      foreach ($profile->getAllRights() as $right) {
         if (Session::haveRight($right['field'], READ)) {
            $can_display = true;
            break;
         }
      }

      return $can_display;
   }

   /**
    * Can the user globally create an item ?
    * @return boolean
    */
   static function canCreate() {
      return false;
   }

   /**
    * Display the menu
    * @param string $type type of menu : dashboard to show graphs, anything else to show only the dropdown menu
    */
   public function displayMenu($type = 'dashboard') {
      $pluralNumber = Session::getPluralNumber();

      $config = new PluginFlyvemdmConfig();
      $isGlpiConfigured = $config->isGlpiConfigured();

      $twig = plugin_flyvemdm_getTemplateEngine();
      $data = [
         'configuration' => $isGlpiConfigured,
         'menuType' => $type,
         'menu'   => [
            __('General', 'flyvemdm') => [
               PluginFlyvemdmInvitation::getTypeName($pluralNumber)   => [
                        'link' =>Toolbox::getItemTypeSearchURL(PluginFlyvemdmInvitation::class),
                        'pic'  => PluginFlyvemdmInvitation::getMenuPicture(),
                  ],
               PluginFlyvemdmAgent::getTypeName($pluralNumber)        => [
                        'link' => Toolbox::getItemTypeSearchURL(PluginFlyvemdmAgent::class),
                        'pic'  => PluginFlyvemdmAgent::getMenuPicture(),
                  ],
               PluginFlyvemdmFleet::getTypeName($pluralNumber) => [
                        'link' =>Toolbox::getItemTypeSearchURL(PluginFlyvemdmFleet::class),
                        'pic'  => PluginFlyvemdmFleet::getMenuPicture(),
                  ],
               PluginFlyvemdmPackage::getTypeName($pluralNumber)      => [
                        'link' => Toolbox::getItemTypeSearchURL(PluginFlyvemdmPackage::class),
                        'pic'  => PluginFlyvemdmPackage::getMenuPicture(),
                  ],
               PluginFlyvemdmFile::getTypeName($pluralNumber)         => [
                        'link' =>Toolbox::getItemTypeSearchURL(PluginFlyvemdmFile::class),
                        'pic'  => PluginFlyvemdmFile::getMenuPicture(),
                  ],
            ],
            __('Configuration', 'flyvemdm') => [
               __('General')       => [
                  'link' => Toolbox::getItemTypeFormURL(PluginFlyvemdmConfig::class) . '?forcetab='.PluginFlyvemdmConfig::class.'$2',
               ],
               __('Message queue', 'flyvemdm') => [
                  'link' => Toolbox::getItemTypeFormURL(PluginFlyvemdmConfig::class) . '?forcetab='.PluginFlyvemdmConfig::class.'$3',
               ],
               __('Debug', 'flyvemdm') => [
                  'link' => Toolbox::getItemTypeFormURL(PluginFlyvemdmConfig::class) . '?forcetab='.PluginFlyvemdmConfig::class.'$4',
               ]
            ]
         ],
      ];
      echo $twig->render('menu.html.twig', $data);
   }

   /**
    * Gets the menu content
    * @return array the menu content
    */
   public static function getMenuContent() {
      $front_flyvemdm = "/plugins/flyvemdm/front";

      $menu = [];
      $menu['title'] = self::getMenuName();
      $menu['page']  = "$front_flyvemdm/menu.php";

      $itemtypes = [
         PluginFlyvemdmAgent::class        => 'agent',
         PluginFlyvemdmPackage::class      => 'package',
         PluginFlyvemdmFile::class         => 'file',
         PluginFlyvemdmFleet::class        => 'fleet',
         PluginFlyvemdmInvitation::class   => 'invitation',
      ];

      $pluralNumber = Session::getPluralNumber();
      foreach ($itemtypes as $itemtype => $option) {
         $menu['options'][$option]['title']           = $itemtype::getTypeName($pluralNumber);
         $menu['options'][$option]['page']            = $itemtype::getSearchURL(false);
         $menu['options'][$option]['links']['search'] = $itemtype::getSearchURL(false);
         if ($itemtype::canCreate()) {
            $menu['options'][$option]['links']['add'] = $itemtype::getFormURL(false);
         }
      }
      return $menu;
   }
}
