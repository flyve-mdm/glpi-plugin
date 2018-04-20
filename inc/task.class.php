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

use GlpiPlugin\Flyvemdm\Exception\PolicyApplicationException;
use GlpiPlugin\Flyvemdm\Exception\TaskPublishPolicyBadFleetException;
use GlpiPlugin\Flyvemdm\Exception\TaskPublishPolicyPolicyNotFoundException;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.32
 */
class PluginFlyvemdmTask extends CommonDBRelation {

   // From CommonDBRelation

   /**
    * @var string $itemtype_1 First itemtype of the relation
    */
   public static $itemtype_1 = PluginFlyvemdmFleet::class;

   /**
    * @var string $items_id_1 DB's column name storing the ID of the first itemtype
    */
   public static $items_id_1 = 'plugin_flyvemdm_fleets_id';

   /**
    * @var string $itemtype_2 Second itemtype of the relation
    */
   public static $itemtype_2 = PluginFlyvemdmPolicy::class;

   /**
    * @var string $items_id_2 DB's column name storing the ID of the second itemtype
    */
   public static $items_id_2 = 'plugin_flyvemdm_policies_id';

   /**
    * @var PluginFlyvemdmPolicyBase Policy
    */
   protected $policy;

   /**
    * @var PluginFlyvemdmFleet $fleet Fleet
    */
   protected $fleet;

   /**
    *
    * @var boolean $silent
    */
   protected $silent;

   /**
    * Gets the tab name for the item
    * @param CommonGLPI $item
    * @param integer $withtemplate
    * @return string the tab name
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $DB;

      if (static::canView()) {
         $pluralNumber = Session::getPluralNumber();
         switch ($item->getType()) {
            case PluginFlyvemdmFleet::class:
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $fleetId = $item->getID();
                  $nb1 = countElementsInTable(static::getTable(),
                     ['plugin_flyvemdm_fleets_id' => $fleetId]);
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
                        PluginFlyvemdmTask::getTable() . '.' . PluginFlyvemdmFleet::getForeignKeyField() => $fleetId,
                     ]
                  ];
                  $result = $DB->request($request)->next();
                  $nb2 = array_pop($result);
               }
               return [
                  1 => self::createTabEntry(PluginFlyvemdmPolicy::getTypeName($pluralNumber), $nb1),
                  2 => self::createTabEntry(__('Tasks statuses', 'flyvemdm'), $nb2),
               ];
               break;

            case PluginFlyvemdmAgent::class:
               $agentId = $item->getID();
               $nb = countElementsInTable(static::getTable(),
                  ['plugin_flyvemdm_agents_id' => $agentId]);
               return self::createTabEntry(PluginFlyvemdmPolicy::getTypeName($pluralNumber), $nb);
               break;
         }
      }
   }

   /**
    * @see CommonDBRelation::addNeededInfoToInput()
    */
   public function addNeededInfoToInput($input) {
      // Set default values for linked item, if needed
      if (!isset($input['itemtype'])) {
         $input['itemtype'] = null;
      }
      if (!isset($input['items_id'])) {
         $input['items_id'] = 0;
      }
      if (!isset($input['value'])) {
         $input['value'] = '';
      }
      if (!isset($input['_silent'])) {
         $this->silent = false;
      } else {
         $this->silent = ($input['_silent'] === true);
      }

      return $input;
   }

