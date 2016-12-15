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
 * @since 0.1.0
 * @deprecated
 */
class PluginStorkmdmMenu extends CommonGLPI {
   static $rightname = 'plugin_stork_config';

   public static function getMenuName() {
      return __('Stork MDM');
   }

   /**
    * Localized name of the type
    *
    * @param $nb  integer  number of item in the type (default 0)
   **/
   public static function getTypeName($nb=0) {
      global $LANG;
      return _n('Menu', 'Menus', $nb, "storkmdm");
   }

   public static function displayMenu() {
      global $CFG_GLPI;

      $iconPath = $CFG_GLPI['root_doc']."/plugins/storkmdm/pics";

      echo "<ul class='storkmdm_menu'>";

      echo "<li><a href='agent.php'>";
      //echo "<img src='$iconPath/agent.png'>";
      echo "".__('Agent', 'storkmdm')."</a></li>";

      echo "<li><a href='fleet.php'>";
      //echo "<img src='$iconPath/fleet.png'>";
      echo "".__('Fleet', 'storkmdm')."</a></li>";

      echo "<li><a href='package.php'>";
      //echo "<img src='$iconPath/package.png'>";
      echo "".__('Package', 'storkmdm')."</a></li>";

      echo "</ul>";
      echo "<span class='clear'></span>";
   }

   /**
    *
    *
    *
    */
   public static function getMenuContent() {

      $front_storkmdm = "/plugins/storkmdm/front";

      $menu = array();
      $menu['title'] = self::getMenuName();
      $menu['page']  = "$front_storkmdm/menu.php";
      if (true /*| Session::haveRight('plugin_storkmdm_config',
                                   PluginstorkmdmConfig::RIGHT_EDIT_CONFIGURATION)*/) {
         $menu['links']['config']  = "$front_storkmdm/config.form.php";
      }

      $itemtypes = array(
            'PluginStorkmdmAgent'                  => 'agent',
            'PluginStorkmdmPackage'                => 'package',
            'PluginStorkmdmFleet'                  => 'fleet',
            );

      foreach ($itemtypes as $itemtype => $option) {
         $menu['options'][$option]['title']           = $itemtype::getTypeName(2);
         $menu['options'][$option]['page']            = $itemtype::getSearchURL(false);
         $menu['options'][$option]['links']['search'] = $itemtype::getSearchURL(false);
         if ($itemtype::canCreate()) {
            $menu['options'][$option]['links']['add'] = $itemtype::getFormURL(false);

//             if (Session::haveRight('plugin_storkmdm_config',
//                                    PluginStorkmdmConfig::RIGHT_EDIT_CONFIGURATION)) {
//                $menu['options'][$option]['links']['config'] = "$front_storkmdm/config.form.php";
//             }
         }
      }
      return $menu;
   }
}
