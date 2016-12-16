<?php
/**
 LICENSE

Copyright (C) 2016 Teclib'
Copyright (C) 2010-2016 by the FusionInventory Development Team.

This file is part of Flyve MDM Plugin for GLPI.

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
You should have received a copy of the GNU Affero General Public License
along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 ------------------------------------------------------------------------------
 @author    Thierry Bugier Pineau
 @copyright Copyright (c) 2016 Flyve MDM plugin team
 @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 @link      https://github.com/flyvemdm/backend
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.32
 */
class PluginStorkmdmFleet_Policy extends CommonDBRelation {

   // From CommonDBRelation
   /**
    * @var string $itemtype_1 First itemtype of the relation
    */
   public static $itemtype_1 = 'PluginStorkmdmFleet';

   /**
    * @var string $items_id_1 DB's column name storing the ID of the first itemtype
    */
   public static $items_id_1 = 'plugin_storkmdm_fleets_id';

   /**
    * @var string $itemtype_2 Second itemtype of the relation
    */
   public static $itemtype_2 = 'PluginStorkmdmPolicy';

   /**
    * @var string $items_id_2 DB's column name storing the ID of the second itemtype
    */
   public static $items_id_2 = 'plugin_storkmdm_policies_id';

   /**
    * @var PluginStorkmdmPolicyBase Policy
    */
   protected $policy;

   /**
    * @var PluginStorkmdmFleet $fleet Fleet
    */
   protected $fleet;

   /**
    *
    * @var boolean $silent
    */
   protected $silent;

   /**
    * {@inheritDoc}
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
    * {@inheritDoc}
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

      if (!isset($input['plugin_storkmdm_policies_id'])
            || !isset($input['plugin_storkmdm_fleets_id'])) {
               Session::addMessageAfterRedirect(__('Fleet and policy must be specified', 'storkmdm'), false, ERROR);
         return false;
      }

      // Check the policy exists
      $policyFactory = new PluginStorkmdmPolicyFactory();
      $this->policy  = $policyFactory->createFromDBByID($input['plugin_storkmdm_policies_id']);
      if (!$this->policy instanceof PluginStorkmdmPolicyInterface) {
         Session::addMessageAfterRedirect(__('Policy not found', 'storkmdm'), false, ERROR);
         return false;
      }

      // Check the property of the relation is valid
      if (!$this->policy->integrityCheck($value, $input['itemtype'], $input['items_id'])) {
         if (isset($_SESSION['MESSAGE_AFTER_REDIRECT']) && $_SESSION['MESSAGE_AFTER_REDIRECT'] === null
               || !isset($_SESSION['MESSAGE_AFTER_REDIRECT'])) {
            Session::addMessageAfterRedirect(__('Incorrect value for this policy', 'storkmdm'), false, ERROR);
         }
         return false;
      }

      // Check the fleet exists
      $fleetId = $input['plugin_storkmdm_fleets_id'];
      $this->fleet = new PluginStorkmdmFleet();
      if (!$this->fleet->getFromDB($fleetId)) {
         Session::addMessageAfterRedirect(__('Cannot find the target fleet', 'storkmdm'), false, ERROR);
         return false;
      }

      // default fleet check
      if ($this->fleet->getField('is_default')) {
         Session::addMessageAfterRedirect(__('Cannot apply a policy on a not managed fleet', 'storkmdm'), false, ERROR);
         return false;
      }

      if (!$this->policy->unicityCheck($value, $input['itemtype'], $input['items_id'], $this->fleet)) {
         Session::addMessageAfterRedirect(__('Policy already applied', 'storkmdm'), false, ERROR);
         return false;
      }

      // Check the policy may be applied to the fleet and the value matches requirements
      if (!$this->policy->canApply($this->fleet, $input['value'], $input['itemtype'], $input['items_id'])) {
         Session::addMessageAfterRedirect(__('The requirements for this policy are not met', 'storkmdm'), false, ERROR);
         return false;
      }

      if (! $this->policy->apply($this->fleet, $input['value'], $input['itemtype'], $input['items_id'])) {
         Session::addMessageAfterRedirect(__('Failed to apply the policy', 'storkmdm'), false, ERROR);
         return false;
      }

      return $input;
   }

   /**
    * {@inheritDoc}
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
      if (isset($input['plugin_storkmdm_policies_id'])) {
         $policyId = $input['plugin_storkmdm_policies_id'];
      } else {
         $policyId = $this->fields['plugin_storkmdm_policies_id'];
      }
      $policyFactory = new PluginStorkmdmPolicyFactory();
      $this->policy = $policyFactory->createFromDBByID($policyId);
      if (!$this->policy instanceof PluginStorkmdmPolicyInterface) {
         Session::addMessageAfterRedirect(__('Policy not found', 'storkmdm'), false, ERROR);
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
      if (isset($input['plugin_storkmdm_fleets_id'])) {
         $fleetId = $input['plugin_storkmdm_fleets_id'];
      } else {
         $fleetId = $this->fields['plugin_storkmdm_fleets_id'];
      }
      $this->fleet = new PluginStorkmdmFleet();
      if (!$this->fleet->getFromDB($fleetId)) {
         Session::addMessageAfterRedirect(__('Cannot find the target fleet', 'storkmdm'), false, ERROR);
         return false;
      }

      // default fleet check
      if ($this->fleet->getField('is_default')) {
         Session::addMessageAfterRedirect(__('Cannot apply a policy on a not managed fleet', 'storkmdm'), false, ERROR);
         return false;
      }

      // Check the policy may be applied to the fleet and the value is matches requirements
      if (!$this->policy->integrityCheck($value, $itemtype, $itemId)) {
         Session::addMessageAfterRedirect(__('Incorrect value for this policy', 'storkmdm'), false, ERROR);
         return false;
      }

      if ($itemId != $this->fields['items_id'] || $policyId != $this->fields['plugin_storkmdm_policies_id']) {
         // the fleet and the policy are not changing, then check unicity
         if (!$this->policy->unicityCheck($this->fleet)) {
            Session::addMessageAfterRedirect(__('Policy already applied', 'storkmdm'), false, ERROR);
            return false;
         }
      }

      if (!$this->policy->canApply($this->fleet, $value, $itemtype, $itemId)) {
         Session::addMessageAfterRedirect(__('The requirements for this policy are not met', 'storkmdm'), false, ERROR);
         return false;
      }

      // TODO : What if the fleet changes, or the value changes ?
      if (! $this->policy->apply($this->fleet, $value, $itemtype, $itemId)) {
         Session::addMessageAfterRedirect(__('Failed to apply the policy', 'storkmdm'), false, ERROR);
         return false;
      }

      return $input;
   }

   /** $this->policy->field['group']
    * {@inheritDoc}
    * @see CommonDBRelation::post_addItem()
    */
   public function post_addItem() {
      $this->updateQueue($this->fleet, array($this->policy->getGroup()));
   }