   /**
    * @see CommonDBRelation::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      $input = parent::prepareInputForAdd($input);
      if (is_object($input['value'])) {
         // If value contains multiple JSON encoded data the API provides it as an stdClass.
         $value = get_object_vars($input['value']);
         $input['value'] = json_encode($input['value'], JSON_UNESCAPED_SLASHES);
      } else if (is_array($input['value'])) {
         // Newer versions of GLPi 9.1 send an array instead of an object
         $value = $input['value'];
         $input['value'] = json_encode($input['value'], JSON_UNESCAPED_SLASHES);
      } else {
         $value = $input['value'];
      }

      if (!isset($input['plugin_flyvemdm_policies_id'])
         || !isset($input['plugin_flyvemdm_fleets_id'])) {
         Session::addMessageAfterRedirect(__('Fleet and policy must be specified', 'flyvemdm'),
            false, ERROR);
         return false;
      }

      // Check the policy exists
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $this->policy = $policyFactory->createFromDBByID($input['plugin_flyvemdm_policies_id']);
      if (!$this->policy instanceof PluginFlyvemdmPolicyInterface) {
         Session::addMessageAfterRedirect(__('Policy not found', 'flyvemdm'), false, ERROR);
         return false;
      }

      // Check the property of the relation is valid
      if (!$this->policy->integrityCheck($value, $input['itemtype'], $input['items_id'])) {
         if (isset($_SESSION['MESSAGE_AFTER_REDIRECT']) && $_SESSION['MESSAGE_AFTER_REDIRECT'] === null
            || !isset($_SESSION['MESSAGE_AFTER_REDIRECT'])) {
            Session::addMessageAfterRedirect(__('Incorrect value for this policy', 'flyvemdm'),
               false, ERROR);
         }
         return false;
      }

      // Check the fleet exists
      $fleetId = $input['plugin_flyvemdm_fleets_id'];
      $this->fleet = new PluginFlyvemdmFleet();
      if (!$this->fleet->getFromDB($fleetId)) {
         Session::addMessageAfterRedirect(__('Cannot find the target fleet', 'flyvemdm'), false,
            ERROR);
         return false;
      }

      // default fleet check
      if ($this->fleet->getField('is_default')) {
         Session::addMessageAfterRedirect(__('Cannot apply a policy on a not managed fleet',
            'flyvemdm'), false, ERROR);
         return false;
      }

      if (!$this->policy->unicityCheck($value, $input['itemtype'], $input['items_id'],
         $this->fleet)) {
         Session::addMessageAfterRedirect(__('Policy already applied', 'flyvemdm'), false, ERROR);
         return false;
      }

      if (!$this->policy->conflictCheck($value, $input['itemtype'], $input['items_id'],
         $this->fleet)) {
         // Error Message created by the policy
         return false;
      }

      // Check the policy may be applied to the fleet and the value matches requirements
      if (!$this->policy->canApply($input['value'], $input['itemtype'],
         $input['items_id'], $this->fleet)) {
         Session::addMessageAfterRedirect(__('The requirements for this policy are not met',
            'flyvemdm'), false, ERROR);
         return false;
      }

      return $input;
   }

   public function prepareInputForUpdate($input) {
      $input = parent::prepareInputForUpdate($input);
      $value = $input['value'];
      if (is_object($input['value'])) {
         // If value contains multiple JSON encoded data the API provides it as an stdClass.
         $value = get_object_vars($input['value']);
         $input['value'] = json_encode($input['value'], JSON_UNESCAPED_SLASHES);
      } else if (is_array($input['value'])) {
         // Newer versions of GLPi 9.1 send an array instead if an object
         $value = $input['value'];
         $input['value'] = json_encode($input['value'], JSON_UNESCAPED_SLASHES);
      }

      // Take into account the policy being applied if its ID changes
      $policyId = $this->fields['plugin_flyvemdm_policies_id'];
      if (isset($input['plugin_flyvemdm_policies_id'])) {
         $policyId = $input['plugin_flyvemdm_policies_id'];
      }
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $this->policy = $policyFactory->createFromDBByID($policyId);
      if (!$this->policy instanceof PluginFlyvemdmPolicyInterface) {
         Session::addMessageAfterRedirect(__('Policy not found', 'flyvemdm'), false, ERROR);
         return false;
      }

      // Take into account change of item
      $itemtype = $this->fields['itemtype'];
      if (isset($input['itemtype'])) {
         $itemtype = $input['itemtype'];
      }
      $itemId = $this->fields['items_id'];
      if (isset($input['items_id'])) {
         $itemId = $input['items_id'];
      }

      //Check the fleet exists
      $fleetId = $this->fields['plugin_flyvemdm_fleets_id'];
      if (isset($input['plugin_flyvemdm_fleets_id'])) {
         $fleetId = $input['plugin_flyvemdm_fleets_id'];
      }
      $this->fleet = new PluginFlyvemdmFleet();
      if (!$this->fleet->getFromDB($fleetId)) {
         Session::addMessageAfterRedirect(__('Cannot find the target fleet', 'flyvemdm'), false,
            ERROR);
         return false;
      }

      // default fleet check
      if ($this->fleet->getField('is_default')) {
         Session::addMessageAfterRedirect(__('Cannot apply a policy on a not managed fleet',
            'flyvemdm'), false, ERROR);
         return false;
      }

      // Check the policy may be applied to the fleet and the value is matches requirements
      if (!$this->policy->integrityCheck($value, $itemtype, $itemId)) {
         Session::addMessageAfterRedirect(__('Incorrect value for this policy', 'flyvemdm'), false,
            ERROR);
         return false;
      }

      if ($itemId != $this->fields['items_id'] || $policyId != $this->fields['plugin_flyvemdm_policies_id']) {
         // the fleet and the policy are not changing, then check unicity
         if (!$this->policy->unicityCheck($value, $itemtype, $itemId, $this->fleet)) {
            Session::addMessageAfterRedirect(__('Policy already applied', 'flyvemdm'), false,
               ERROR);
            return false;
         }
      }

      if (!$this->policy->canApply($value, $itemtype, $itemId, $this->fleet)) {
         Session::addMessageAfterRedirect(__('The requirements for this policy are not met',
            'flyvemdm'), false, ERROR);
         return false;
      }

      // TODO : What if the fleet changes, or the value changes ?
      if (!$this->policy->pre_apply($value, $itemtype, $itemId, $this->fleet)) {
         Session::addMessageAfterRedirect(__('Failed to apply the policy', 'flyvemdm'), false,
            ERROR);
         return false;
      }

      return $input;
   }

   public function post_addItem() {
      $this->publishPolicy($this->fleet);
      $this->createTaskStatuses($this->fleet);
   }

   public function post_updateItem($history = 1) {
      $this->publishPolicy($this->fleet);
      $this->createTaskStatuses($this->fleet);
   }

   public function pre_deleteItem() {
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $this->policy = $policyFactory->createFromDBByID($this->fields['plugin_flyvemdm_policies_id']);
      if (!$this->policy instanceof PluginFlyvemdmPolicyInterface) {
         Session::addMessageAfterRedirect(__('Policy not found', 'flyvemdm'), false, ERROR);
         return false;
      }
      $this->fleet = new PluginFlyvemdmFleet();
      if (!$this->fleet->getFromDB($this->fields['plugin_flyvemdm_fleets_id'])) {
         Session::addMessageAfterRedirect(__('Fleet not found', 'flyvemdm'), false, ERROR);
         return false;
      }
      return $this->policy->pre_unapply($this->fields['value'], $this->fields['itemtype'],
         $this->fields['items_id'], $this->fleet);
   }

   /**
    * @see CommonDBTM::post_deleteItem()
    */
   public function post_purgeItem() {
      //$this->updateQueue($this->fleet, [$this->policy->getGroup()]);
      $this->unpublishPolicy($this->fleet);
      $this->deleteTaskStatuses();
   }

