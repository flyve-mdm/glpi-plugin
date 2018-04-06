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

/**
 * @since 0.1.33
 */
abstract class PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /**
    * @var bool $canApply if true the policy can be applied
    */
   protected $canApply = true;

   /**
    * @var bool $unicityRequired if true the policy cannot be applied more than once to a fleet
    */
   protected $unicityRequired = true;

   /**
    * @var string $symbol symbol of the policy
    */
   protected $symbol;

   /**
    * @var string $group name of group thie policy belongs to
    */
   protected $group;

   /**
    * @var PluginFlyvemdmPolicy instance of the policy in the DB
    */
   protected $policyData;

   /**
    * @var array list of statuses specific to a policy type. To be overrided in child class
    */
   protected $specificStatuses = [];

    /**
     * get common task statuses
     * @return array
     */
   public static final function getEnumBaseTaskStatus() {
      return [
         'pending'   => __('Pending', 'flyvemdm'),
         'received'  => __('Received', 'flyvemdm'),
         'done'      => __('Done', 'flyvemdm'),
         'failed'    => __('Failed', 'flyvemdm'),
         'canceled'  => __('Canceled', 'flyvemdm'),
      ];
   }

    /**
     * get specific task statuses
     * To be overriden in child class
     * @return array
     */
   public static function getEnumSpecificStatus() {
      return [];
   }

    /**
    * PluginFlyvemdmPolicyBase constructor.
    * @param PluginFlyvemdmPolicy $policy
    */
   public function __construct(PluginFlyvemdmPolicy $policy) {
      $this->policyData = $policy;
   }

   /**
    * JSON decode properties for the policy and merges them with default values
    * @param string $properties
    * @param array $defaultProperties
    * @return array
    */
   protected function jsonDecodeProperties($properties, array $defaultProperties) {
      if (empty($properties)) {
         return $defaultProperties;
      } else {
         $propertyCollection = json_decode($properties, true);
      }
      if (empty($propertyCollection)) {
         return $defaultProperties;
      }
      $intersect  = array_intersect_key($propertyCollection, $defaultProperties);
      $difference = array_diff_key($defaultProperties, $propertyCollection);
      return $difference + $intersect;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @param PluginFlyvemdmFleet $fleet
    * @return bool
    */
   public function canApply($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet) {
      return $this->canApply;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @param PluginFlyvemdmFleet $fleet
    * @return bool
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet) {
      if ($this->unicityRequired) {
         $policyId            = $this->policyData->getID();
         $fleetId             = $fleet->getID();
         $task = new PluginFlyvemdmTask();
         $relationCollection  = $task->find("`plugin_flyvemdm_fleets_id`='$fleetId' AND `plugin_flyvemdm_policies_id`='$policyId'", '', '1');
         if (count($relationCollection) > 0) {
            // A relation already exists for this policy and this fleet
            return false;
         }
      }
      return true;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @param PluginFlyvemdmFleet $fleet
    * @return bool
    */
   public function conflictCheck($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet) {
      return true;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @return bool
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      return true;
   }

   /**
    * @return string
    */
   public function translateData() {
      return '';
   }

   /**
    * @return string
    */
   public function getGroup() {
      return $this->group;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @return bool
    * @param PluginFlyvemdmFleet $fleet
    */
   public function pre_apply($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet) {
      return true;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @param PluginFlyvemdmFleet $fleet
    * @return bool
    */
   public function pre_unapply($value, $itemtype, $itemId, PluginFlyvemdmFleet $fleet) {
      // Do nothing by default
      // May be overriden by inhrited classes
      return true;
   }

   /**
    * @param string $value value of the task
    * @param string $itemType type of the item linked to the task
    * @param integer $itemId ID of the item
    * @return string
    */
   public function showValueInput($value = '', $itemType = '', $itemId = 0) {
      $html = '<input name="value" value="' . $value . '" >';

      return $html;
   }

   /**
    * @param PluginFlyvemdmTask $task
    * @return mixed
    */
   public function showValue(PluginFlyvemdmTask $task) {
      return $task->getField('value');
   }

   /**
    * @param array $input
    * @return array
    */
   public function preprocessFormData($input) {
      return $input;
   }

   /**
    * @param $status
    * @return mixed
    */
   public function filterStatus($status) {
      $allStatuses = array_merge(self::getEnumBaseTaskStatus(), static::getEnumSpecificStatus());
      if (!in_array($status, array_keys($allStatuses))) {
         return null;
      }

      return $status;
   }

   /**
    * @return PluginFlyvemdmPolicy
    */
   public function getPolicyData() {
      return $this->policyData;
   }

   /**
    * Generate the form for add or edit a policy
    *
    * @param string $mode add or update
    * @param array $_input values for update
    * @return string html
    */
   public function formGenerator(
      $mode = 'add',
      array $_input = []
   ) {
      $task = new PluginFlyvemdmTask();
      $value = '';
      $itemtype = '';
      $itemId = 0;
      if (!$task->isNewID($_input['task'])) {
         $task->getFromDB($_input['task']);
         $value = $task->getField('value');
         $itemtype = $task->getField('itemtype');
         $itemId = $task->getField('items_id');
      }
      $form = [
         'mode' => $mode,
         'input' => $this->showValueInput($value, $itemtype, $itemId)
      ];
      if ($mode == "update") {
         $form['url'] = Toolbox::getItemTypeFormURL(PluginFlyvemdmTask::class);
         $form['rand'] = mt_rand();
         $form['taskId'] = $_input['task'];
         $form['policyId'] = $_input['policyId'];
         $form['fleetId'] = $_input['fleet'];
         $form['_csrf'] = (GLPI_USE_CSRF_CHECK) ? Html::hidden('_glpi_csrf_token',
            ['value' => Session::getNewCSRFToken()]) : '';
      }
      $twig = plugin_flyvemdm_getTemplateEngine();
      return $twig->render('policy_form.html.twig', ['form' => $form]);
   }
}
