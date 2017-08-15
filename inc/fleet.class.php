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
 * @since 0.1.0
 */
class PluginFlyvemdmFleet extends CommonDBTM implements PluginFlyvemdmNotifiable {

   /**
    * @var string $rightname name of the right in DB
    */
   static $rightname = 'flyvemdm:fleet';

   /**
    * @var bool $dohistory maintain history
    */
   public $dohistory = true;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85)
    */
   protected $usenotepad               = true;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi >=0.85)
    */
   protected $usenotepadRights         = true;


   protected $deleteDefaultFleet       =  false;

   static $types = array(
         'Phone'
   );

   /**
    * Localized name of the type
    * @param $nb integer number of item in the type (default 0)
    */
   public static function getTypeName($nb = 0) {
      return _n('Fleet', 'Fleets', $nb, "flyvemdm");
   }

   public static function getMenuPicture() {
      return '../pics/picto-fleet.png';
   }

   /**
    * @see CommonGLPI::defineTabs()
    */
   public function defineTabs($options = array()) {
      $tab = array();
      $this->addDefaultFormTab($tab);
      if (!$this->isNewItem()) {
         $this->addStandardTab(PluginFlyvemdmAgent::class, $tab, $options);
         $this->addStandardTab('PluginFlyvemdmTask', $tab, $options);
         $this->addStandardTab('Notepad', $tab, $options);
         $this->addStandardTab('Log', $tab, $options);
      } else {
         $tab[1]  = __s('Main');
      }

      return $tab;
   }

   /**
    * Show form for edition
    */
   public function showForm($ID, $options = array()) {
      global $CFG_GLPI, $DB;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $twig = plugin_flyvemdm_getTemplateEngine();
      $fields              = $this->fields;
      $objectName          = autoName($this->fields["name"], "name",
            (isset($options['withtemplate']) && $options['withtemplate'] == 2),
            $this->getType(), -1);
      $fields['name']      = Html::autocompletionTextField($this, 'name',
                             array('value' => $objectName, 'display' => false));
      $data = [
            'withTemplate' => (isset($options['withtemplate']) && $options['withtemplate'] ? "*" : ""),
            'fleet'        => $fields,
      ];

      echo $twig->render('fleet.html', $data);

      $this->showFormButtons($options);
   }

   /**
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      if (!isset($input['is_default'])) {
         $input['is_default'] = '0';
      }

      if (!isset($input['entities_id'])) {
         $input['entities_id'] = $_SESSION['glpiactive_entity'];
      }

      return $input;
   }

   /**
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      unset($input['is_default']);
      if (isset($input['is_recursive']) && $this->fields['is_recursive'] != $input['is_recursive']) {
         // Do not change recursivity of default fleet
         unset($input['is_recursive']);
      }

      return $input;
   }

   /**
    * Actions done before the DELETE of the item in the database /
    * Maybe used to add another check for deletion
    * @return bool : true if item need to be deleted else false
    */
   public function pre_deleteItem() {
      global $DB;

      // check if fleet being deleted is the default one
      if ($this->fields['is_default'] == '1' && $this->deleteDefaultFleet !== true) {

         $config = Config::getConfigurationValues('flyvemdm', array('service_profiles_id'));
         //if ( !Entity::canPurge() && $_SESSION['glpiactiveprofile']['id'] != $config['service_profiles_id']) {
            //Session::addMessageAfterRedirect(__('Cannot delete the default fleet', 'flyvemdm'));
            //return false;
         //}
      }

      // move agents in the fleet into the default one
      $fleetId = $this->getID();
      $agent = new PluginFlyvemdmAgent();
      $entityId = $this->fields['entities_id'];
      $defaultFleet = self::getDefaultFleet($entityId);
      $agents = $this->getAgents();
      if ($defaultFleet === null && count($agents) > 0) {
         if (!$this->deleteDefaultFleet) {
            // No default fleet
            // TODO : Create it again ?
            Session::addMessageAfterRedirect(__('No default fleet found to move devices', 'flyvemdm'));
            return false;
         }
      }

      foreach ($agents as $agent) {
         if (!$agent->update([
               'id'                          => $agent->getID(),
               'plugin_flyvemdm_fleets_id'   => $defaultFleet->getID()
         ])) {
            Session::addMessageAfterRedirect(__('Could not move all devices to the not managed fleet', 'flyvemdm'));
            return false;
         }
      }

      //Delete policies on the fleet
      $fleetId = $this->getID();
      $task = new PluginFlyvemdmTask();
      $rows = $task->find("`plugin_flyvemdm_fleets_id` = '$fleetId'");
      foreach ($rows as $row) {
         $decodedValue = json_decode($row['value'], JSON_OBJECT_AS_ARRAY);
         if (isset($decodedValue['remove_on_delete']) && $decodedValue['remove_on_delete'] != '0') {
            $decodedValue['remove_on_delete'] = '0';
            $row['value'] = $decodedValue;
            $task->update($row);
         }
      }
      if (!$task->deleteByCriteria(array('plugin_flyvemdm_fleets_id' => $fleetId), true)) {
         Session::addMessageAfterRedirect(__('Could not delete policies on the fleet', 'flyvemdm'));
         return false;
      }

      $mqttQueue = new PluginFlyvemdmMqttupdatequeue();
      if (!$mqttQueue->deleteByCriteria(array('plugin_flyvemdm_fleets_id' => $fleetId))) {
         Session::addMessageAfterRedirect(__('Could not delete message queue on the fleet', 'flyvemdm'));
         // Do not fail yet. We need a CRON purge feature on this itemtype
         //return false;
      }

      return true;
   }

   /**
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      global $CFG_GLPI;

      $tab = array();
      $tab['common']                 = __s('Fleet', "flyvemdm");

      $i = 1;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'name';
      $tab[$i]['name']                = __('Name');
      $tab[$i]['datatype']            = 'itemlink';
      $tab[$i]['massiveaction']       = false;

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'id';
      $tab[$i]['name']                = __('ID');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'number';

      $i++;
      $tab[$i]['table']               = PluginFlyvemdmTask::getTable();
      $tab[$i]['field']               = 'items_id';
      $tab[$i]['name']                = _n('Associated element', 'Associated elements', Session::getPluralNumber());
      $tab[$i]['datatype']            = 'specific';
      $tab[$i]['comments']            = true;
      $tab[$i]['nosort']              = true;
      $tab[$i]['nosearch']            = true;
      $tab[$i]['additionalfields']    = array('itemtype');
      $tab[$i]['joinparams']          = array('jointype'   => 'child');
      $tab[$i]['forcegroupby']        = true;
      $tab[$i]['massiveaction']       = false;

      $i++;
      $tab[$i]['table']               = PluginFlyvemdmTask::getTable();
      $tab[$i]['field']               = 'itemtype';
      $tab[$i]['name']                = _n('Associated item type', 'Associated item types', Session::getPluralNumber());
      $tab[$i]['datatype']            = 'itemtypename';
      $tab[$i]['itemtype_list']       = 'fleet_types';
      $tab[$i]['nosort']              = true;
      $tab[$i]['additionalfields']    = array('itemtype');
      $tab[$i]['joinparams']          = array('jointype'   => 'child');
      $tab[$i]['forcegroupby']        = true;
      $tab[$i]['massiveaction']       = false;

      $i++;
      $tab[$i]['table']           = self::getTable();
      $tab[$i]['field']           = 'is_default';
      $tab[$i]['name']            = __('Not managed', 'flyvemdm');
      $tab[$i]['datatype']        = 'bool';
      $tab[$i]['massiveaction']   = false;

      return $tab;
   }

   /**
    *
    * @see PluginFlyvemdmNotifiable::getTopic()
    */
   public function getTopic() {
      if (!isset($this->fields['id'])) {
         return null;
      }

      return '/' . $this->fields['entities_id'] . '/fleet/' . $this->fields['id'];
   }

   /**
    * Actions done after the ADD of the item in the database
    * @return nothing
    */
   public function post_addItem() {
      // Generate default policies for groups of policies
      $task = new PluginFlyvemdmTask();
      $task->publishPolicies($this, array('camera', 'connectivity', 'encryption', 'policies'));
   }

   /**
    *
    * @see CommonDBTM::post_deleteItem()
    */
   public function post_deleteItem() {
      // unlink agents
      $this->post_purgeItem();
   }

   /**
    *
    * @see CommonDBTM::post_purgeItem()
    */
   public function post_purgeItem() {
      global $DB;

      // now the fleet is empty, delete MQTT topcis
      $table_policy = PluginFlyvemdmPolicy::getTable();
      $query = "SELECT DISTINCT `group` FROM `$table_policy`";
      $result = $DB->query($query);
      if ($result) {
         $groups = array();
         while ($row = $DB->fetch_assoc($result)) {
            $groups[] = $row['group'];
         }
      }
      PluginFlyvemdmTask::cleanupPolicies($this, $groups);
   }

   /**
    * @see CommonDBTM::cleanDBonPurge()
    */
   public function cleanDBonPurge() {
      global $DB;

      // Unsuscribe all agents from the fleet
      $fleetId = $this->getID();
      $query = "SELECT `id`
      FROM `glpi_plugin_flyvemdm_agents`
      WHERE `glpi_plugin_flyvemdm_agents`.`plugin_flyvemdm_fleets_id` = '$fleetId'";

      if ($result = $DB->query($query)) {
         while ($row = $DB->fetch_assoc($result)) {
            $agent = new PluginFlyvemdmAgent();
            if ($agent->getFromDB($row['id'])) {
               $agent->unsubscribe();
            }
         }
      }

      // Force deletion regardless a file or application removal policy should take place
      $taskTable = getTableForItemType('PluginFlyvemdmTask');
      $itemId = $this->getID();
      $query = "DELETE FROM `$taskTable` WHERE `plugin_flyvemdm_fleets_id`='$itemId'";
      $DB->query($query);
   }

   /**
    * Get all agents in the fleet
    *
    * @return array isntances of agents belonging to the fleet
    */
   public function getAgents() {
      $id = $this->getID();
      if (! ($id > 0)) {
         return [];
      }
      $agents = [];
      $agent = new PluginFlyvemdmAgent();
      $rows = $agent->find("`plugin_flyvemdm_fleets_id`='$id'");

      foreach ($rows as $agentId => $row) {
         $agent = new PluginFlyvemdmAgent();
         if ($agent->getFromDB($agentId)) {
            $agents[] = $agent;
         }
      }

      return $agents;
   }

   /**
    * @see PluginFlyvemdmNotifiable::getFleet()
    */
   public function getFleet() {
      if ($this->isNewItem()) {
         return null;
      }

      return $this;
   }

   /**
    * @see PluginFlyvemdmNotifiable::getPackages()
    */
   public function getPackages() {
      $packages = array();

      $fleetId = $this->getID();
      if ($fleetId > 0) {
         $task = new PluginFlyvemdmTask();
         $rows = $task->find("`plugin_flyvemdm_fleets_id`='$fleetId' AND `itemtype`='PluginFlyvemdmPackage'");
         foreach ($rows as $id => $row) {
            $package = new PluginFlyvemdmPackage();
            $package->getFromDB($row['plugin_flyvemdm_packages_id']);
            $packages[] = $package;
         }
      }

      return $packages;
   }

   /**
    * @see PluginFlyvemdmNotifiable::getFiles()
    */
   public function getFiles() {
      $files = array();

      $fleetId = $this->getID();
      if ($fleetId > 0) {
         $task = new PluginFlyvemdmTask();
         $rows = $task->find("`plugin_flyvemdm_fleets_id`='$fleetId' AND `itemtype`='PluginFlyvemdmFile'");
         foreach ($rows as $id => $row) {
            $file = new PluginFlyvemdmPackage();
            $file->getFromDB($row['plugin_flyvemdm_packages_id']);
            $files[] = $file;
         }
      }

      return $files;
   }

   /**
    * Gets the default fleet for an entity
    * @param string $entityId ID of the entoty to search in
    * @return PluginFlyvemdmFleet|null
    */
   public function getFromDBByDefaultForEntity($entityId = null) {
      if ($entityId === null) {
         $entityId = $_SESSION['glpiactive_entity'];
      }

      $rows = $this->find("`is_default`='1' AND `entities_id`='$entityId'", "`id` ASC");
      if (count($rows) < 1) {
         return $this->add(array(
               'is_default'  => '1',
               'name'        => __("not managed fleet", 'flyvemdm'),
               'entities_id' => $entityId,
         ));
      }
      reset($rows);
      $this->getFromDB(current(array_keys($rows)));
      return $this->getID();
   }

   /**
    * Gets the default fleet for an entity
    * @param string $entityId ID of the entoty to search in
    * @return PluginFlyvemdmFleet|null
    */
   public static function getDefaultFleet($entityId = '') {
      if ($entityId == '') {
         $entityId = $_SESSION['glpiactive_entity'];
      }
      $defaultFleet = new PluginFlyvemdmFleet();
      if (!$defaultFleet->getFromDBByQuery(
            "WHERE `is_default`='1' AND `entities_id`='$entityId'"
            )) {
               return null;
      }
            return $defaultFleet;
   }

   /**
    *
    * @see PluginFlyvemdmNotifiable::notify()
    */
   public function notify($topic, $mqttMessage, $qos = 0, $retain = 0) {
      $mqttClient = PluginFlyvemdmMqttclient::getInstance();
      $mqttClient->publish($topic, $mqttMessage, $qos, $retain);
   }
   /**
    * create folders and initial setup of the entity related to MDM
    * @param CommonDBTM $item
    */
   public function hook_entity_add(CommonDBTM $item) {
      if ($item instanceof Entity) {
         // Create the default fleet for a new entity
         $this->getFromDBByDefaultForEntity($item->getID());
      }
   }

   /**
    * delete fleets in the entity being purged
    * @param CommonDBTM $item
    */
   public function hook_entity_purge(CommonDBTM $item) {
      if ($item instanceof Entity) {
         $fleet = new static();
         $fleet->deleteDefaultFleet = true;
         $fleet->deleteByCriteria(array('entities_id' => $item->getField('id')), 1);
      }
   }
}