   /**
    * Deletes the task statuses
    */
   private function deleteTaskStatuses() {
      $taskStatus = new PluginFlyvemdmTaskstatus();
      $taskStatus->deleteByCriteria([
         'plugin_flyvemdm_tasks_id' => $this->getID(),
      ], 1);
   }

   /**
    * MQTT publish a policy applying to the fleet
    *
    * @param PluginFlyvemdmNotifiableInterface $item
    *
    * @throws TaskPublishPolicyBadFleetException
    * @throws TaskPublishPolicyPolicyNotFoundException
    */
   public function publishPolicy(PluginFlyvemdmNotifiableInterface $item) {
      if ($this->silent) {
         return;
      }

      $fleet = $item->getFleet();
      if ($fleet === null || $fleet->getField('is_default') != '0') {
         $notifiableItemtype = get_class($item);
         $exceptionMessage = "Plugin Flyvemdm : no fleet for the notifiable item $notifiableItemtype, or has a default fleet";
         Toolbox::logInFile('php-errors', $exceptionMessage . PHP_EOL);
         throw new TaskPublishPolicyBadFleetException($exceptionMessage);
      }

      $topic = $item->getTopic();

      $policy = new PluginFlyvemdmPolicy();
      $policyFk = $policy::getForeignKeyField();
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $appliedPolicy = $policyFactory->createFromDBByID($this->fields[$policyFk]);
      if ($appliedPolicy === null) {
         $exceptionMessage = "Plugin Flyvemdm : Policy ID " . $this->fields[$policyFk] . " not found while generating MQTT message";
         Toolbox::logInFile('php-errors', $exceptionMessage . PHP_EOL);
         throw new TaskPublishPolicyPolicyNotFoundException($exceptionMessage);
      }

      $policy->getFromDB($this->fields[$policyFk]);
      $policyName = $policy->getField('symbol');
      $taskId = $this->getID();
      $policyMessage = $appliedPolicy->getMqttMessage(
         $this->fields['value'],
         $this->fields['itemtype'],
         $this->fields['items_id']
      );
      $policyMessage['taskId'] = $this->getID();
      $encodedMessage = json_encode($policyMessage, JSON_UNESCAPED_SLASHES);
      $fleet->notify("$topic/Policy/$policyName/Task/$taskId", $encodedMessage, 0, 1);
   }

