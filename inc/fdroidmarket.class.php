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

class PluginFlyvemdmFDroidMarket extends CommonDBTM {

   /** @var string $rightname name of the right in DB */
   static $rightname                   = 'flyvemdm:fdroidmarket';

   /** @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85) */
   protected $usenotepad               = true;

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
      return _n('F-Droid Market', 'F-Droid Markets', $count);
   }

   public function getAdditionalLinks() {
      return [];
   }

   /**
    * Define tabs available for this itemtype
    */
   public function defineTabs($options = []) {
      $tab = [];
      $this->addDefaultFormTab($tab);
      if (!$this->isNewItem()) {
         $this->addStandardTab(PluginFlyvemdmFDroidApplication::class, $tab, $options);
      }
      $this->addStandardTab(Notepad::class, $tab, $options);
      $this->addStandardTab(Log::class, $tab, $options);

      return $tab;
   }

   /**
    * get Cron description parameter for this class
    * @param $name string name of the task
    * @return array of string
    */
   static function cronInfo($name) {
      switch ($name) {
         case 'UpdateRepositories' :
            return ['description' => __('Updates the list of applications')];
      }
   }

   /**
    * Maitnains a local list of all apps available in the repository
    * This algorithm is limited and cannot handle a huge quantity of applications
    * @param CronTask $cronTask
    * @return integer
    */
   public static function cronUpdateRepositories(CronTask $cronTask) {
      global $DB;

      $cronStatus = 0;
      $cronTask->log('Update the list of applications available from F-Droid like repositories');

      $request = [
         'FROM'   => static::getTable(),
      ];
      foreach ($DB->request($request) as $row) {
         $fdroidMarket = new static();
         $fdroidMarket->getFromResultSet($row);
         $volume = $fdroidMarket->updateRepository();
         $cronTask->addVolume($volume);
         $cronStatus = 1;
      }

      return $cronStatus;
   }

   /**
    * Updates the list of applications from a F Droid like repository
    * @return integer
    */
   public function updateRepository() {
      global $DB;

      $volume = 0;
      if (strlen($this->fields['url']) === 0) {
         return 0;
      }
      $xml = file_get_contents($this->fields['url']);
      $fdroid = simplexml_load_string($xml);
      unset($xml);

      if (isset($fdroid->application)) {
         $marketFk = $this::getForeignKeyField();
         $fdroidApplication = new PluginFlyvemdmFDroidApplication();
         $DB->query("UPDATE `" . PluginFlyvemdmFDroidApplication::getTable() . "` SET `is_available` = '0' WHERE `$marketFk`=" . $this->getID());
         foreach ($fdroid->application as $application) {
            $input = [
               'name'         => Toolbox::addslashes_deep($application->name),
               'package_name' => Toolbox::addslashes_deep($application->id),
               'entities_id'  => $this->fields['entities_id'],
               'is_recursive' => $this->fields['is_recursive'],
               $marketFk      => $this->getID(),
               'alias'        => Toolbox::addslashes_deep($application->name),
               'desc'         => Toolbox::addslashes_deep($application->desc),
            ];

            // Find the latest published version
            $highestVersionCode = (string) $application->marketvercode;
            $selectedPackage = null;
            foreach ($application->package as $key => $package) {
               if ((string) $package->versioncode == $highestVersionCode) {
                  $selectedPackage = $package;
                  break;
               }
            }
            if ($selectedPackage !== null) {
               $input['version'] = Toolbox::addslashes_deep((string) $selectedPackage->version);
               $input['version_code'] = Toolbox::addslashes_deep((string) $selectedPackage->versioncode);
               $input['filesize'] = Toolbox::addslashes_deep((string) $selectedPackage->size);
               $input['filename'] = Toolbox::addslashes_deep((string) $selectedPackage->apkname);
               PluginFlyvemdmFDroidApplication::import($input);
            }
         }

         // Delete applications vanished from the repo
         $criteria = [$marketFk => $this->getID(), 'is_available' => '0'];
         if (isCommandline()) {
            $dbUtils = new DbUtils();
            $deleteCount = $dbUtils->countElementsInTable(PluginFlyvemdmFDroidApplication::getTable(), $criteria);
         }
         $fdroidApplication->deleteByCriteria($criteria);
         $volume = count($fdroid->application) + $deleteCount;
      }

      return $volume;
   }

   public function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      $canedit = static::canUpdate();
      $fields = $this->fields;

      $data = [
         'withTemplate'       => (isset($options['withtemplate']) && $options['withtemplate'] ? '*' : ''),
         'isNewID'            => $this->isNewID($ID),
         'fdroidmarket'       => $fields,
         'importButton'       => Html::submit(_x('button', 'Import'), ['name' => 'import']),
         'canImport'          => PluginFlyvemdmPackage::canCreate(),
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('fdroidmarket.html.twig', $data);

      if (!$this->isNewID($ID)) {
         $options['addbuttons'] = [
            'refresh' => _x('button', 'Import'),
         ];
      }
      $this->showFormButtons($options);
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
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => self::getTable(),
         'field'              => 'url',
         'name'               => __('URL', 'flyvemdm'),
         'datatype'           => 'string'
      ];

      return $tab;
   }
}