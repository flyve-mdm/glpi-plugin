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

class PluginFlyvemdmTaskstatus extends CommonDBTM {

   // name of the right in DB
   public static $rightname = 'flyvemdm:taskstatus';

   /**
    * Localized name of the type
    * @param $nb  integer  number of item in the type (default 0)
    * @return string
    */
   public static function getTypeName($nb = 0) {
      return __s('Task status', 'flyvemdm');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $DB;

      if (!static::canView()) {
         return '';
      }

      if (!($item instanceof PluginFlyvemdmNotifiableInterface)) {
         return '';
      }
      if ($withtemplate) {
         return '';
      }

      $nb = 0;
      $pluralNumber = Session::getPluralNumber();
      switch ($item->getType()) {
         case PluginFlyvemdmAgent::class:
            if ($_SESSION['glpishow_count_on_tabs']) {
               $DbUtil = new DbUtils();
               $nb = $DbUtil->countElementsInTable(
                  static::getTable(),
                  [
                     PluginFlyvemdmAgent::getForeignKeyField() => $item->getID()
                  ]
               );
            }
            break;

         case PluginFlyvemdmFleet::class:
            if ($_SESSION['glpishow_count_on_tabs']) {
               $notifiableType = $item->getType();
               $notifiableId = $item->getID();
               $tableTaskStatus = PluginFlyvemdmTaskstatus::getTable();
               $tableTask = PluginFlyvemdmTask::getTable();
               $request = [
                  'COUNT'      => 'c',
                  'FROM'       => $tableTaskStatus,
                  'INNER JOIN' => [
                     $tableTask => [
                        'FKEY' => [
                           $tableTaskStatus => PluginFlyvemdmTask::getForeignKeyField(),
                           $tableTask       => 'id'
                        ]
                     ]
                  ],
                  'WHERE'      => [
                     'AND' => [
                        $tableTask . '.itemtype_applied' => $notifiableType,
                        $tableTask . '.items_id_applied' => $notifiableId,
                     ]
                  ]
               ];
               $result = $DB->request($request)->next();
               $nb = $result['c'];
            }
            break;
      }
      return self::createTabEntry(self::getTypeName($pluralNumber), $nb);
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch (get_class($item)) {
         case PluginFlyvemdmAgent::class:
            self::showForAgent($item);
            return true;
            break;

         case PluginFlyvemdmFleet::class:
            self::showForFleet($item);
            return true;
            break;
      }
      return false;
   }

   public function prepareInputForAdd($input) {
      if (!isset($input['status'])) {
         return false;
      }

      if (!isset($input[PluginFlyvemdmTask::getForeignKeyField()])) {
         return false;
      }
      $task = new PluginFlyvemdmTask();
      if (!$task->getFromDB($input[PluginFlyvemdmTask::getForeignKeyField()])) {
         return false;
      }

      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($task->getField(PluginFlyvemdmPolicy::getForeignKeyField()));

      $input['status'] = $policy->filterStatus($input['status']);
      if ($input['status'] === null) {
         return false;
      }

      return $input;
   }

   public function canUpdateItem() {
      // Check the active profile
      $config = Config::getConfigurationValues('flyvemdm', ['agent_profiles_id']);
      if ($_SESSION['glpiactiveprofile']['id'] != $config['agent_profiles_id']) {
         return parent::canUpdateItem();
      }

      // Check the task matches the agent itself
      $agent = new PluginFlyvemdmAgent();
      if (!$agent->getFromDB($this->fields[PluginFlyvemdmAgent::getForeignKeyField()])) {
         return false;
      }
      if ($agent->getField(User::getForeignKeyField()) != Session::getLoginUserID()) {
         return false;
      }

      return parent::canUpdateItem();
   }

   public function prepareInputForUpdate($input) {
      if (isAPI() && $this->canUpdateItem()) {
         if (!isset($input['_message'])) {
            return false;
         }
         if (!isset($input['_topic'])) {
            return false;
         }
         $feedback = json_decode($input['_message'], true);
         $input['status'] = $feedback['status'];
         $mqttPath = explode('/', $input['_topic'], 4);
         if (!isset($mqttPath[3]) || !PluginFlyvemdmCommon::startsWith($mqttPath[3],
               "Status/Task")) {
            return false;
         }
      }

      if (!isset($input['status'])) {
         return false;
      }

      $taskIdFk = PluginFlyvemdmTask::getForeignKeyField();
      $policyIdFK = PluginFlyvemdmPolicy::getForeignKeyField();
      unset($input[$policyIdFK]);
      unset($input[$taskIdFk]);

      $task = new PluginFlyvemdmTask();
      if (!$task->getFromDB($this->fields[$taskIdFk])) {
         return false;
      }

      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($task->getField($policyIdFK));

      $input['status'] = $policy->filterStatus($input['status']);
      if ($input['status'] === null) {
         return false;
      }

      return $input;
   }

   public function post_updateItem($history = 1) {
      if(isset($this->input['topic']) && isset($this->input['message'])) {
         $agent = new PluginFlyvemdmAgent();
         $agent->updateLastContact($this->input['topic'], $this->input['message']);
      }
   }