   /**
    * Creates task status for all agents in the fleet linked to this task
    * @param PluginFlyvemdmNotifiableInterface $item
    */
   public function createTaskStatuses(PluginFlyvemdmNotifiableInterface $item) {
      $fleet = $item->getFleet();
      if ($fleet === null || $fleet->getField('is_default') != '0') {
         return;
      }

      // Initialize a task status for each agent in the fleet
      $fleetId = $fleet->getID();
      $agent = new PluginFlyvemdmAgent();
      $fleetFk = $fleet::getForeignKeyField();
      $rows = $agent->find("`$fleetFk` = '$fleetId'");
      foreach ($rows as $row) {
         $agent = new PluginFlyvemdmAgent();
         if ($agent->getFromDB($row['id'])) {
            $taskStatus = new PluginFlyvemdmTaskstatus();
            $taskStatus->add([
               $agent::getForeignKeyField()  => $row['id'],
               $this::getForeignKeyField()   => $this->getID(),
               'status'                      => 'pending',
            ]);
         }
      }
   }

   /**
    * MQTT unpublish a policy from the fleet
    *
    * @param PluginFlyvemdmNotifiableInterface $item
    */
   public function unpublishPolicy(PluginFlyvemdmNotifiableInterface $item) {
      if ($this->silent) {
         return;
      }

      $fleet = $item->getFleet();
      if ($fleet !== null && $fleet->getField('is_default') == '0') {
         $topic = $item->getTopic();

         $policy = new PluginFlyvemdmPolicy();
         $taskId = $this->getID();
         $policy->getFromDB($this->fields['plugin_flyvemdm_policies_id']);
         $policyName = $policy->getField('symbol');
         $fleet->notify("$topic/Policy/$policyName/Task/$taskId", null, 0, 1);
      }
   }

