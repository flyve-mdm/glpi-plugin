<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2016 by the FusionInventory Development Team.
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
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.32
 */
class PluginFlyvemdmFleet_Policy extends CommonDBRelation {

   // From CommonDBRelation
   /**
    * @var string $itemtype_1 First itemtype of the relation
    */
   public static $itemtype_1 = 'PluginFlyvemdmFleet';

   /**
    * @var string $items_id_1 DB's column name storing the ID of the first itemtype
    */
   public static $items_id_1 = 'plugin_flyvemdm_fleets_id';

   /**
    * @var string $itemtype_2 Second itemtype of the relation
    */
   public static $itemtype_2 = 'PluginFlyvemdmPolicy';

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

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (static::canView()) {
         switch ($item->getType()) {
            case PluginFlyvemdmFleet::class:
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = 0;
                  $fleetId = $item->getID();
                  $pluralNumber = Session::getPluralNumber();
                  $nb = countElementsInTable(static::getTable(), ['plugin_flyvemdm_fleets_id' => $fleetId]);
               }
               return self::createTabEntry(PluginFlyvemdmPolicy::getTypeName($pluralNumber), $nb);
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
         // Newer versions of GLPi 9.1 send an array instead if an object
         $value = $input['value'];
         $input['value'] = json_encode($input['value'], JSON_UNESCAPED_SLASHES);
      } else {
         $value = $input['value'];
      }

      if (!isset($input['plugin_flyvemdm_policies_id'])
            || !isset($input['plugin_flyvemdm_fleets_id'])) {
               Session::addMessageAfterRedirect(__('Fleet and policy must be specified', 'flyvemdm'), false, ERROR);
         return false;
      }

      // Check the policy exists
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $this->policy  = $policyFactory->createFromDBByID($input['plugin_flyvemdm_policies_id']);
      if (!$this->policy instanceof PluginFlyvemdmPolicyInterface) {
         Session::addMessageAfterRedirect(__('Policy not found', 'flyvemdm'), false, ERROR);
         return false;
      }

      // Check the property of the relation is valid
      if (!$this->policy->integrityCheck($value, $input['itemtype'], $input['items_id'])) {
         if (isset($_SESSION['MESSAGE_AFTER_REDIRECT']) && $_SESSION['MESSAGE_AFTER_REDIRECT'] === null
               || !isset($_SESSION['MESSAGE_AFTER_REDIRECT'])) {
            Session::addMessageAfterRedirect(__('Incorrect value for this policy', 'flyvemdm'), false, ERROR);
         }
         return false;
      }

      // Check the fleet exists
      $fleetId = $input['plugin_flyvemdm_fleets_id'];
      $this->fleet = new PluginFlyvemdmFleet();
      if (!$this->fleet->getFromDB($fleetId)) {
         Session::addMessageAfterRedirect(__('Cannot find the target fleet', 'flyvemdm'), false, ERROR);
         return false;
      }

      // default fleet check
      if ($this->fleet->getField('is_default')) {
         Session::addMessageAfterRedirect(__('Cannot apply a policy on a not managed fleet', 'flyvemdm'), false, ERROR);
         return false;
      }

      if (!$this->policy->unicityCheck($value, $input['itemtype'], $input['items_id'], $this->fleet)) {
         Session::addMessageAfterRedirect(__('Policy already applied', 'flyvemdm'), false, ERROR);
         return false;
      }

      if (!$this->policy->conflictCheck($value, $input['itemtype'], $input['items_id'], $this->fleet)) {
         // Error Message created by the policy
         return false;
      }

      // Check the policy may be applied to the fleet and the value matches requirements
      if (!$this->policy->canApply($this->fleet, $input['value'], $input['itemtype'], $input['items_id'])) {
         Session::addMessageAfterRedirect(__('The requirements for this policy are not met', 'flyvemdm'), false, ERROR);
         return false;
      }

      if (! $this->policy->apply($this->fleet, $input['value'], $input['itemtype'], $input['items_id'])) {
         Session::addMessageAfterRedirect(__('Failed to apply the policy', 'flyvemdm'), false, ERROR);
         return false;
      }

