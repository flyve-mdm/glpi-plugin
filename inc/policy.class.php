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
 * @since 0.1.33
 */
class PluginFlyvemdmPolicy extends CommonDBTM {

   /**
    * @var string $rightname name of the right in DB
    */
   static $rightname                   = 'flyvemdm:policy';

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85)
    */
   protected $usenotepad               = false;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi >=0.85)
    */
   protected $usenotepadRights         = false;

   public function getFromDBBySymbol($symbol) {
      return $this->getFromDBByQuery("WHERE `symbol`='$symbol'");
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::post_getFromDB()
    */
   public function post_getFromDB() {
      // Translate some fields
      $this->fields['name'] = __($this->fields['name'], 'flyvemdm');
      $this->fields['comment'] = __($this->fields['comment'], 'flyvemdm');

      // Internationalize type_data field depending on the type of policy
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromPolicy($this);
      $translatedTypeData = $policy->translateData();
      $this->fields['type_data'] = json_encode($translatedTypeData, JSON_UNESCAPED_SLASHES);
   }

   /**
    * Localized name of the type
    * @param $nb  integer  number of item in the type (default 0)
    **/
   static function getTypeName($nb=0) {
      global $LANG;
      return _n('Policy', 'Policies', $nb, "flyvemdm");
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      global $CFG_GLPI;

      $tab = array();
      $tab['common']                 = __s('Policy', "flyvemdm");

      $tab[1]['table']               = self::getTable();
      $tab[1]['field']               = 'name';
      $tab[1]['name']                = __('Name');
      $tab[1]['datatype']            = 'itemlink';
      $tab[1]['massiveaction']       = false;

      $tab[2]['table']               = self::getTable();
      $tab[2]['field']               = 'id';
      $tab[2]['name']                = __('ID');
      $tab[2]['massiveaction']       = false;
      $tab[2]['datatype']            = 'number';

      $tab[3]['table']               = PluginFlyvemdmPolicyCategory::getTable();
      $tab[3]['field']               = 'completename';
      $tab[3]['name']                = __('Policy category', 'flyvemdm');
      $tab[3]['datatype']            = 'dropdown';
      $tab[3]['massiveaction']       = false;

      $tab[4]['table']               = self::getTable();
      $tab[4]['field']               = 'type';
      $tab[4]['name']                = __('Type', 'flyvemdm');
      $tab[4]['datatype']            = 'string';

      $tab[5]['table']               = self::getTable();
      $tab[5]['field']               = 'type_data';
      $tab[5]['name']                = __('Enumeration data', 'flyvemdm');
      $tab[5]['datatype']            = 'string';
      $tab[5]['massiveaction']       = false;

      $tab[6]['table']               = self::getTable();
      $tab[6]['field']               = 'group';
      $tab[6]['name']                = __('Group', 'flyvemdm');
      $tab[6]['datatype']            = 'string';
      $tab[6]['massiveaction']       = false;

      $tab[7]['table']               = self::getTable();
      $tab[7]['field']               = 'default_value';
      $tab[7]['name']                = __('Default value', 'flyvemdm');
      $tab[7]['datatype']            = 'string';
      $tab[7]['massiveaction']       = false;

      $tab[8]['table']               = self::getTable();
      $tab[8]['field']               = 'recommended_value';
      $tab[8]['name']                = __('Recommended value', 'flyvemdm');
      $tab[8]['datatype']            = 'string';
      $tab[8]['massiveaction']       = false;

      return $tab;
   }
}