   /**
    * {@inheritDoc}
    * @see CommonDBRelation::post_updateItem()
    */
   public function post_updateItem($history = 1) {
      $this->updateQueue($this->fleet, array($this->policy->getGroup()));
   }

   /**
    *
    * {@inheritDoc}
    * @see CommonDBTM::pre_deleteItem()
    */
   public function pre_deleteItem() {
      $policyFactory = new PluginStorkmdmPolicyFactory();
      $this->policy  = $policyFactory->createFromDBByID($this->fields['plugin_storkmdm_policies_id']);
      if (!$this->policy instanceof PluginStorkmdmPolicyInterface) {
         Session::addMessageAfterRedirect(__('Policy not found', 'storkmdm'), false, ERROR);
         return false;
      }
      $this->fleet = new PluginStorkmdmFleet();
      if (!$this->fleet->getFromDB($this->fields['plugin_storkmdm_fleets_id'])) {
         Session::addMessageAfterRedirect(__('Fleet not found', 'storkmdm'), false, ERROR);
         return false;
      }
      return $this->policy->unapply($this->fleet, $this->fields['value'], $this->fields['itemtype'], $this->fields['items_id']);
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::post_deleteItem()
    */
   public function post_purgeItem() {
      $this->updateQueue($this->fleet, array($this->policy->getGroup()));
   }

   public function updateQueue(PluginStorkmdmNotifiable $item, $groups = array()) {
      if (! $item instanceof PluginStorkmdmFleet) {
         // Cannot queue MQTT messages for devices
         // Then send them immediately
         $this->publishPolicies($item, $groups);
      } else {
         if ($this->silent) {
            return;
         }

         // Queue an update for each group
         foreach ($groups as $group) {
            $mqttUpdateQueue = new PluginStorkmdmMqttupdatequeue();
            $mqttUpdateQueue->add([
                  'plugin_storkmdm_fleets_id' => $item->getID(),
                  'group'                     => $group
            ]);
         }
      }
   }

   /**
    * MQTT publish all policies applying to the fleet
    *
    * @param PluginStorkmdmNotifiable $item
    * @param array $groups the notifiable is updated only for the following policies groups
    */
   public function publishPolicies(PluginStorkmdmNotifiable $item, $groups = array()) {
      global $DB;

      if ($this->silent) {
         return;
      }

      $fleet = $item->getFleet();

      if ($fleet !== null && $fleet->getField('is_default') == '0') {
         $topic = $item->getTopic();
         $fleetId = $fleet->getID();

         if (count($groups) == 0) {
            $fleet_policyTable = PluginStorkmdmFleet_Policy::getTable();
            $policyTable = PluginStorkmdmPolicy::getTable();
            $query = "SELECT DISTINCT `group`
            FROM `$fleet_policyTable` `fp`
            LEFT JOIN `$policyTable` `p` ON `fp`.`plugin_storkmdm_policies_id` = `p`.`id`
            WHERE `fp`.`plugin_storkmdm_fleets_id` = '$fleetId'";
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
            foreach($groups as $groupName) {
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
    * @param PluginStorkmdmFleet $fleet fleet the group will built for
    */
   protected function buildGroupOfPolicies($group, $fleet) {
      global $DB;

      $policy = new PluginStorkmdmPolicy();
      $policiesByDefault = $policy->find("`group` = '$group'");

      // Collect ids of applied policies and prepare applied data
      $fleetId = $fleet->getID();
      $fleet_Policy = new PluginStorkmdmFleet_Policy();
      $fleet_policyTable = PluginStorkmdmFleet_Policy::getTable();
      $policyTable = PluginStorkmdmPolicy::getTable();
      $query = "SELECT * FROM `$fleet_policyTable` `fp`
            LEFT JOIN `$policyTable` `p` ON `fp`.`plugin_storkmdm_policies_id` = `p`.`id`
            WHERE `fp`.`plugin_storkmdm_fleets_id`='$fleetId' AND `p`.`group` = '$group'";
      $result = $DB->query($query);
      $policyFactory = new PluginStorkmdmPolicyFactory();
      $groupToEncode = array();
      $excludedPolicyIds = array();
      while ($row = $DB->fetch_assoc($result)) {
         $policy = $policyFactory->createFromDBByID($row['plugin_storkmdm_policies_id']);
         if ($policy === null) {
            Toolbox::logInFile('php-errors', "Plugin Storkmdm : Policy ID " . $row['plugin_storkmdm_policies_id'] . "not found while generating MQTT message\n" );
         } else {
            $policyMessage = $policy->getMqttMessage($row['value'], $row['itemtype'], $row['items_id']);
            if ($policyMessage === false) {
               // There is an error while applying the policy
               continue;
            }
            $groupToEncode[] = $policyMessage;
         }
         $excludedPolicyIds[] = $row['plugin_storkmdm_policies_id'];
      }

      $excludedPolicyIds = "'" . implode("', '", $excludedPolicyIds) . "'";
      $policy = new PluginStorkmdmPolicy();
      $rows = $policy->find("`group` = '$group' AND `id` NOT IN ($excludedPolicyIds) AND `default_value` NOT IN ('')");
      foreach ($rows as $policyId => $row) {
         $policy = $policyFactory->createFromDBByID($row['id']);
         if ($policy === null) {
            Toolbox::logInFile('php-errors', "Plugin Storkmdm : Policy ID " . $row['plugin_storkmdm_policies_id'] . "not found while generating MQTT message\n" );
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
    * @param PluginStorkmdmNotifiable $item a notifiable item
    * @param array $groups array of groups to delete
    */
   public static function cleanupPolicies(PluginStorkmdmNotifiable $item, $groups = array()) {
      global $DB;

      $mqttClient = PluginStorkmdmMqttclient::getInstance();
      $topic = $item->getTopic();
      foreach($groups as $groupName) {
         $mqttClient->publish("$topic/$groupName", null, 0, 1);
      }
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      global $CFG_GLPI;

      $tab = array();
      $tab['common']           = __('Characteristics');

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = __('ID');
      $tab[2]['massiveaction'] = false;
      $tab[2]['datatype']      = 'number';

      $tab[3]['table']         = PluginStorkmdmFleet::getTable();
      $tab[3]['field']         = 'id';
      $tab[3]['name']          = __('Fleet ID');
      $tab[3]['massiveaction'] = false;
      $tab[3]['datatype']      = 'dropdown';

      $tab[4]['table']         = PluginStorkmdmPolicy::getTable();
      $tab[4]['field']         = 'id';
      $tab[4]['name']          = __('Policy ID');
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'dropdown';

      $tab[5]['table']               = self::getTable();
      $tab[5]['field']               = 'itemtype';
      $tab[5]['name']                = 'itemtype';
      $tab[5]['massiveaction']       = false;
      $tab[5]['nodisplay']           = true;
      $tab[5]['datatype']            = 'string';

      $tab[6]['table']               = self::getTable();
      $tab[6]['field']               = 'items_id';
      $tab[6]['name']                = 'item';
      $tab[6]['massiveaction']       = false;
      $tab[6]['nodisplay']           = true;
      $tab[6]['datatype']            = 'integer';

      return $tab;

   }

   /**
    * Uninstall from GLPI
    */
   public static function uninstall() {
      global $DB;

      $table = getTableForItemType(__CLASS__);
      $DB->query("DROP TABLE IF EXISTS `$table`") or die($DB->error());
   }

}