   /**
    * get the groups of policies where at least one policy applies to a fleet
    *
    * @param PluginFlyvemdmFleet $fleet
    *
    * @return string[] groups of policies
    */
   public function getGroupsOfAppliedPolicies(PluginFlyvemdmFleet $fleet) {
      global $DB;

      $groups = [];
      if ($fleet !== null && $fleet->getField('is_default') == '0') {
         $fleetId = $fleet->getID();
         // publish policies of all groups where at least one policy applies

         // find all groups of applied policies
         $taskTable = PluginFlyvemdmTask::getTable();
         $policyTable = PluginFlyvemdmPolicy::getTable();
         $query = "SELECT DISTINCT `group`
                   FROM `$taskTable` `fp`
                   LEFT JOIN `$policyTable` `p` ON `fp`.`plugin_flyvemdm_policies_id` = `p`.`id`
                   WHERE `fp`.`plugin_flyvemdm_fleets_id` = '$fleetId'";
         $result = $DB->query($query);

         // add groups
         if ($result === false) {
            while ($row = $DB->fetch_assoc($result)) {
               $groups[] = $row['group'];
            }
         }
      }

      return $groups;
   }

   /**
    * Builds a group of policies using the value of an applied policy for a fleet, and the default value of
    * non applied policies of the same group
    *
    * @param string                     $group name of a group of policies
    * @param PluginFlyvemdmFleet        $fleet fleet the group will built for
    *
    * @return array
    */
   public function getGroupOfPolicies($group, $fleet) {
      global $DB;

      // get applied policies and the data for the fleet
      $fleetId = $fleet->getID();
      $taskTable = PluginFlyvemdmTask::getTable();
      $policyTable = PluginFlyvemdmPolicy::getTable();
      $query = "SELECT `t`.* FROM `$taskTable` `t`
                LEFT JOIN `$policyTable` `p` ON `t`.`plugin_flyvemdm_policies_id` = `p`.`id`
                WHERE `t`.`plugin_flyvemdm_fleets_id`='$fleetId' AND `p`.`group` = '$group'";
      $result = $DB->query($query);
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $excludedPolicyIds = [];
      $policiesToApply = [];
      while ($row = $DB->fetch_assoc($result)) {
         $appliedPolicyData = $policyFactory->createFromDBByID($row['plugin_flyvemdm_policies_id']);
         if ($appliedPolicyData === null) {
            Toolbox::logInFile('php-errors',
               "Plugin Flyvemdm : Policy ID " . $row['plugin_flyvemdm_policies_id'] . "not found while generating MQTT message\n");
         } else {
            $policiesToApply[] = [
               'tasks_id'   => $row['id'],
               'policyData' => $appliedPolicyData,
               'policyId'   => $row['plugin_flyvemdm_policies_id'],
               'value'      => $row['value'],
               'itemtype'   => $row['itemtype'],
               'items_id'   => $row['items_id'],
            ];
         }
         $excludedPolicyIds[] = $row['plugin_flyvemdm_policies_id'];
      }

      // get policies and their default data
      $excludedPolicyIds = "'" . implode("', '", $excludedPolicyIds) . "'";
      $policy = new PluginFlyvemdmPolicy();
      $rows = $policy->find("`group` = '$group' AND `id` NOT IN ($excludedPolicyIds) AND `default_value` NOT IN ('')");
      foreach ($rows as $policyId => $row) {
         $defaultPolicyData = $policyFactory->createFromDBByID($policyId);
         if ($defaultPolicyData === null) {
            Toolbox::logInFile('php-errors',
               "Plugin Flyvemdm : Policy ID " . $row['plugin_flyvemdm_policies_id'] . "not found while generating MQTT message\n");
         } else {
            $policiesToApply[] = [
               'tasks_id'   => '0',
               'policyData' => $defaultPolicyData,
               'policyId'   => $policyId,
               'value'      => $row['default_value'],
               'itemtype'   => '',
               'items_id'   => '',
            ];
         }
      }

      return $policiesToApply;
   }

