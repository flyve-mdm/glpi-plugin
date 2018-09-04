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
class PluginFlyvemdmPolicyDeployapplication extends PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /** @var array $postUnapplyTask task to add after unapplying the policy */
   private $postUnapplyTask = null;

   /**
    * PluginFlyvemdmPolicyDeployapplication constructor.
    * @param PluginFlyvemdmPolicy $policy
    */
   public function __construct(PluginFlyvemdmPolicy $policy) {
      parent::__construct($policy);
      $this->symbol = $policy->getField('symbol');
      $this->unicityRequired = ($policy->getField('unicity') != '0');
      $this->group = $policy->getField('group');
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @return boolean
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      // Check the value exists
      if (!isset($value['remove_on_delete'])) {
         Session::addMessageAfterRedirect(__('The remove on delete flag is mandatory', 'flyvemdm'));
         return false;
      }

      // Check the value is a boolean
      if ($value['remove_on_delete'] != '0' && $value['remove_on_delete'] != '1') {
         Session::addMessageAfterRedirect(__('The remove on delete flag must be 0 or 1', 'flyvemdm'));
         return false;
      }

      // Check the itemtype is an application
      if ($itemtype != PluginFlyvemdmPackage::class) {
         Session::addMessageAfterRedirect(__('You must choose an application to apply this policy', 'flyvemdm'));
         return false;
      }

      //check the item exists
      $package = new PluginFlyvemdmPackage();
      if (!$package->getFromDB($itemId)) {
         Session::addMessageAfterRedirect(__('The application does not exists', 'flyvemdm'));
         return false;
      }

      return true;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @return array|boolean
    */
   public function getMqttMessage($value, $itemtype, $itemId) {
      $decodedValue = json_decode($value, JSON_OBJECT_AS_ARRAY);
      if (!$this->integrityCheck($decodedValue, $itemtype, $itemId)) {
         return false;
      }
      $package = new PluginFlyvemdmPackage();
      $package->getFromDB($itemId);
      $array = [
            $this->symbol  => $package->getField('package_name'),
            'id'           => $package->getID(),
            'versionCode'  => $package->getField('version_code'),
      ];

      return $array;
   }

   /**
    * @param mixed                             $value
    * @param mixed                             $itemtype
    * @param integer                           $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    * @return bool
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      // Check the policy is already applied
      $notifiableType = $notifiable->getType();
      $notifiableId = $notifiable->getID();
      $task = new PluginFlyvemdmTask();
      $rows = $task->find("`itemtype_applied` = '$notifiableType'
            AND `items_id_applied` = '$notifiableId'
            AND `plugin_flyvemdm_policies_id` = '" . $this->policyData->getID() . "'
            AND `items_id` = '$itemId'", '', '1');
      if (count($rows) > 0) {
         return false;
      }
      return true;
   }

   /**
    * @param mixed                             $value
    * @param mixed                             $itemtype
    * @param integer                           $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    * @return bool
    */
   public function conflictCheck($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      // Check there is not already a removal policy (to avoid opposite policy)
      $package = new PluginFlyvemdmPackage();
      if (!$package->getFromDB($itemId)) {
         // Cannot apply a non existing applciation
         Session::addMessageAfterRedirect(__('The application does not exists', 'flyvemdm'), false, ERROR);
         return false;
      }
      $packageName = $package->getField('name');

      $policyData = new PluginFlyvemdmPolicy();
      if (!$policyData->getFromDBBySymbol('removeApp')) {
         Toolbox::logInFile('php-errors', 'Plugin FlyveMDM: Application removal policy not found\n');
         // Give up this check
      } else {
         $policyId = $policyData->getID();
         $notifiableType = $notifiable->getType();
         $notifiableId = $notifiable->getID();
         $DbUtil = new DbUtils();
         $count = $DbUtil->countElementsInTable(PluginFlyvemdmTask::getTable(), [
            'itemtype_applied' => $notifiableType,
            'items_id_applied' => $notifiableId,
            'plugin_flyvemdm_policies_id' => $policyId,
            'value' => $packageName
         ]);
         if ($count > 0) {
            Session::addMessageAfterRedirect(__('A removal policy for this application is applied. Please, remove it first.', 'flyvemdm'), false, ERROR);
            return false;
         }
      }

      return true;
   }

   /**
    * @param mixed                             $value
    * @param mixed                             $itemtype
    * @param integer                           $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    *
    * @return bool
    */
   public function pre_unapply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      $decodedValue = json_decode($value, JSON_OBJECT_AS_ARRAY);
      if ($this->integrityCheck($decodedValue, $itemtype, $itemId) === false) {
         return false;
      }

      if ($decodedValue['remove_on_delete'] == '0') {
         return true;
      }

      $policyData = new PluginFlyvemdmPolicy();
      if (!$policyData->getFromDBBySymbol('removeApp')) {
         Toolbox::logInFile('php-errors',
            'Plugin FlyveMDM: Application removal policy not found\n');
         return false;
      }

      $package = new PluginFlyvemdmPackage();
      if (!$package->getFromDB($itemId)) {
         Toolbox::logInFile('php-errors',
            'Plugin FlyveMDM: Package info not found\n');
         return true;
      }

      $package = new $itemtype();
      if (!$package->getFromDB($itemId)) {
         return false;
      }

      $policyData = new PluginFlyvemdmPolicy();
      if (!$policyData->getFromDBBySymbol('removeApp')) {
         Toolbox::logInFile('php-errors', 'Plugin FlyveMDM: Application removal policy not found\n');
         return false;
      }

      $this->postUnapplyTask = [
         'itemtype_applied'            => $notifiable->getType(),
         'items_id_applied'            => $notifiable->getID(),
         'plugin_flyvemdm_policies_id' => $policyData->getID(),
         'value'                       => $package->getField('package_name'),
      ];

      return true;
   }

   public function post_unapply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      $task = new PluginFlyvemdmTask();
      $task->add($this->postUnapplyTask);
   }

   public function showValueInput($value = '', $itemType = '', $itemId = 0) {
      $itemtype = PluginFlyvemdmPackage::class;
      $removeOnDelete = 1;
      if ($value !== '') {
         $value = json_decode($value, JSON_OBJECT_AS_ARRAY);
         $removeOnDelete = $value['remove_on_delete'];
      }

      $data['typeTmpl'] = $itemtype;
      $data['itemtype'] = $itemtype;
      $data['dropdown'] = [
            PluginFlyvemdmPackage::dropdown([
                  'display'      => false,
                  'displaywith'  => ['alias'],
                  'name'         => 'items_id',
                  'value'        => $itemId,
               ]),
            Dropdown::showYesNo('value[remove_on_delete]', $removeOnDelete, -1, ['display' => false])
      ];
      $data['android_requirements'] = $this->getAndroidCompatibilityMessage();

      $twig = plugin_flyvemdm_getTemplateEngine();
      return $twig->render('policy_value.html.twig', ['data' => $data]);
   }

   /**
    * @param PluginFlyvemdmTask $task
    * @return string
    */
   public function showValue(PluginFlyvemdmTask $task) {
      $package = new PluginFlyvemdmPackage();
      if ($package->getFromDB($task->getField('items_id'))) {
         $alias = $package->getField('alias');
         $name  = $package->getField('name');
         return "$alias ($name)";
      }
      return NOT_AVAILABLE;
   }

   public static function getEnumSpecificStatus() {
      return [
         'waiting' => __('Waiting', 'flyvemdm'),
      ];
   }
}
