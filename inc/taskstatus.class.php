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

      if (static::canView()) {
         switch ($item->getType()) {
            case PluginFlyvemdmAgent::class:
               if (!$withtemplate) {
                  $nb = 0;
                  $pluralNumber = Session::getPluralNumber();
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $DbUtil = new DbUtils();
                     $nb = $DbUtil->countElementsInTable(
                        static::getTable(),
                        [
                           PluginFlyvemdmAgent::getForeignKeyField() => $item->getID()
                        ]
                     );
                  }
                  return self::createTabEntry(self::getTypeName($pluralNumber), $nb);
               }
               break;

            case PluginFlyvemdmFleet::class:
               if (!$withtemplate) {
                  $nb = 0;
                  $pluralNumber = Session::getPluralNumber();
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $notifiableType = $item->getType();
                     $notifiableId = $item->getID();
                     $request = [
                        'COUNT' => 'c',
                        'FROM' => PluginFlyvemdmTaskstatus::getTable(),
                        'INNER JOIN' => [
                           PluginFlyvemdmTask::getTable() => [
                              'FKEY' => [
                                 PluginFlyvemdmTaskstatus::getTable() => PluginFlyvemdmTask::getForeignKeyField(),
                                 PluginFlyvemdmTask::getTable() => 'id'
                              ]
                           ]
                        ],
                        'WHERE' => [
                           'AND' => [
                              PluginFlyvemdmTask::getTable() . '.itemtype_applied' => $notifiableType,
                              PluginFlyvemdmTask::getTable() . '.items_id_applied' => $notifiableId,
                           ]
                        ]
                     ];
                     $result = $DB->request($request)->next();
                     $nb = $result['c'];
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
         case PluginFlyvemdmAgent::class:
            self::showForAgent($item);
            return true;
            break;

         case PluginFlyvemdmFleet::class:
            self::showForFleet($item);
            break;
      }
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

   public function prepareInputForUpdate($input) {
      if (!isset($input['status'])) {
         return false;
      }

      unset($input[PluginFlyvemdmPolicy::getForeignKeyField()]);
      unset($input[PluginFlyvemdmTask::getForeignKeyField()]);

      $task = new PluginFlyvemdmTask();
      if (!$task->getFromDB($this->fields[PluginFlyvemdmTask::getForeignKeyField()])) {
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

    /**
    * Update status of a task
    *
    * @param PluginFlyvemdmPolicyBase $policy
    * @param string $status
    */
   public function updateStatus(PluginFlyvemdmPolicyBase $policy, $status) {
      $status = $policy->filterStatus($status);

      if ($status === null) {
         return;
      }

      $this->update([
         'id'     => $this->getID(),
         'status' => $status,
      ]);
   }

   /**
    * Gets task statuses for a given agent
    * @param PluginFlyvemdmAgent $agent an agent from which get the policies statuses
    * @return DBIterator
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
         return false;
      }

      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }

      // get items
      $status = new PluginFlyvemdmTaskstatus();
      $items_id = $item->getField('id');
      $itemFk = $item::getForeignKeyField();
      $condition = "`$itemFk` = '$items_id' ";
      $rows = $status->getStatusesForAgent($item);
      $number = count($rows);

      // get the pager
      $pager = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);

      $data = [
         'number'       => $number,
         'pager'        => $pager,
         'taskstatuses' => $rows,
         'start'        => $start,
         'stop'         => $start + $_SESSION['glpilist_limit']
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('agent_taskstatus.html.twig', $data);
   }

   /**
    * Shows task statuses for a fleet
    *
    * @param CommonDBTM $item
    * @param string $withtemplate
    */
   public static function showForFleet(CommonDBTM $item, $withTemplate = '') {
      global $DB;

      if (!$item->canView()) {
         return false;
      }

      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }

      $notifiableType = $item->getType();
      $notifiableId = $item->getID();
      $canedit = Session::haveRightsOr('flyvemdm:fleet', [CREATE, UPDATE, DELETE, PURGE]);
      $rand = mt_rand();

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
      $pager = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);

      $data = [
         'number'       => $number,
         'pager'        => $pager,
         'taskstatuses' => $rows,
         'start'        => $start,
         'stop'         => $start + $_SESSION['glpilist_limit']
      ];
      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('fleet_taskstatus.html.twig', $data);

      Html::closeForm();
   }
}