   /**
    * Gets task statuses for a given agent
    * @param PluginFlyvemdmAgent $agent an agent from which get the policies statuses
    * @return DBmysqlIterator
    */
   public function getStatusesForAgent(PluginFlyvemdmAgent $agent) {
      global $DB;

      $request = [
         'FIELDS' => [
            PluginFlyvemdmTaskstatus::getTable() => '*',
            PluginFlyvemdmPolicy::getTable() => 'name',
         ],
         'FROM' => [
            PluginFlyvemdmTaskstatus::getTable(),
         ],
         'INNER JOIN' => [
            PluginFlyvemdmTask::getTable() => [
               'FKEY' => [
                  PluginFlyvemdmTask::getTable() => 'id',
                  PluginFlyvemdmTaskstatus::getTable() => PluginFlyvemdmTask::getForeignKeyField()
               ]
            ],
            PluginFlyvemdmPolicy::getTable() => [
               'FKEY' => [
                  PluginFlyvemdmTask::getTable() => PluginFlyvemdmPolicy::getForeignKeyField(),
                  PluginFlyvemdmPolicy::getTable() => 'id'
               ]
            ]
         ],
         'WHERE' =>  [
            PluginFlyvemdmAgent::getForeignKeyField() => $agent->getID(),
         ]
      ];

      return $DB->request($request);
   }

   public static function showForAgent(PluginFlyvemdmAgent $item) {
      if (!PluginFlyvemdmAgent::canView()) {
         return;
      }

      $start = isset($_GET["start"]) ? intval($_GET["start"]) : 0;

      // get items
      $status = new PluginFlyvemdmTaskstatus();
      $rows = $status->getStatusesForAgent($item);
      $number = count($rows);

      // get the pager
      $pager_top = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);
      $pager_bottom = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);

      $data = [
         'number'       => $number,
         'pager_top'    => $pager_top,
         'pager_bottom' => $pager_bottom,
         'taskstatuses' => $rows,
         'start'        => $start,
         'stop'         => $start + $_SESSION['glpilist_limit'],
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('agent_taskstatus.html.twig', $data);
   }

   /**
    * Shows task statuses for a fleet
    *
    * @param CommonDBTM $item
    * @param string $withTemplate
    */
   public static function showForFleet(CommonDBTM $item, $withTemplate = '') {
      global $DB;

      if (!$item->canView()) {
         return;
      }

      $start = isset($_GET["start"]) ? intval($_GET["start"]) : 0;

      $notifiableType = $item->getType();
      $notifiableId = $item->getID();

      $request = [
         'COUNT' => 'c',
         'FIELDS' => [
            PluginFlyvemdmTask::getTable() => PluginFlyvemdmPolicy::getForeignKeyField(),
            PluginFlyvemdmPolicy::getTable() => 'name',
            PluginFlyvemdmTaskstatus::getTable() => 'status',
         ],
         'FROM' => PluginFlyvemdmTaskstatus::getTable(),
         'INNER JOIN' => [
            PluginFlyvemdmTask::getTable() => [
               'FKEY' => [
                  PluginFlyvemdmTaskstatus::getTable() => PluginFlyvemdmTask::getForeignKeyField(),
                  PluginFlyvemdmTask::getTable() => 'id'
               ]
            ],
            PluginFlyvemdmPolicy::getTable() => [
               'FKEY' => [
                  PluginFlyvemdmTask::getTable() => PluginFlyvemdmPolicy::getForeignKeyField(),
                  PluginFlyvemdmPolicy::getTable() => 'id'
               ]
            ]
         ],
         'GROUPBY' => [
            PluginFlyvemdmPolicy::getTable() . '.' . 'id',
            PluginFlyvemdmTaskstatus::getTable() . '.' . 'status'
         ],
         'WHERE' => [
            'AND' => [
               PluginFlyvemdmTask::getTable() . '.itemtype_applied' => $notifiableType,
               PluginFlyvemdmTask::getTable() . '.items_id_applied' => $notifiableId,
            ]
         ],
         'ORDER' => [
            PluginFlyvemdmPolicy::getTable() .'.name ASC'
         ]
      ];
      $rows = $DB->request($request);
      $number = $rows->count();

      // get the pager
      $pager_top = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);
      $pager_bottom = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);

      $data = [
         'number'       => $number,
         'pager_top'    => $pager_top,
         'pager_bottom' => $pager_bottom,
         'taskstatuses' => $rows,
         'start'        => $start,
         'stop'         => $start + $_SESSION['glpilist_limit'],
      ];
      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('fleet_taskstatus.html.twig', $data);

      Html::closeForm();
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
         'id'            => '2',
         'table'         => $this->getTable(),
         'field'         => 'id',
         'name'          => __('ID'),
         'massiveaction' => false,
         'datatype'      => 'number',
      ];

      $tab[] = [
         'id'            => '3',
         'table'         => $this->getTable(),
         'field'         => 'date_creation',
         'name'          => __('Creation date'),
         'datatype'      => 'datetime',
         'massiveaction' => false
      ];

      $tab[] = [
         'id'            => '4',
         'table'         => $this->getTable(),
         'field'         => 'date_mod',
         'name'          => __('Last update'),
         'datatype'      => 'datetime',
         'massiveaction' => false
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => $this->getTable(),
         'field'         => 'plugin_flyvemdm_agents_id',
         'name'          => PluginFlyvemdmAgent::getTypeName(1),
         'datatype'      => 'itemlink',
         'massiveaction' => false
      ];

      $tab[] = [
         'id'            => '6',
         'table'         => $this->getTable(),
         'field'         => 'plugin_flyvemdm_tasks_id',
         'name'          => PluginFlyvemdmTask::getTypeName(1),
         'datatype'      => 'itemlink',
         'massiveaction' => false
      ];

      $tab[] = [
         'id'            => '7',
         'table'         => $this->getTable(),
         'field'         => 'status',
         'name'          => __('Status', 'flyvemdm'),
         'datatype'      => 'string',
         'massiveaction' => false
      ];

      return $tab;
   }
}
