<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2016 by the FusionInventory Development Team.
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
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi
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

   const TEMPLATE = 'menu.html';

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
    */
   public function displayMenu() {
      $iconPath = __DIR__ . '/../pics';

      $pluralNumber = Session::getPluralNumber();

      $twig = plugin_flyvemdm_getTemplateEngine();
      $data = [
            'menu'   => [
                  __('General', 'flyvemdm') => [
                        PluginFlyvemdmInvitation::getTypeName($pluralNumber)  => [
                              'link' =>Toolbox::getItemTypeSearchURL(PluginFlyvemdmInvitation::class),
                              'pic'  => PluginFlyvemdmInvitation::getMenuPicture(),
                        ],
                        PluginFlyvemdmAgent::getTypeName($pluralNumber)       => [
                              'link' => Toolbox::getItemTypeSearchURL(PluginFlyvemdmAgent::class),
                              'pic'  => PluginFlyvemdmAgent::getMenuPicture(),
                        ],
                        PluginFlyvemdmFleet::getTypeName($pluralNumber)       => [
                              'link' =>Toolbox::getItemTypeSearchURL(PluginFlyvemdmFleet::class),
                              'pic'  => PluginFlyvemdmFleet::getMenuPicture(),
                        ],
                        PluginFlyvemdmPackage::getTypeName($pluralNumber)     => [
                              'link' => Toolbox::getItemTypeSearchURL(PluginFlyvemdmPackage::class),
                              'pic'  => PluginFlyvemdmPackage::getMenuPicture(),
                        ],
                        PluginFlyvemdmFile::getTypeName($pluralNumber)        => [
                              'link' =>Toolbox::getItemTypeSearchURL(PluginFlyvemdmFile::class),
                              'pic'  => PluginFlyvemdmFile::getMenuPicture(),
                        ],
                  ],
                  /*
                  __('Board', 'flyvemdm') => [
                        PluginFlyvemdmFleet::getTypeName($pluralNumber)       => [
                              'link' => Toolbox::getItemTypeSearchURL(PluginFlyvemdmFleet::class)
                        ]
                  ]
                  */
            ],
      ];
      echo $twig->render('menu.html', $data);
   }

   /**
    *
    *
    *
    */
   public static function getMenuContent() {
      $front_flyvemdm = "/plugins/flyvemdm/front";

      $menu = array();
      $menu['title'] = self::getMenuName();
      $menu['page']  = "$front_flyvemdm/menu.php";

      $itemtypes = array(
            'PluginFlyvemdmAgent'                  => 'agent',
            'PluginFlyvemdmPackage'                => 'package',
            'PluginFlyvemdmFile'                   => 'file',
            'PluginFlyvemdmFleet'                  => 'fleet',
            'PluginFlyvemdmInvitation'             => 'invitation',
      );

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
