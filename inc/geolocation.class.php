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
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
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
class PluginFlyvemdmGeolocation extends CommonDBTM {

   // name of the right in DB
   static $rightname = 'flyvemdm:geolocation';

   /**
    * @var bool $dohistory maintain history
    */
   public $dohistory = false;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85)
    */
   protected $usenotepad = true;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi >=0.85)
    */
   protected $usenotepadRights = true;

   public static $types = ['Computer'];

   /**
    * Localized name of the type
    * @param integer $nb number of item in the type (default 0)
    * @return string
    */
   public static function getTypeName($nb = 0) {
      return _n('Geolocation', 'Geolocations', $nb, "flyvemdm");
   }

   /**
    * @see CommonGLPI::getTabNameForItem()
    *
    * @since version 9.1
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $DbUtil = new DbUtils();

      if (static::canView()) {
         switch ($item->getType()) {
            case PluginFlyvemdmAgent::class:
               if (!$withtemplate) {
                  $nb = 0;
                  $computerId = $item->getField('computers_id');
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = $DbUtil->countElementsInTable(static::getTable(),
                        ['computers_id' => $computerId]);
                  }
                  return self::createTabEntry(self::getTypeName(1), $nb);
               }
               break;

            case Computer::class:
               if (!$withtemplate) {
                  $nb = 0;
                  $computerId = $item->getField('id');
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = $DbUtil->countElementsInTable(static::getTable(),
                        ['computers_id' => $computerId]);
                  }
                  return self::createTabEntry(self::getTypeName(1), $nb);
               }
               break;
         }
      }
   }

   /**
    * @param CommonGLPI $item object
    * @param integer $tabnum (default 1)
    * @param integer $withtemplate (default 0)
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch (get_class($item)) {
         case PluginFlyvemdmAgent::class:
            self::showForAgent($item);
            return true;
            break;

         case Computer::class:
            self::showForComputer($item);
            return true;
            break;
      }
   }


   /**
    * @see CommonDBTM::getRights()
    */
   public function getRights($interface = 'central') {
      $rights = parent::getRights();
      //$values = [READ    => __('Read'),
      //           PURGE   => ['short' => __('Purge'),
      //                       'long'  => _x('button', 'Delete permanently')]];

      //$values += ObjectLock::getRightsToAdd( get_class($this), $interface ) ;

      //if ($this->maybeDeleted()) {
      //   $values[DELETE] = ['short' => __('Delete'),
      //                      'long'  => _x('button', 'Put in dustbin')];
      //}
      //if ($this->usenotepad) {
      //   $values[READNOTE] = ['short' => __('Read notes'),
      //                        'long' => __("Read the item's notes")];
      //   $values[UPDATENOTE] = ['short' => __('Update notes'),
      //                          'long' => __("Update the item's notes")];
      //}

      return $rights;
   }

   /**
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      if (!isset($input['computers_id'])) {
         Session::addMessageAfterRedirect(__('associated device is mandatory', 'flyvemdm'));
         return false;
      }
      if (!isset($input['latitude']) || !isset($input['longitude'])) {
         Session::addMessageAfterRedirect(__('latitude and longitude are mandatory', 'flyvemdm'));
         return false;
      }

      if (!$input['latitude'] == 'na' && !$input['longitude'] == 'na') {
         $input['latitude'] = floatval($input['latitude']);
         $input['longitude'] = floatval($input['longitude']);
         $input['computers_id'] = intval($input['computers_id']);

         if ($input['latitude'] < -180 || $input['latitude'] > 180) {
            Session::addMessageAfterRedirect(__('latitude is invalid', 'flyvemdm'));
            return false;
         }
         if ($input['longitude'] < -180 || $input['longitude'] > 180) {
            Session::addMessageAfterRedirect(__('longitude is invalid', 'flyvemdm'));
            return false;
         }
      }

      $computer = new Computer();
      if (!$computer->getFromDB($input['computers_id'])) {
         Session::addMessageAfterRedirect(__('Device not found', 'flyvemdm'));
         return false;
      }

      return $input;
   }

   /**
    * Prepare data before update
    * @param array $input
    * @return array|false if the update fails
    */
   public function prepareInputForUpdate($input) {
      if (!isset($input['latitude']) || !isset($input['longitude'])) {
         Session::addMessageAfterRedirect(__('latitude and longitude are mandatory', 'flyvemdm'));
         return false;
      }

      if (isset($input['computers_id'])) {
         $input['computers_id'] = intval($input['computers_id']);
         $computer = new Computer();
         if (!$computer->getFromDB($input['computers_id'])) {
            Session::addMessageAfterRedirect(__('Device not found', 'flyvemdm'));
            return false;
         }
      }

      if (!$input['latitude'] == 'na' && !$input['longitude'] == 'na') {
         if (isset($input['latitude'])) {
            $input['latitude'] = floatval($input['latitude']);
            if ($input['latitude'] < -180 || $input['latitude'] > 180) {
               Session::addMessageAfterRedirect(__('latitude is invalid', 'flyvemdm'));
               return false;
            }
         }
         if (isset($input['longitude'])) {
            $input['longitude'] = floatval($input['longitude']);
            if ($input['longitude'] < -180 || $input['longitude'] > 180) {
               Session::addMessageAfterRedirect(__('longitude is invalid', 'flyvemdm'));
               return false;
            }
         }
      }

      return $input;
   }

   /**
    * Add default join for search on Geolocation
    * @return string
    */
   public static function addDefaultJoin() {
      $geolocationTable = self::getTable();
      $computerTable = Computer::getTable();
      $join = "LEFT JOIN `$computerTable` AS `c` ON `$geolocationTable`.`computers_id`=`c`.`id` ";

      return $join;
   }

   /**
    * Add default where for search on Geolocation
    * @return string
    */
   public static function addDefaultWhere() {

      $where = '';

      // Entities
      if (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
         // Force complete SQL not summary when access to all entities
         $geolocationTable = self::getTable();
         $computerTable = 'c'; // See self::addDefaultJoin
         $DbUtil = new DbUtils();
         $where .= $DbUtil->getEntitiesRestrictRequest('', "c", "entities_id", '', false, true);
      }

      return $where;
   }

   /**
    * Displays the agents according the datetime
    * @param CommonDBTM $item
    * @return string an html with the geolocation of the agent
    */
   public static function showForAgent(CommonDBTM $item) {
      $beginDate = $endDate = date('Y-m-d');
      $computerId = $item->getField('computers_id');

      $geolocation = new PluginFlyvemdmGeolocation();
      if ($rows = $geolocation->find('computers_id = ' . $computerId, 'date ASC')) {
         $beginDate = date('Y-m-d H:i:s', strtotime(reset($rows)['date']));
         if (count($rows) > 1) {
            $endDate = date('Y-m-d H:i:s', strtotime(end($rows)['date']));
         }
      }

      $randBegin = mt_rand();
      $randEnd = mt_rand();
      $data = [
         'computerId' => $computerId,
         'beginDate'  => $beginDate,
         'endDate'    => $endDate,
         'randBegin'  => $randBegin,
         'randEnd'    => $randEnd,
      ];
      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('computer_geolocation.html.twig', $data);
   }

   /**
    * @return array
    */
   public function getSearchOptionsNew() {
      $tab = parent::getSearchOptionsNew();

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
         'table'         => 'glpi_computers',
         'field'         => 'id',
         'name'          => __('Computer'),
         'datatype'      => 'dropdown',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '4',
         'table'         => $this->getTable(),
         'field'         => 'latitude',
         'name'          => __('latitude'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => $this->getTable(),
         'field'         => 'longitude',
         'name'          => __('longitude'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'            => '6',
         'table'         => $this->getTable(),
         'field'         => 'date',
         'name'          => __('date'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      return $tab;
   }

   /**
    * Deletes the geolocation related with the computer
    * @param CommonDBTM $item
    */
   public function hook_computer_purge(CommonDBTM $item) {
      if ($item instanceof Computer) {
         $geolocation = new self();
         $geolocation->deleteByCriteria(['computers_id' => $item->getID()]);
      }
   }
}
