<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2017 by the FusionInventory Development Team.
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
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
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
   static $rightname = 'flyvemdm:policy';

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85)
    */
   protected $usenotepad = false;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi >=0.85)
    */
   protected $usenotepadRights = false;

   /**
    * Finds the symbol that matches the argument
    * @param string $symbol
    * @return boolean true if the symbol is found
    */
   public function getFromDBBySymbol($symbol) {
      return $this->getFromDBByQuery("WHERE `symbol`='$symbol'");
   }

   /**
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
    * @param integer $nb number of item in the type (default 0)
    * @return string
    */
   static function getTypeName($nb = 0) {
      return _n('Policy', 'Policies', $nb, "flyvemdm");
   }

   /**
    * @see CommonDBTM::getSearchOptionsNew()
    * @return array
    */
   public function getSearchOptionsNew() {
      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => __('Policy', 'flyvemdm'),
      ];

      $tab[] = [
         'id'            => '1',
         'table'         => $this->getTable(),
         'field'         => 'name',
         'name'          => __('Name'),
         'datatype'      => 'itemlink',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '2',
         'table'         => $this->getTable(),
         'field'         => 'id',
         'name'          => __('ID'),
         'massiveaction' => false,
         'datatype'      => 'number',
      ];

      $tab[] = [
         'id'            => '3',
         'table'         => 'glpi_plugin_flyvemdm_policycategories',
         'field'         => 'completename',
         'name'          => __('Policy category'),
         'datatype'      => 'dropdown',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'       => '4',
         'table'    => $this->getTable(),
         'field'    => 'type',
         'name'     => __('Type'),
         'datatype' => 'string',
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => $this->getTable(),
         'field'         => 'type_data',
         'name'          => __('Enumeration data'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '6',
         'table'         => $this->getTable(),
         'field'         => 'group',
         'name'          => __('Group'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '7',
         'table'         => $this->getTable(),
         'field'         => 'default_value',
         'name'          => __('Default value'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '8',
         'table'         => $this->getTable(),
         'field'         => 'recommended_value',
         'name'          => __('Recommended value'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      return $tab;
   }

}
