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

class PluginFlyvemdmFDroidApplication extends CommonDBTM {

   /** @var string $rightname name of the right in DB */
   static $rightname                   = 'flyvemdm:fdroidapplication';

   /** @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85) */
   protected $usenotepad               = true;

   /**
    * get mdm types availables
    */
   public static function getEnumImportStatus() {
      return [
         'no_import'    => __('No import', 'flyvemdm'),
         'to_import'    => __('To import', 'flyvemdm'),
         'imported'     => __('Imported', 'flyvemdm'),
      ];
   }

   /**
    * Return the picture file for the menu
    * @return string
    */
   public static function getMenuPicture() {
      return '';
   }

   /**
    * Returns the name of the type
    * @param integer $count
    * @return string
    */
   static function getTypeName($count = 0) {
      return _n('F-Droid application', 'F-Droid applications', $count);
   }

   /**
    * @see CommonGLPI::getTabNameForItem()
    *
    * @since version 9.1
    * @param CommonGLPI $item
    * @param integer $withtemplate
    * @return array|string
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (static::canView()) {
         switch ($item->getType()) {
            case PluginFlyvemdmFDroidMarket::class:
               if (!$withtemplate) {
                  $nb = 0;
                  $fleetId = $item->getID();
                  $pluralNumber = Session::getPluralNumber();
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $DbUtil = new DbUtils();
                     $nb = $DbUtil->countElementsInTable(static::getTable(), ['plugin_flyvemdm_fleets_id' => $fleetId]);
                  }
                  return self::createTabEntry(self::getTypeName($pluralNumber), $nb);
               }
               break;
         }
      }

      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch (get_class($item)) {
         case PluginFlyvemdmFDroidMarket::class:
            self::showForFDroidMarket($item);
            return true;
            break;
      }

      return false;
   }

   public function getAdditionalLinks() {
      return [];
   }

   function getRights($interface = 'central') {
      $values = [
         READ     => __('Read'),
         UPDATE   => __('Update'),
      ];

      return $values;
   }

   /**
    * get Cron description parameter for this class
    * @param $name string name of the task
    * @return array of string
    **/
   static function cronInfo($name) {
      switch ($name) {
         case 'DownloadApplications' :
            return ['description' => __('download applications from the market')];
      }
   }

   /**
    * Maitnains a local list of all apps available in the repository
    * This algorithm is limited and cannot handle a huge quantity of applications
    * @param CronTask $cronTask
    * @return number
    */
   public static function cronDownloadApplications(CronTask $cronTask) {
      global $DB;

      $cronStatus = 0;

      $cronTask->log('Download applications to import from F-Droid');

      $fDroidApplication = new PluginFlyvemdmFDroidApplication();
      $request = [
         'FROM'  => PluginFlyvemdmFDroidApplication::getTable(),
         'WHERE' => ['import_status' => 'to_import']
      ];
      $package = new PluginFlyvemdmPackage();
      $market = new PluginFlyvemdmFDroidMarket();
      foreach ($DB->request($request) as $row) {
         if ($package->getFromDBByCrit(['name' => $row['name']])) {
            continue;
         }
         $market->getFromDB($row[$market::getForeignKeyField()]);
         $baseUrl = dirname($market->getField('url'));

         $file = GLPI_TMP_DIR . "/" . $row['filename'];
         file_put_contents($file, file_get_contents("$baseUrl/" . $row['filename']));
         $_POST['_file'][0] = $row['filename'];
         if ($package->add($row)) {
            $fDroidApplication->update([
               'id'                         => $row['id'],
               'import_status'              => 'imported',
            ]);
         } else {
            Toolbox::logInFile('php-errors', 'Failed to import an application from a F-Droid like market');
         }
         $cronStatus = 1;
      }

      return $cronStatus;
   }

   /**
    * Imports an application in the database, or updates an existing one
    * @param array $input
    * @return integer|false ID of the imported item or false on error
    */
   public function import($input) {
      if (!isset($input['name'])) {
         return false;
      }

      $id = $this->getFromDBByCrit(['name' => $input['name']]);
      if ($id === false) {
         return $this->add($input);
      }

      $input['id'] = $id;
      $input['is_available'] = '1';
      if ($this->update($input) === false) {
         return false;
      }
      return $id;
   }

   public function prepareInputForUpdate($input) {
      if (isset($input['_skip_checks'])) {
         return $input;
      }

      if (!isset($input['import_status'])) {
         $input = ['import_status' => 'no_import'];
      }

      return $input;
   }

   public function showForm($ID, $options = []) {
      $options['canUpdate'] = (!$this->isNewID($ID)) && ($this->canUpdate() > 0);
      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      $fields = $this->fields;

      $importStatuses = static::getEnumImportStatus();
      $fields['import_status'] = $importStatuses[$fields['import_status']];

      $data = [
         'withTemplate'       => (isset($options['withtemplate']) && $options['withtemplate'] ? '*' : ''),
         'isNewID'            => $this->isNewID($ID),
         'canUpdate'          => $options['canUpdate'],
         'fdroidapplication'  => $fields,
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('fdroidapplication.html.twig', $data);

      if (PluginFlyvemdmPackage::canCreate()) {
         $options['addbuttons'] = [
            'import' => __('Import the package', 'flyvemdm'),
         ];
      }
      $this->showFormButtons($options);
   }

   public static function showForFDroidMarket(CommonDBTM $item, $withtemplate = '') {
      global $CFG_GLPI, $DB;

      if (!$item->canView()) {
         return false;
      }

      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }

      // get items
      $items_id = $item->getField('id');
      $itemFk = $item::getForeignKeyField();
      $condition = "`$itemFk` = '$items_id' ";
      $request = [
         'FROM' => PluginFlyvemdmFDroidApplication::getTable(),
         'WHERE' => [
            PluginFlyvemdmFDroidMarket::getForeignKeyField() => $item->getID(),
         ]
      ];
      $result = $DB->request($request);
      $number = count($result);
      $rows = [];
      foreach ($result as $row) {
         if ($row['name'] == '') {
            $row['name'] = '(' . $row['id'] .')';
         }
         $row['itemUrl'] = PluginFlyvemdmFDroidApplication::getFormURLWithID($row['id']);
         $rows[] = $row;
      }

      // get the pager
      $pager = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);

      $data = [
         'number'             => $number,
         'pager'              => $pager,
         'fdroidapplications' => $rows,
         'start'              => $start,
         'stop'               => $start + $_SESSION['glpilist_limit']
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('fdroidmarket_fdroidapplication.html.twig', $data);
   }

   public function getSearchOptionsNew() {
      $tab = parent::getSearchOptionsNew();

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'alias',
         'name'               => __('Alias', 'flyvemdm'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'version',
         'name'               => __('Version', 'flyvemdm'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'filesize',
         'name'               => __('Size'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'is_imported',
         'name'               => __('Import status', 'flyvemdm'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      return $tab;
   }
}
