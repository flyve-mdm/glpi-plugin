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
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
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
    *
    * @return array
    */
   public static final function getEnumBaseTaskStatus() {
      return [
         'pending'      => __('Pending', 'flyvemdm'),
         'received'     => __('Received', 'flyvemdm'),
         'done'         => __('Done', 'flyvemdm'),
         'failed'       => __('Failed', 'flyvemdm'),
         'canceled'     => __('Canceled', 'flyvemdm'),
         'incompatible' => __('Incompatible', 'flyvemdm'),

         // when a policy is applied on a fleet and an agent in the fleet
         // only the policy on the agent must apply
         // the conflicting policy on the fleet won't apply, and the agent
         // must feedback the status 'overriden'
         'overriden'    => __('Overriden', 'flyvemdm'),
      ];
   }

   /**
    * get specific task statuses
    * To be overriden in child class
    *
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
    *
    * @return array
    */
   protected function jsonDecodeProperties($properties, array $defaultProperties) {
      if (empty($properties)) {
         return $defaultProperties;
      }
      $propertyCollection = json_decode($properties, true);
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
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    *
    * @return bool
    */
   public function canApply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      return $this->canApply;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    *
    * @return boolean
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      if (!$this->unicityRequired) {
         return true;
      }

      $policyId            = $this->policyData->getID();
      $notifiableType      = $notifiable->getType();
      $notifiableId        = $notifiable->getID();
      $task = new PluginFlyvemdmTask();
      $relationCollection  = $task->find("`itemtype_applied` = '$notifiableType' AND `items_id_applied`='$notifiableId' AND `plugin_flyvemdm_policies_id`='$policyId'", '', '1');
      return (count($relationCollection) === 0);
   }

   public function conflictCheck($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      return true;
   }

   public function integrityCheck($value, $itemtype, $itemId) {
      return true;
   }

   public function translateData() {
      return '';
   }

   public function getGroup() {
      return $this->group;
   }

   public function pre_apply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      return true;
   }

   public function pre_unapply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      // Do nothing by default
      // May be overriden by inhrited classes
      return true;
   }

   public function post_unapply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {}

   public function showValueInput($value = '', $itemType = '', $itemId = 0) {

      $data['itemtype'] = $itemType;
      $data['value'] = $value;
      $data['typeTmpl'] = PluginFlyvemdmPolicyBase::class;
      $data['android_requirements'] = $this->getAndroidCompatibilityMessage();
      $twig = plugin_flyvemdm_getTemplateEngine();
      return $twig->render('policy_value.html.twig', ['data' => $data]);
   }

   public function showValue(PluginFlyvemdmTask $task) {
      return $task->getField('value');
   }

   public function preprocessFormData($input) {
      return $input;
   }

   /**
    * @param $status
    *
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
    *
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
         'input' => $this->showValueInput($value, $itemtype, $itemId),
      ];
      if ($mode == "update") {
         $form['url'] = Toolbox::getItemTypeFormURL(PluginFlyvemdmTask::class);
         $form['rand'] = mt_rand();
         $form['taskId'] = $_input['task'];
         $form['policyId'] = $this->policyData->getID();
         $form['itemtype_applied'] = $_input['itemtype_applied'];
         $form['items_id_applied'] = $_input['items_id_applied'];
         $form['_csrf'] = (GLPI_USE_CSRF_CHECK) ? Html::hidden('_glpi_csrf_token',
            ['value' => Session::getNewCSRFToken()]) : '';
      }
      $twig = plugin_flyvemdm_getTemplateEngine();
      return $twig->render('policy_form.html.twig', ['form' => $form]);
   }

   protected function getAndroidCompatibilityMessage() {
      $min = $this->policyData->getField('android_min_version');
      $max = $this->policyData->getField('android_max_version');

      $message = '';
      if ($min == '0' && $max != '0') {
         $message = sprintf(
            __('Compatibility with Android up to %1$s.', 'flyvemdm'),
            $max
         );
      }
      if ($min != '0' && $max == '0') {
         $message = sprintf(
            __('Compatibility with Android from %1$s.', 'flyvemdm'),
            $min
         );
      }
      if ($min != '0' && $max != '0') {
         $message = sprintf(
            __('Compatibility with Android %1$s to %2$s.', 'flyvemdm'),
            $min,
            $max
         );
      }
      if ($this->policyData->getField('is_android_system') != '0') {
         $message .= ' ' . sprintf('Requires system privileges.', 'flyvemdm');
      }
      return $message;
   }
}
