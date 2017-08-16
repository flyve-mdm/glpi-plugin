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
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0.33
 */
class PluginFlyvemdmWellknownpath extends CommonDropdown {

   // name of the right in DB
   public static $rightname            = 'flyvemdm:wellknownpath';

   /**
    * Localized name of the type
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb=0) {
      return __s('Well known path', 'flyvemdm');
   }

   /**
    * Prepare input datas for adding the item
    * @param array $input data used to add the item
    * @return array the modified $input array
    */
   public function prepareInputForAdd($input) {
      // unset($input['is_default']);
      return $input;
   }

   /**
    * Prepare input datas for updating the item
    * @param array $input data used to update the item
    * @return array the modified $input array
    */
   public function prepareInputForUpdate($input) {
      unset($input['is_default']);
      return $input;
   }

   /**
    * @see CommonDropdown::getSearchOptions()
    */
   public function getSearchOptions() {
      $tab = array();
      $tab['common']             = __s('Well known path', "flyvemdm");

      $i = 1;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'name';
      $tab[$i]['name']            = __('Name');
      $tab[$i]['datatype']        = 'itemlink';
      $tab[$i]['massiveaction']   = false;

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'id';
      $tab[$i]['name']            = __('ID');
      $tab[$i]['massiveaction']   = false;
      $tab[$i]['datatype']        = 'number';

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'comment';
      $tab[$i]['name']            = __('comment', 'flyvemdm');
      $tab[$i]['massiveaction']   = false;
      $tab[$i]['datatype']        = 'text';

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'is_default';
      $tab[$i]['name']            = __('default', 'flyvemdm');
      $tab[$i]['massiveaction']   = false;
      $tab[$i]['datatype']        = 'bool';

      return $tab;
   }
}
