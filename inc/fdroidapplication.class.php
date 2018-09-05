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

   public $dohistory                   = true;

   /** @var string $rightname name of the right in DB */
   static $rightname                   = 'flyvemdm:fdroidapplication';

   /** @var bool $usenotepad enable notepad for the itemtype */
   protected $usenotepad         = true;

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
    * Define tabs available for this itemtype
    */
   public function defineTabs($options = []) {
      $tab = [];
      $this->addDefaultFormTab($tab);
      $this->addStandardTab(Notepad::class, $tab, $options);
      $this->addStandardTab(Log::class, $tab, $options);

      return $tab;
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
                     $nb = $DbUtil->countElementsInTable(static::getTable(), ['plugin_flyvemdm_fdroidmarkets_id' => $fleetId]);
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
    * downloads all applications marked to import
    * This algorithm is limited and cannot handle a huge quantity of applications
    * @param CronTask $cronTask
    * @return number
    */
   public static function cronDownloadApplications(CronTask $cronTask) {
      global $DB;

      $cronStatus = 0;

      $cronTask->log('Download applications to import from F-Droid');

      $request = [
         'FROM'  => PluginFlyvemdmFDroidApplication::getTable(),
         'WHERE' => ['import_status' => 'to_import']
      ];
      $market = new PluginFlyvemdmFDroidMarket();
      foreach ($DB->request($request) as $row) {
         $fDroidApplication = new PluginFlyvemdmFDroidApplication();
         $fDroidApplication->getFromDB($row['id']);
         $fDroidApplication->downloadApplication();
         $cronStatus = 1;
      }

      return $cronStatus;
   }

   /**
    * Downloads an application
    * @return false
    */
   public function downloadApplication() {
      $package = new PluginFlyvemdmPackage();
      $market = new PluginFlyvemdmFDroidMarket();
      if ($package->getFromDBByCrit(['name' => $this->fields['name']])) {
         return false;
      }
      $market->getFromDB($row[$market::getForeignKeyField()]);
      $baseUrl = dirname($market->fields['url']);

      $file = GLPI_TMP_DIR . "/" . $this->fields['filename'];
      file_put_contents($file, file_get_contents("$baseUrl/" . $this->fields['filename']));
      $_POST['_file'][0] = $this->fields['filename'];
      if ($package->add($this->fields)) {
         $this->update([
            'id'                         => $this->fields['id'],
            'import_status'              => 'imported',
         ]);
      } else {
         Toolbox::logInFile('php-errors', 'Failed to import an application from a F-Droid like market');
      }
   }

   /**
    * Imports an application in the database, or updates an existing one
    * @param array $input
    * @return integer|false ID of the imported item or false on error
    */
   public static function import($input) {
      $marketFk = PluginFlyvemdmFDroidMarket::getForeignKeyField();
      if (!isset($input['name']) || !isset($input[$marketFk])) {
         return false;
      }

      $application = new self();
      $application->getFromDBByCrit([
         'name'      => $input['name'],
         $marketFk   => $input[$marketFk],
      ]);
      if ($application->isNewItem()) {
         return $application->add($input);
      }

      $input['id'] = $application->getID();
      $input['is_available'] = '1';
      if ($application->update($input) === false) {
         return false;
      }
      return $application->getID();
   }

   public function prepareInputForUpdate($input) {
      if (isset($input['_skip_checks'])) {
         return $input;
      }

      if (!isset($input['import_status'])) {
         $input['import_status'] = 'no_import';
      }

      if (!isset($input['is_auto_upgradable'])) {
         $input['is_auto_upgradable'] = '1';
      }

      return $input;
   }

   public function showForm($ID, $options = []) {
      $options['canUpdate'] = (!$this->isNewID($ID)) && ($this->canUpdate() > 0);
      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      $canedit = static::canUpdate();
      $fields = $this->fields;

      $importStatuses = static::getEnumImportStatus();
      $fields['import_status'] = $importStatuses[$fields['import_status']];
      $fields['is_auto_upgradable'] = Html::getCheckbox([
         'title'     => __('Download the application updates', 'flyvemdm'),
         'name'      => 'is_auto_upgradable',
         'checked'   => $fields['is_auto_upgradable'] !== '0',
         'readonly'  => ($canedit == '0' ? '1' : '0'),
      ]);

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

      $searchParams = [];
      if (isset($_SESSION['glpisearch'][PluginFlyvemdmFDroidApplication::class])) {
         $searchParams = $_SESSION['glpisearch'][PluginFlyvemdmFDroidApplication::class];
      }
      $searchParams = Search::manageParams(PluginFlyvemdmApplication::class, $searchParams);
      $searchParams['showbookmark'] = false;
      $searchParams['target'] = PluginFlyvemdmFDroidMarket::getFormUrlWithID($item->getID());
      $searchParams['addhidden'] = [
         'id' => $item->getID(),
         PluginFlyvemdmFDroidMarket::getForeignKeyField() => $item->getID(),
      ];
      Search::showGenericSearch(PluginFlyvemdmFDroidApplication::class, $searchParams);

      Search::showList(PluginFlyvemdmFDroidApplication::class, $searchParams);
   }

   public function post_updateItem($history = 1) {
      if (isset($this->oldvalues['version_code'])) {
         if ($this->oldvalues['version_code'] < $this->fields['version_code']) {

         }
      }
   }

   public function getSearchOptionsNew() {
      return $this->rawSearchOptions();
   }

   public function rawSearchOptions() {
      if (method_exists('CommonDBTM', 'rawSearchOptions')) {
         $tab = parent::rawSearchOptions();
      } else {
         $tab = parent::getSearchOptionsNew();
      }

      $tab[] = [
         'id'                 => '2',
         'table'              => self::getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => self::getTable(),
         'field'              => 'alias',
         'name'               => __('Alias', 'flyvemdm'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => self::getTable(),
         'field'              => 'version',
         'name'               => __('Version', 'flyvemdm'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => self::getTable(),
         'field'              => 'filesize',
         'name'               => __('Size'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => self::getTable(),
         'field'              => 'import_status',
         'name'               => __('Import status', 'flyvemdm'),
         'searchtype'         => ['equals', 'notequals'],
         'massiveaction'      => false,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => self::getTable(),
         'field'              => PluginFlyvemdmFDroidMarket::getForeignKeyField(),
         'name'               => __('FDroid market', 'flyvemdm'),
         'massiveaction'      => false,
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => self::getTable(),
         'field'              => 'is_auto_upgradable',
         'name'               => __('Download the application updates', 'flyvemdm'),
         'massiveaction'      => false,
      ];

      return $tab;
   }

   public static function addDefaultJoin($ref_table, $already_link_tables) {
      $join = '';

      $table = PluginFlyvemdmFDroidMarket::getTable();
      $fkTable = PluginFlyvemdmFDroidMarket::getForeignKeyField();
      $join = "LEFT JOIN `$table` ON `$table`.`id`=`$ref_table`.`$fkTable` ";

      return $join;
   }

   public static function addDefaultWhere() {
      $where = '';

      $fkFDroidMarket = PluginFlyvemdmFDroidMarket::getForeignKeyField();
      if (isset($_GET['id'])) {
         $fDfroidMarketId = (int) $_GET['id'];
         $where = " `$fkFDroidMarket` = '$fDfroidMarketId'";
      }
      return $where;
   }

   public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'import_status':
            $elements = self::getEnumImportStatus();
            $output = Dropdown::showFromArray(
               $name,
               $elements,
               [
                  'display' => false,
                  'value' => $values[$field]
               ]
            );
            return $output;
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }
}
