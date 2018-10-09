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
   public static $itemtype_1 = 'itemtype_applied';

   /**
    * @var string $items_id_1 DB's column name storing the ID of the first itemtype
    */
   public static $items_id_1 = 'items_id_applied';

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
    * @var PluginFlyvemdmNotifiable $notifiable Notifiable
    */
   protected $notifiable;

   /**
    *
    * @var boolean $silent
    */
   protected $silent;

   public static function getTypeName($nb = 0) {
      return _n('Task', 'Tasks', $nb, 'flyvemdm');
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
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
      if ($_SESSION['glpishow_count_on_tabs']) {
         $notifiableType = $item->getType();
         $notifiableId = $item->getID();
         $pluralNumber = Session::getPluralNumber();
         $DbUtil = new DbUtils();
         $nb = $DbUtil->countElementsInTable(
            static::getTable(),
            [
               'itemtype_applied' => $notifiableType,
               'items_id_applied' => $notifiableId,
            ]
         );
      }
      return self::createTabEntry(PluginFlyvemdmTask::getTypeName($pluralNumber), $nb);
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
         || !isset($input['itemtype_applied']) || !isset($input['items_id_applied'])) {
         Session::addMessageAfterRedirect(__('Notifiable and policy must be specified', 'flyvemdm'),
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
      $notifiableType = $input['itemtype_applied'];
      $notifiableId = $input['items_id_applied'];
      if (!is_subclass_of($notifiableType, PluginFlyvemdmNotifiableInterface::class)) {
         Session::addMessageAfterRedirect(__('This is not a notifiable object', 'flyvemdm'), false,
            ERROR);
         return false;
      }
      $this->notifiable = new $notifiableType();
      if (!$this->notifiable->getFromDB($notifiableId)) {
         Session::addMessageAfterRedirect(__('Cannot find the notifiable object', 'flyvemdm'), false,
            ERROR);
         return false;
      }

      // default fleet check (is the notifiable ... notifiable ?)
      if (!$this->notifiable->isNotifiable()) {
         Session::addMessageAfterRedirect(__('Cannot apply a policy on a not managed fleet',
            'flyvemdm'), false, ERROR);
         return false;
      }

      if (!$this->policy->unicityCheck($value, $input['itemtype'], $input['items_id'],
         $this->notifiable)) {
         Session::addMessageAfterRedirect(__('Policy already applied', 'flyvemdm'), false, ERROR);
         return false;
      }

      if (!$this->policy->conflictCheck($value, $input['itemtype'], $input['items_id'],
         $this->notifiable)) {
         // Error Message created by the policy
         return false;
      }

      // Check the policy may be applied to the fleet and the value matches requirements
      if (!$this->policy->canApply($input['value'], $input['itemtype'],
          $input['items_id'], $this->notifiable)) {
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

      //Check the notifiable exists
      $notifiableType = $this->fields['itemtype_applied'];
      $notifiableId = $this->fields['items_id_applied'];
      if (isset($input['items_id_applied'])) {
         $notifiableId = $input['items_id_applied'];
      }
      $this->notifiable = new $notifiableType();
      if (!$this->notifiable->getFromDB($notifiableId)) {
         // TRANS: %1$s is the type of the item on which a policy is applied
         Session::addMessageAfterRedirect(sprintf(__('Cannot find the target %1$s', 'flyvemdm'), $this->notifiable->getTypeName()), false,
            ERROR);
         return false;
      }

      // Check the notifiable can receive tasks
      if (!$this->notifiable->isNotifiable()) {
         // TRANS: %1$s is the type of the item on which one attempds to apply a policy
         Session::addMessageAfterRedirect(sprintf(__('Cannot apply a policy on this %1$s', $this->notifiable->getTypeName()),
            'flyvemdm'), false, ERROR);
         return false;
      }

      // Check the policy may be applied to the notifiable and the value matches requirements
      if (!$this->policy->integrityCheck($value, $itemtype, $itemId)) {
         Session::addMessageAfterRedirect(__('Incorrect value for this policy', 'flyvemdm'), false,
            ERROR);
         return false;
      }

      if ($itemId != $this->fields['items_id'] || $policyId != $this->fields['plugin_flyvemdm_policies_id']) {
         // the fleet and the policy are not changing, then check unicity
         if (!$this->policy->unicityCheck($value, $itemtype, $itemId, $this->notifiable)) {
            Session::addMessageAfterRedirect(__('Policy already applied', 'flyvemdm'), false,
               ERROR);
            return false;
         }
      }

      if (!$this->policy->canApply($value, $itemtype, $itemId, $this->notifiable)) {
         Session::addMessageAfterRedirect(__('The requirements for this policy are not met',
            'flyvemdm'), false, ERROR);
         return false;
      }

      // TODO : What if the fleet changes, or the value changes ?
      if (!$this->policy->pre_apply($value, $itemtype, $itemId, $this->notifiable)) {
         Session::addMessageAfterRedirect(__('Failed to apply the policy', 'flyvemdm'), false,
            ERROR);
         return false;
      }

      return $input;
   }

   public function post_addItem() {
      try {
         $this->publishPolicy($this->notifiable);
      } catch (TaskPublishPolicyPolicyNotFoundException $exception) {
         Session::addMessageAfterRedirect(__("Policy publish action failed.",
            'flyvemdm'), false, INFO, true);
      }
      $this->createTaskStatuses($this->notifiable);
   }

   public function post_updateItem($history = 1) {
      try {
         $this->publishPolicy($this->notifiable);
      } catch (TaskPublishPolicyPolicyNotFoundException $exception) {
         Session::addMessageAfterRedirect(__("Policy publish action failed.",
            'flyvemdm'), false, INFO, true);
      }
      $this->deleteTaskStatuses();
      $this->createTaskStatuses($this->notifiable);
   }

   public function pre_deleteItem() {
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $this->policy = $policyFactory->createFromDBByID($this->fields['plugin_flyvemdm_policies_id']);
      if (!$this->policy instanceof PluginFlyvemdmPolicyInterface) {
         Session::addMessageAfterRedirect(__('Policy not found', 'flyvemdm'), false, ERROR);
         return false;
      }
      $notifiableType = $this->fields['itemtype_applied'];
      $this->notifiable = new $notifiableType();
      if (!$this->notifiable->getFromDB($this->fields['items_id_applied'])) {
         Session::addMessageAfterRedirect(sprintf(__('%1$s not found', 'flyvemdm'), $this->notifiable->getTypeName()), false, ERROR);
         return false;
      }
      return $this->policy->pre_unapply($this->fields['value'], $this->fields['itemtype'],
         $this->fields['items_id'], $this->notifiable);
   }

   /**
    * @see CommonDBTM::post_deleteItem()
    */
   public function post_purgeItem() {
      $this->unpublishPolicy($this->notifiable);
      $this->deleteTaskStatuses();
      $this->policy->post_unapply($this->fields['value'], $this->fields['itemtype'],
         $this->fields['items_id'], $this->notifiable);
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
    * @throws TaskPublishPolicyPolicyNotFoundException
    */
   public function publishPolicy(PluginFlyvemdmNotifiableInterface $item) {
      if ($this->silent) {
         return;
      }

      $policy = new PluginFlyvemdmPolicy();
      $policyFk = $policy::getForeignKeyField();
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $appliedPolicy = $policyFactory->createFromDBByID($this->fields[$policyFk]);
      if ($appliedPolicy === null) {
         $exceptionMessage = "Policy ID " . $this->fields[$policyFk] . " not found while generating MQTT message";
         Toolbox::logInFile('php-errors', 'Plugin Flyvemdm : '. $exceptionMessage . PHP_EOL);
         throw new TaskPublishPolicyPolicyNotFoundException($exceptionMessage);
      }

      $policy->getFromDB($this->fields[$policyFk]);
      $policyName = $policy->getField('symbol');
      $taskId = $this->getID();
      $policyMessage = $appliedPolicy->getBrokerMessage(
         $this->fields['value'],
         $this->fields['itemtype'],
         $this->fields['items_id']
      );
      $policyMessage['taskId'] = $this->getID();
      $message = json_encode($policyMessage, JSON_UNESCAPED_SLASHES);
      $topic = $item->getTopic();
      $recipient = "$topic/Policy/$policyName/Task/$taskId";
      $brokerMessage = new PluginFlyvemdmMqttMessage($message, $recipient, ['retain' => 1]);
      $item->notify(new PluginFlyvemdmBrokerEnvelope($brokerMessage));
   }

   /**
    * Creates task status for all agents in the fleet linked to this task
    * @param PluginFlyvemdmNotifiableInterface $item
    */
   public function createTaskStatuses(PluginFlyvemdmNotifiableInterface $item) {
      if (!$item->isNotifiable()) {
         return;
      }

      // Initialize a task status for each agent in the fleet
      $notifiableId = $item->getID();
      $agents = $item->getAgents($notifiableId);
      foreach ($agents as $agent) {
         $taskStatus = new PluginFlyvemdmTaskstatus();
         $taskStatus->add([
            PluginFlyvemdmAgent::getForeignKeyField() => $agent->getID(),
            $this::getForeignKeyField()               => $this->getID(),
            'status'                                  => 'pending',
         ]);
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

      if (!$item->isNotifiable()) {
         return;
      }

      $topic = $item->getTopic();

      $policy = new PluginFlyvemdmPolicy();
      $taskId = $this->getID();
      $policy->getFromDB($this->fields['plugin_flyvemdm_policies_id']);
      $policyName = $policy->getField('symbol');
      $recipient = "$topic/Policy/$policyName/Task/$taskId";
      $message = null;
      $brokerMessage = new PluginFlyvemdmMqttMessage($message, $recipient, ['retain' => 1]);
      $this->notify(new PluginFlyvemdmBrokerEnvelope($brokerMessage));
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
         $policyMessage = $policy->getBrokerMessage(
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
      return $this->rawSearchOptions();
   }

   public function rawSearchOptions() {
      if (method_exists('CommonDBTM', 'rawSearchOptions')) {
         $tab = parent::rawSearchOptions();
      } else {
         $tab = parent::getSearchOptionsNew();
      }

      $tab[] = [
         'id'            => '3',
         'table'         => $this->getTable(),
         'field'         => 'value',
         'name'          => __('Value'),
         'massiveaction' => false,
         'nosearch'      => true,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => PluginFlyvemdmPolicy::getTable(),
         'field'         => 'id',
         'name'          => __('Policy ID', 'flyvemdm'),
         'massiveaction' => false,
         'datatype'      => 'dropdown',
      ];

      $tab[] = [
         'id'            => '6',
         'table'         => $this->getTable(),
         'field'         => 'itemtype',
         'name'          => __('itemtype'),
         'massiveaction' => false,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '7',
         'table'         => $this->getTable(),
         'field'         => 'items_id',
         'name'          => __('item'),
         'massiveaction' => false,
         'datatype'      => 'integer',
      ];

      $tab[] = [
         'id'            => '8',
         'table'         => PluginFlyvemdmPolicy::getTable(),
         'field'         => 'name',
         'name'          => __('policy name', 'flyvemdm'),
         'massiveaction' => false,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '9',
         'table'         => $this->getTable(),
         'field'         => 'itemtype_applied',
         'name'          => __('applied itemtype', 'flyvemdm'),
         'massiveaction' => false,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '10',
         'table'         => $this->getTable(),
         'field'         => 'items_id_applied',
         'name'          => __('applied ID', 'flyvemdm'),
         'massiveaction' => false,
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
         case PluginFlyvemdmAgent::class:
            static::showForNotifiable($item, $withtemplate);
            break;
      }
   }

   /**
    * Shows tasks (applied policies) on a fleet
    *
    * @param CommonDBTM $item
    * @param string $withtemplate
    */
   static function showForNotifiable(CommonDBTM $item, $withtemplate = '') {
      global $CFG_GLPI;

      if (!$item->canView()) {
         return;
      }

      $itemtype = $item->getType();
      $itemId = $item->getID();
      $canedit = Session::haveRightsOr('flyvemdm:fleet', [CREATE, UPDATE, DELETE, PURGE]);
      $rand = mt_rand();

      // Show apply policy form
      $policyDropdown = null;
      if ((empty($withtemplate) || ($withtemplate != 2))
         && $canedit) {
         $policyDropdown = PluginFlyvemdmPolicy::dropdown([
            'display'             => false,
            'name'                => 'plugin_flyvemdm_policies_id',
            'display_emptychoice' => true,
            'toupdate'            => [
               'value_fieldname'    => 'value',
               'to_update'          => 'plugin_flyvemdm_policy_value',
               'url'                => $CFG_GLPI['root_doc'] . "/plugins/flyvemdm/ajax/policyValue.php",
            ],
         ]);
      }

      // Get all policy names
      $policy = new PluginFlyvemdmPolicy();
      $policies = $policy->find();

      // Get applied policies
      $task = new PluginFlyvemdmTask();
      $appliedPolicies = $task->find("`itemtype_applied` = '$itemtype' AND `items_id_applied` = '$itemId'");

      // add needed data for display
      $factory = new PluginFlyvemdmPolicyFactory();
      foreach ($appliedPolicies as $id => &$appliedPolicyData) {
         $appliedPolicyData['checkbox'] = Html::getMassiveActionCheckBox(__CLASS__, $id);
         $appliedPolicyData['policyName'] = $policies[$appliedPolicyData['plugin_flyvemdm_policies_id']]['name'];
         $policyItem = $factory->createFromDBByID($appliedPolicyData['plugin_flyvemdm_policies_id']);
         if ($policyItem !== null) {
            $task = new PluginFlyvemdmTask();
            $task->getFromDB($id);
            $appliedPolicyData['value'] = $policyItem->showValue($task);
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
         'task'      => [
            'policy'           => $policyDropdown,
            'itemtype_applied' => $itemtype,
            'items_id_applied' => $itemId,
         ],
         'policies'          => $appliedPolicies,
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('fleet_policy.html.twig', $data);

      Html::closeForm();
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