   /**
    *
    * @param array $policiesToApply
    *
    * @return array
    *
    * @throws PolicyApplicationException
    */
   protected function buildMqttMessage($policiesToApply) {
      // generate message of all policies
      $groupToEncode = [];
      foreach ($policiesToApply as $policyToApply) {
         $policy = $policyToApply['policyData'];
         $policyMessage = $policy->getMqttMessage(
            $policyToApply['value'],
            $policyToApply['itemtype'],
            $policyToApply['items_id']
         );
         if ($policyMessage === false) {
            // There is an error while applying the policy, continue with next one for minimal impact
            throw new PolicyApplicationException("Policy '" . $policy->getPolicyData()
                  ->getField('name') . "' with value '" . $policyToApply['value'] . "' was not applied\n");
            continue;
         }
         // Add a task ID to the message if esists
         if ($policyToApply['tasks_id'] != '0') {
            $policyMessage['taskId'] = $policyToApply['tasks_id'];
         }
         $groupToEncode[] = $policyMessage;
      }

      return $groupToEncode;
   }

   /**
    * Removes persisted MQTT messages for groups of policies
    *
    * @param PluginFlyvemdmNotifiableInterface $item a notifiable item
    * @param array $groups array of groups to delete
    */
   public static function cleanupPolicies(PluginFlyvemdmNotifiableInterface $item, $groups = []) {
      $mqttClient = PluginFlyvemdmMqttclient::getInstance();
      $topic = $item->getTopic();
      foreach ($groups as $groupName) {
         $mqttClient->publish("$topic/$groupName", null, 0, 1);
      }
   }

   /**
    * @see CommonDBTM::getSearchOptionsNew()
    * @return array
    */
   public function getSearchOptionsNew() {
      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => __('Task', 'flyvemdm'),
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
         'table'         => 'glpi_plugin_flyvemdm_fleets',
         'field'         => 'id',
         'name'          => __('Fleet ID'),
         'massiveaction' => false,
         'datatype'      => 'dropdown',
      ];

      $tab[] = [
         'id'            => '4',
         'table'         => 'glpi_plugin_flyvemdm_policies',
         'field'         => 'id',
         'name'          => __('Policy ID'),
         'massiveaction' => false,
         'datatype'      => 'dropdown',
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => $this->getTable(),
         'field'         => 'itemtype',
         'name'          => __('itemtype'),
         'massiveaction' => false,
         'nodisplay'     => '1',
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '6',
         'table'         => $this->getTable(),
         'field'         => 'items_id',
         'name'          => __('item'),
         'massiveaction' => false,
         'nodisplay'     => '1',
         'datatype'      => 'integer',
      ];

      $tab[] = [
         'id'            => '7',
         'table'         => 'glpi_plugin_flyvemdm_policies',
         'field'         => 'name',
         'name'          => __('policy_name'),
         'massiveaction' => false,
         'nodisplay'     => '1',
         'datatype'      => 'string',
      ];

