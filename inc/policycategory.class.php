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
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * @since 0.1.0.33
 */
class PluginFlyvemdmPolicyCategory extends CommonTreeDropdown {

   // name of the right in DB
   public static  $rightname           = 'flyvemdm:policycategory';
   public         $can_be_translated   = true;

   /**
    * Localized name of the type
    * @param $nb  integer  number of item in the type (default 0)
    */
   static function getTypeName($nb=0) {
      return _n('Policy category', 'Policy categories', 2);
   }

   /**
    * @see CommonTreeDropdown::getSearchOptions()
    */
   public function getSearchOptions() {
      global $CFG_GLPI;

      $tab = array();
      $tab['common']             = __s('Policy category', "flyvemdm");

      $tab[1]['table']           = self::getTable();
      $tab[1]['field']           = 'completename';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false;

      $tab[2]['table']           = self::getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false;
      $tab[2]['datatype']        = 'number';

      $tab[3]['table']           = self::getTable();
      $tab[3]['field']           = 'comment';
      $tab[3]['name']            = __('comment', 'flyvemdm');
      $tab[3]['massiveaction']   = false;
      $tab[3]['datatype']        = 'text';

      return $tab;
   }
}
