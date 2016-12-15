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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0.33
 */
class PluginStorkmdmWellknownpath extends CommonDropdown {

   // name of the right in DB
   public static $rightname            = 'storkmdm:wellknownpath';

   /**
    * Localized name of the type
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb=0) {
      return __s('Well known path', 'storkmdm');
   }

   /**
    * Prepare input datas for adding the item
    * @param $input datas used to add the item
    * @return the modified $input array
    */
   public function prepareInputForAdd($input) {
      // unset($input['is_default']);
      return $input;
   }

   /**
    * Prepare input datas for updating the item
    * @param $input datas used to update the item
    * @return the modified $input array
    */
   public function prepareInputForUpdate($input) {
      unset($input['is_default']);
      return $input;
   }

   /**
    * {@inheritDoc}
    * @see CommonDropdown::getSearchOptions()
    */
   public function getSearchOptions() {
      global $CFG_GLPI;

      $tab = array();
      $tab['common']             = __s('Wellknownpath', "storkmdm");

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
      $tab[$i]['name']            = __('comment', 'storkmdm');
      $tab[$i]['massiveaction']   = false;
      $tab[$i]['datatype']        = 'text';

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'is_default';
      $tab[$i]['name']            = __('default', 'storkmdm');
      $tab[$i]['massiveaction']   = false;
      $tab[$i]['datatype']        = 'bool';

      return $tab;
   }

   /**
    * Uninstall process
    */
   public static function uninstall() {
      global $DB;

      foreach (array('DisplayPreference', 'Bookmark') as $itemtype) {
         $item = new $itemtype();
         $item->deleteByCriteria(array('itemtype' => __CLASS__));
      }

      // Remove dropdowns localization
      $dropdownTranslation = new DropdownTranslation();
      $dropdownTranslation->deleteByCriteria(array("itemtype LIKE '" . __CLASS__ . "'"), 1);

      $table = getTableForItemType(__CLASS__);
      $DB->query("DROP TABLE IF EXISTS `$table`");
   }

}