      return $tab;
   }

   /**
    * @param CommonGLPI $item
    * @param integer $tabnum
    * @param integer $withtemplate
    * @return bool|void
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch (get_class($item)) {
         case PluginFlyvemdmFleet::class:
            switch ($tabnum) {
               case 1:
                  static::showForFleet($item, $withtemplate);
                  break;
               case 2:
                  static::showTaskStatusesForItem($item, $withtemplate);
                  break;
            }
            break;
         case PluginFlyvemdmAgent::class:
            static::showForFleet($item, $withtemplate);
            break;
      }
   }

   /**
    *
    * @param CommonDBTM $item
    * @param string $withtemplate
    * @return bool
    */
   static function showForFleet(CommonDBTM $item, $withtemplate = '') {
      global $CFG_GLPI;

      if (!$item->canView()) {
         return false;
      }

      $itemId = $item->getID();
      $canedit = Session::haveRightsOr('flyvemdm:fleet', [CREATE, UPDATE, DELETE, PURGE]);
      $rand = mt_rand();

      // Show apply policy form
      $policyDropdown = null;
      if ((empty($withtemplate) || ($withtemplate != 2))
         && $canedit) {
         $policyDropdown = PluginFlyvemdmPolicy::dropdown([
            'display'  => false,
            'name'     => 'plugin_flyvemdm_policies_id',
            'toupdate' => [
               'value_fieldname' => 'value',
               'to_update'       => 'plugin_flyvemdm_policy_value',
               'url'             => $CFG_GLPI['root_doc'] . "/plugins/flyvemdm/ajax/policyValue.php",
            ],
         ]);
      }

      // Get all policy names
      $policy = new PluginFlyvemdmPolicy();
      $policies = $policy->find();

      // Get applied policies
      $task = new PluginFlyvemdmTask();
      $appliedPolicies = $task->find("`plugin_flyvemdm_fleets_id` = '$itemId'");

      // add needed data for display
      $factory = new PluginFlyvemdmPolicyFactory();
      foreach ($appliedPolicies as $id => &$appliedPolicyData) {
         $appliedPolicyData['checkbox'] = Html::getMassiveActionCheckBox(__CLASS__, $id);
         $appliedPolicyData['policyName'] = $policies[$appliedPolicyData['plugin_flyvemdm_policies_id']]['name'];
         $appliedPolicyData['agentName'] = '-';
         $policyItem = $factory->createFromDBByID($appliedPolicyData['plugin_flyvemdm_policies_id']);
         if ($policyItem !== null) {
            $task = new PluginFlyvemdmTask();
            $task->getFromDB($id);
            $appliedPolicyData['value'] = $policyItem->showValue($task);
         }

         if($appliedPolicyData['plugin_flyvemdm_agents_id'] != 0){
            $agent = new PluginFlyvemdmAgent();
            $agent->getFromDB($id);
            $appliedPolicyData['agentName'] = $agent->getName();
         }
      }

      // Template data
      $addFormBegin = "<form name='task_form$rand' id='task_form$rand' method='post'
                       action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
      $addFormEnd = Html::closeForm(false);

      $actions = ['purge' => _x('button', 'Delete permanently')];
      $massiveactionparams = [
         'num_displayed'    => count($appliedPolicies),
         'container'        => 'mass' . __CLASS__ . $rand,
         'specific_actions' => $actions,
         'display'          => false,
      ];
      $massiveActionTop = Html::showMassiveActions($massiveactionparams);
      $massiveactionparams['ontop'] = false;
      $massiveActionBottom = Html::showMassiveActions($massiveactionparams);

      $data = [
         'canEdit'           => $canedit,
         'addForm'           => [
            'begin' => $addFormBegin,
            'end'   => $addFormEnd,
         ],
         'massiveActionForm' => [
            'begin' => Html::getOpenMassiveActionsForm('mass' . __CLASS__ . $rand)
               . $massiveActionTop,
            'end'   => $massiveActionBottom
               . Html::closeForm(false),
         ],
         'checkAll'          => Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand),
         'fleet_policy'      => [
            'policy'                    => $policyDropdown,
            'plugin_flyvemdm_fleets_id' => $itemId,
         ],
         'policies'          => $appliedPolicies,
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('fleet_policy.html.twig', $data);

      Html::closeForm();
   }

   public static function showTaskStatusesForItem(CommonDBTM $item, $withTemplate = '') {
      global $DB;

      if (!$item->canView()) {
         return false;
      }

      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }

      $fleetId = $item->getID();
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
            PluginFlyvemdmTask::getTable() . '.' . PluginFlyvemdmFleet::getForeignKeyField() => $fleetId,
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

   public static function showTaskForAgent(CommonDBTM $item, $withTemplate = '') {
      if (!$item->canView()) {
         return false;
      }

      $itemId = $item->getID();
      $canedit = Session::haveRightsOr('flyvemdm:fleet', [CREATE, UPDATE, DELETE, PURGE]);
      $rand = mt_rand();
   }

   /**
    * Processes
    * @param array $input
    * @return array
    */
   public function preprocessInput(array $input) {
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy = $policyFactory->createFromDBByID($input['plugin_flyvemdm_policies_id']);
      if ($policy) {
         $input = $policy->preprocessFormData($input);
      }

      return $input;
   }
}