      return $input;
   }

   /**
    * @see CommonDBRelation::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      $input = parent::prepareInputForUpdate($input);
      if (is_object($input['value'])) {
         // If value contains multiple JSON encoded data the API provides it as an stdClass.
         $value = get_object_vars($input['value']);
         $input['value'] = json_encode($input['value'], JSON_UNESCAPED_SLASHES);
      } else if (is_array($input['value'])) {
         // Newer versions of GLPi 9.1 send an array instead if an object
         $value = $input['value'];
         $input['value'] = json_encode($input['value'], JSON_UNESCAPED_SLASHES);
      } else {
         $value = $input['value'];
      }

      // Take into account the policy being applied if its ID changes
      if (isset($input['plugin_flyvemdm_policies_id'])) {
         $policyId = $input['plugin_flyvemdm_policies_id'];
      } else {
         $policyId = $this->fields['plugin_flyvemdm_policies_id'];
      }
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $this->policy = $policyFactory->createFromDBByID($policyId);
      if (!$this->policy instanceof PluginFlyvemdmPolicyInterface) {
         Session::addMessageAfterRedirect(__('Policy not found', 'flyvemdm'), false, ERROR);
         return false;
      }

      // Take into account change of item
      if (isset($input['itemtype'])) {
         $itemtype = $input['itemtype'];
      } else {
         $itemtype = $this->fields['itemtype'];
      }
      if (isset($input['items_id'])) {
         $itemId = $input['items_id'];
      } else {
         $itemId = $this->fields['items_id'];
      }

      //Check the fleet exists
      if (isset($input['plugin_flyvemdm_fleets_id'])) {
         $fleetId = $input['plugin_flyvemdm_fleets_id'];
      } else {
         $fleetId = $this->fields['plugin_flyvemdm_fleets_id'];
      }
      $this->fleet = new PluginFlyvemdmFleet();
      if (!$this->fleet->getFromDB($fleetId)) {
         Session::addMessageAfterRedirect(__('Cannot find the target fleet', 'flyvemdm'), false, ERROR);
         return false;
      }

      // default fleet check
      if ($this->fleet->getField('is_default')) {
         Session::addMessageAfterRedirect(__('Cannot apply a policy on a not managed fleet', 'flyvemdm'), false, ERROR);
         return false;
      }

      // Check the policy may be applied to the fleet and the value is matches requirements
      if (!$this->policy->integrityCheck($value, $itemtype, $itemId)) {
         Session::addMessageAfterRedirect(__('Incorrect value for this policy', 'flyvemdm'), false, ERROR);
         return false;
      }

      if ($itemId != $this->fields['items_id'] || $policyId != $this->fields['plugin_flyvemdm_policies_id']) {
         // the fleet and the policy are not changing, then check unicity
         if (!$this->policy->unicityCheck($this->fleet)) {
            Session::addMessageAfterRedirect(__('Policy already applied', 'flyvemdm'), false, ERROR);
            return false;
         }
      }

      if (!$this->policy->canApply($this->fleet, $value, $itemtype, $itemId)) {
         Session::addMessageAfterRedirect(__('The requirements for this policy are not met', 'flyvemdm'), false, ERROR);
         return false;
      }

      // TODO : What if the fleet changes, or the value changes ?
      if (! $this->policy->apply($this->fleet, $value, $itemtype, $itemId)) {
         Session::addMessageAfterRedirect(__('Failed to apply the policy', 'flyvemdm'), false, ERROR);
         return false;
      }

      return $input;
   }

   /** $this->policy->field['group']
    * @see CommonDBRelation::post_addItem()
    */
   public function post_addItem() {
      $this->updateQueue($this->fleet, array($this->policy->getGroup()));
   }

   /**
    * @see CommonDBRelation::post_updateItem()
    */
   public function post_updateItem($history = 1) {
      $this->updateQueue($this->fleet, array($this->policy->getGroup()));
   }

   /**
    *
    * @see CommonDBTM::pre_deleteItem()
    */
   public function pre_deleteItem() {
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $this->policy  = $policyFactory->createFromDBByID($this->fields['plugin_flyvemdm_policies_id']);
      if (!$this->policy instanceof PluginFlyvemdmPolicyInterface) {
         Session::addMessageAfterRedirect(__('Policy not found', 'flyvemdm'), false, ERROR);
         return false;
      }
      $this->fleet = new PluginFlyvemdmFleet();
      if (!$this->fleet->getFromDB($this->fields['plugin_flyvemdm_fleets_id'])) {
         Session::addMessageAfterRedirect(__('Fleet not found', 'flyvemdm'), false, ERROR);
         return false;
      }
      return $this->policy->unapply($this->fleet, $this->fields['value'], $this->fields['itemtype'], $this->fields['items_id']);
   }

   /**
    * @see CommonDBTM::post_deleteItem()
    */
   public function post_purgeItem() {
      $this->updateQueue($this->fleet, array($this->policy->getGroup()));
   }

   public function updateQueue(PluginFlyvemdmNotifiable $item, $groups = array()) {
      if (! $item instanceof PluginFlyvemdmFleet) {
         // Cannot queue MQTT messages for devices
         // Then send them immediately
         $this->publishPolicies($item, $groups);
      } else {
         if ($this->silent) {
            return;
         }

         // Queue an update for each group
         foreach ($groups as $group) {
            $mqttUpdateQueue = new PluginFlyvemdmMqttupdatequeue();
            $mqttUpdateQueue->add([
                  'plugin_flyvemdm_fleets_id' => $item->getID(),
                  'group'                     => $group
            ]);
         }
      }
   }

   /**
    * MQTT publish all policies applying to the fleet
    *
    * @param PluginFlyvemdmNotifiable $item
    * @param array $groups the notifiable is updated only for the following policies groups
    */
   public function publishPolicies(PluginFlyvemdmNotifiable $item, $groups = array()) {
      global $DB;

      if ($this->silent) {
         return;
      }

      $fleet = $item->getFleet();

      if ($fleet !== null && $fleet->getField('is_default') == '0') {
         $topic = $item->getTopic();
         $fleetId = $fleet->getID();

         if (count($groups) == 0) {
            $fleet_policyTable = PluginFlyvemdmFleet_Policy::getTable();
            $policyTable = PluginFlyvemdmPolicy::getTable();
            $query = "SELECT DISTINCT `group`
            FROM `$fleet_policyTable` `fp`
            LEFT JOIN `$policyTable` `p` ON `fp`.`plugin_flyvemdm_policies_id` = `p`.`id`
            WHERE `fp`.`plugin_flyvemdm_fleets_id` = '$fleetId'";
            $result = $DB->query($query);

            if ($result === false) {
               while ($row = $DB->fetch_assoc($result)) {
                  $groupName = $row['group'];
                  $groupToEncode = $this->buildGroupOfPolicies($groupName, $fleet);
                  $encodedGroup = json_encode(array($groupName => $groupToEncode), JSON_UNESCAPED_SLASHES);
                  $fleet->notify("$topic/$groupName", $encodedGroup, 0, 1);
               }
            }
         } else {
            foreach ($groups as $groupName) {
               $groupToEncode = $this->buildGroupOfPolicies($groupName, $fleet);
               $encodedGroup = json_encode(array($groupName => $groupToEncode), JSON_UNESCAPED_SLASHES);
               $fleet->notify("$topic/$groupName", $encodedGroup, 0, 1);
            }
         }
      }
   }

   /**
    * Builds a group of policies using the value of an applied policy for a fleet, and the default value of
    * non applied policies of the same group
    * @param string $group name of a group of policies
    * @param PluginFlyvemdmFleet $fleet fleet the group will built for
    */
   protected function buildGroupOfPolicies($group, $fleet) {
      global $DB;

      $policy = new PluginFlyvemdmPolicy();
      $policiesByDefault = $policy->find("`group` = '$group'");

      // Collect ids of applied policies and prepare applied data
      $fleetId = $fleet->getID();
      $fleet_Policy = new PluginFlyvemdmFleet_Policy();
      $fleet_policyTable = PluginFlyvemdmFleet_Policy::getTable();
      $policyTable = PluginFlyvemdmPolicy::getTable();
      $query = "SELECT * FROM `$fleet_policyTable` `fp`
            LEFT JOIN `$policyTable` `p` ON `fp`.`plugin_flyvemdm_policies_id` = `p`.`id`
            WHERE `fp`.`plugin_flyvemdm_fleets_id`='$fleetId' AND `p`.`group` = '$group'";
      $result = $DB->query($query);
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $groupToEncode = array();
      $excludedPolicyIds = array();
      while ($row = $DB->fetch_assoc($result)) {
         $policy = $policyFactory->createFromDBByID($row['plugin_flyvemdm_policies_id']);
         if ($policy === null) {
            Toolbox::logInFile('php-errors', "Plugin Flyvemdm : Policy ID " . $row['plugin_flyvemdm_policies_id'] . "not found while generating MQTT message\n" );
         } else {
            $policyMessage = $policy->getMqttMessage($row['value'], $row['itemtype'], $row['items_id']);
            if ($policyMessage === false) {
               // There is an error while applying the policy
               continue;
            }
            $groupToEncode[] = $policyMessage;
         }
         $excludedPolicyIds[] = $row['plugin_flyvemdm_policies_id'];
      }

      $excludedPolicyIds = "'" . implode("', '", $excludedPolicyIds) . "'";
      $policy = new PluginFlyvemdmPolicy();
      $rows = $policy->find("`group` = '$group' AND `id` NOT IN ($excludedPolicyIds) AND `default_value` NOT IN ('')");
      foreach ($rows as $policyId => $row) {
         $policy = $policyFactory->createFromDBByID($row['id']);
         if ($policy === null) {
            Toolbox::logInFile('php-errors', "Plugin Flyvemdm : Policy ID " . $row['plugin_flyvemdm_policies_id'] . "not found while generating MQTT message\n" );
         } else {
            $policyMessage = $policy->getMqttMessage($row['default_value'], '', '');
            if ($policyMessage === false) {
               continue;
            } else {
               $groupToEncode[] = $policyMessage;
            }
         }
      }

      return $groupToEncode;
   }

   /**
    * Removes persisted MQTT messages for groups of policies
    *
    * @param PluginFlyvemdmNotifiable $item a notifiable item
    * @param array $groups array of groups to delete
    */
   public static function cleanupPolicies(PluginFlyvemdmNotifiable $item, $groups = array()) {
      global $DB;

      $mqttClient = PluginFlyvemdmMqttclient::getInstance();
      $topic = $item->getTopic();
      foreach ($groups as $groupName) {
         $mqttClient->publish("$topic/$groupName", null, 0, 1);
      }
   }

   /**
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      global $CFG_GLPI;

      $tab = array();
      $tab['common']           = __('Characteristics');

      $i = 2;

      $tab[$i]['table']         = $this->getTable();
      $tab[$i]['field']         = 'id';
      $tab[$i]['name']          = __('ID');
      $tab[$i]['massiveaction'] = false;
      $tab[$i]['datatype']      = 'number';

      $i++;
      $tab[$i]['table']         = PluginFlyvemdmFleet::getTable();
      $tab[$i]['field']         = 'id';
      $tab[$i]['name']          = __('Fleet ID');
      $tab[$i]['massiveaction'] = false;
      $tab[$i]['datatype']      = 'dropdown';

      $i++;
      $tab[$i]['table']         = PluginFlyvemdmPolicy::getTable();
      $tab[$i]['field']         = 'id';
      $tab[$i]['name']          = __('Policy ID');
      $tab[$i]['massiveaction'] = false;
      $tab[$i]['datatype']      = 'dropdown';

      $i++;
      $tab[$i]['table']         = self::getTable();
      $tab[$i]['field']         = 'itemtype';
      $tab[$i]['name']          = 'itemtype';
      $tab[$i]['massiveaction'] = false;
      $tab[$i]['nodisplay']     = true;
      $tab[$i]['datatype']      = 'string';

      $i++;
      $tab[$i]['table']         = self::getTable();
      $tab[$i]['field']         = 'items_id';
      $tab[$i]['name']          = 'item';
      $tab[$i]['massiveaction'] = false;
      $tab[$i]['nodisplay']     = true;
      $tab[$i]['datatype']      = 'integer';

      $i++;
      $tab[$i]['table']         = PluginFlyvemdmPolicy::getTable();
      $tab[$i]['field']         = 'name';
      $tab[$i]['name']          = 'policy_name';
      $tab[$i]['massiveaction'] = false;
      $tab[$i]['nodisplay']     = true;
      $tab[$i]['datatype']      = 'string';

      return $tab;

   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch (get_class($item)) {
         case PluginFlyvemdmFleet::class:
            static::showForFleet($item, $withtemplate);
      }
   }

   /**
    *
    * @param CommonDBTM $item
    */
   static function showForFleet(CommonDBTM $item, $withtemplate='') {
      global $DB, $CFG_GLPI;

      if (!$item->canView()) {
         return false;
      }

      $itemId  = $item->getID();
      $canedit = Session::haveRightsOr('flyvemdm:fleet', array(CREATE, UPDATE, DELETE, PURGE));
      $rand    = mt_rand();

      // Show apply policy form
      if ((empty($withtemplate) || ($withtemplate != 2))
          && $canedit) {
         $policyDropdown = PluginFlyvemdmPolicy::dropdown([
               'display'      => false,
               'name'         => 'plugin_flyvemdm_policies_id',
               'toupdate'     => [
                     'value_fieldname' => 'value',
                     'to_update'       => 'plugin_flyvemdm_policy_value',
                     'url'             => $CFG_GLPI['root_doc'] . "/plugins/flyvemdm/ajax/policyValue.php"
               ]
         ]);
      } else {
         $policyDropdown = null;
      }

      // Get all policy names
      $policy = new PluginFlyvemdmPolicy();
      $policies = $policy->find();

      // Get aplied policies
      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $appliedPolicies = $fleet_policy->find("`plugin_flyvemdm_fleets_id` = '$itemId'");

      // add needed data for display
      $factory = new PluginFlyvemdmPolicyFactory();
      foreach ($appliedPolicies as $id => &$appliedPolicy) {
         $appliedPolicy['checkbox']   = Html::getMassiveActionCheckBox(__CLASS__, $id);
         $appliedPolicy['policyName'] = $policies[$appliedPolicy['plugin_flyvemdm_policies_id']]['name'];
         $policyItem = $factory->createFromDBByID($appliedPolicy['plugin_flyvemdm_policies_id']);
         if ($policyItem !== null) {
            $fleet_policy              = new PluginFlyvemdmFleet_Policy();
            $fleet_policy->getFromDB($id);
            $appliedPolicy['value']    = $policyItem->showValue($fleet_policy);
         }
      }

      // Template data
      $addFormBegin = "<form name='fleetpolicy_form$rand' id='fleetpolicy_form$rand' method='post'
                       action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";
      $addFormEnd = Html::closeForm(false);

      $actions = ['purge' => _x('button', 'Delete permanently')];
      $massiveactionparams = [
                                    'num_displayed'    => count($appliedPolicies),
                                    'container'        => 'mass'.__CLASS__.$rand,
                                    'specific_actions' => $actions,
                                    'display'          => false
                              ];
      $massiveActionTop    = Html::showMassiveActions($massiveactionparams);
      $massiveactionparams['ontop'] = false;
      $massiveActionBottom = Html::showMassiveActions($massiveactionparams);

      $data = [
            'canEdit'   => $canedit,
            'addForm'   => [
                  'begin'        => $addFormBegin,
                  'end'          => $addFormEnd
            ],
            'massiveActionForm'  => [
                  'begin'        => Html::getOpenMassiveActionsForm('mass'.__CLASS__.$rand)
                                    . $massiveActionTop,
                  'end'          => $massiveActionBottom
                                    . Html::closeForm(false),
            ],
            'checkAll'  => Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand),
            'fleet_policy' => [
                  'policy'                      => $policyDropdown,
                  'plugin_flyvemdm_fleets_id'   => $itemId
            ],
            'policies'  => $appliedPolicies
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('fleet_policy.html', $data);

      Html::closeForm();
   }

   /**
    * Processes
    * @param unknown $post
    */
   public function preprocessInput($input) {
      $policyFactory = new PluginFlyvemdmPolicyFactory();
      $policy  = $policyFactory->createFromDBByID($input['plugin_flyvemdm_policies_id']);
      if ($policy) {
         $input = $policy->preprocessFormData($input);
      }

      return $input;
   }

   public function getAppliedPolicies(PluginFlyvemdmFleet $fleet) {
      $appliedPolicies = array();
      if (!$fleet->isNewItem()) {
         $itemId = $fleet->getID();
         $rows = $fleet_policy->find("`plugin_flyvemdm_fleets_id` = '$itemId'");
         foreach ($rows as $id => $row) {
            $apliedPolicy = new PluginFlyvemdmFleet_Policy();
            if ($apliedPolicy->getFromDB($id)) {
               $appliedPolicies[] = $appliedPolicy;
            }
         }
      }

      return $appliedPolicies;
   }
}
