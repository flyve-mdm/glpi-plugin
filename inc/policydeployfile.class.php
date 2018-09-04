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
class PluginFlyvemdmPolicyDeployfile extends PluginFlyvemdmPolicyBase implements PluginFlyvemdmPolicyInterface {

   /** @var array $postUnapplyTask task to add after unapplying the policy */
   private $postUnapplyTask = null;

   /**
    * @param PluginFlyvemdmPolicy $policy
    * @internal param string $properties
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
    * @return bool
    */
   public function integrityCheck($value, $itemtype, $itemId) {
      // Check values exist
      if (!isset($value['destination']) || !isset($value['remove_on_delete'])) {
         Session::addMessageAfterRedirect(__('A destination and the remove on delete flag are mandatory', 'flyvemdm'));
         return false;
      }

      // Check remove_on_delete is boolean
      if ($value['remove_on_delete'] != '0' && $value['remove_on_delete'] != '1') {
         Session::addMessageAfterRedirect(__('The remove on delete flag must be 0 or 1', 'flyvemdm'));
         return false;
      }

      // Check the itemtype is a file
      if ($itemtype != PluginFlyvemdmFile::class) {
         Session::addMessageAfterRedirect(__('You must choose a file to apply this policy', 'flyvemdm'));
         return false;
      }

      // Check the file exists
      $file = new PluginFlyvemdmFile();
      if (!$file->getFromDB($itemId)) {
         Session::addMessageAfterRedirect(__('The file does not exists', 'flyvemdm'));
         return false;
      }

      // Check relative directory expression
      if (!strpos($value['destination'], '/../') === false || !strpos($value['destination'], '/./') === false) {
         Session::addMessageAfterRedirect(__('invalid base path', 'flyvemdm'));
         return false;
      }

      // Check double directory separator
      if (!strpos($value['destination'], '//') === false) {
         Session::addMessageAfterRedirect(__('invalid base path', 'flyvemdm'));
         return false;
      }

      // Check base path against well known paths
      $wellKnownPath = new PluginFlyvemdmWellknownpath();
      $rows = $wellKnownPath->find('1');
      $basePathIsValid = false;
      foreach ($rows as $row) {
         if (strpos($value['destination'], $row['name']) === 0) {
            // Path begins with a well known path
            if ($value['destination'] == $row['name']) {
                // ... and is the same
                $basePathIsValid = true;
                break;
            } else {
               // ... or is longer and the same followed by a /
               if (strlen($value['destination']) > strlen($row['name'])) {
                  if (substr($value['destination'], 0, strlen($row['name']) + 1) == $row['name'] . '/') {
                     $basePathIsValid = true;
                     break;
                  }
               }
            }
         }
      }
      if (!$basePathIsValid) {
         Session::addMessageAfterRedirect(__('invalid base path', 'flyvemdm'));
         return false;
      }

      return true;
   }

   /**
    * @param mixed $value
    * @param mixed $itemtype
    * @param integer $itemId
    * @return array|bool
    */
   public function getMqttMessage($value, $itemtype, $itemId) {
      $decodedValue = json_decode($value, JSON_OBJECT_AS_ARRAY);
      if (!$this->integrityCheck($decodedValue, $itemtype, $itemId)) {
         return false;
      }

      // Ensure there is a trailing slash
      if (strrpos($decodedValue['destination'], '/') != strlen($decodedValue['destination']) - 1) {
         $decodedValue['destination'] .= '/';
      }

      $file = new PluginFlyvemdmFile();
      $file->getFromDB($itemId);
      $array = [
            $this->symbol  => $decodedValue['destination'],
            'id'           => $file->getID(),
            'version'      => $file->getField('version'),
      ];

      return $array;
   }

   /**
    * @param mixed                             $value
    * @param mixed                             $itemtype
    * @param integer                           $itemId
    * @param PluginFlyvemdmNotifiableInterface $notifiable
    *
    * @return bool
    */
   public function unicityCheck($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      $notifiableType = $notifiable->getType();
      $notifiableId = $notifiable->getID();
      $task = new PluginFlyvemdmTask();
      $rows = $task->find("`itemtype_applied` = '$notifiableType'
            AND `items_id_applied` = '$notifiableId'
            AND `itemtype` = '$itemtype'");
      foreach ($rows as $row) {
         $decodedValue = json_decode($row['value'], true);
         if ($decodedValue['destination'] == $value['destination'] && $itemId == $row['items_id']) {
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
   public function conflictCheck($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      $policyData = new PluginFlyvemdmPolicy();
      if (!$policyData->getFromDBBySymbol('removeFile')) {
         Toolbox::logInFile('php-errors', 'Plugin FlyveMDM: File removal policy not found\n');
         // Give up this check
      } else {
         $policyId = $policyData->getID();
         $notifiableType = $notifiable->getType();
         $notifiableId = $notifiable->getID();
         $task = new PluginFlyvemdmTask();
         $rows = $task->find("`itemtype_applied` = '$notifiableType'
               AND `items_id_applied` = '$notifiableId'
               AND `plugin_flyvemdm_policies_id` = '$policyId'");
         foreach ($rows as $row) {
            if ($row['value'] == $value['destination']) {
               Session::addMessageAfterRedirect(__('A removal policy is applied for this file destination. Please, remove it first.', 'flyvemdm'), false, ERROR);
               return false;
            }
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
      $value = json_decode($value, JSON_OBJECT_AS_ARRAY);
      if ($this->integrityCheck($value, $itemtype, $itemId) === false) {
         return false;
      }

      if ($value['remove_on_delete'] == '0') {
         return true;
      }

      $policyData = new PluginFlyvemdmPolicy();
      if (!$policyData->getFromDBBySymbol('removeFile')) {
         Toolbox::logInFile('php-errors', 'Plugin FlyveMDM: File removal policy not found\n');
         return false;
      }

      $file = new $itemtype();
      if (!$file->getFromDB($itemId)) {
         return false;
      }

      // Ensure there is a trailing slash
      if (strrpos($value['destination'], '/') != strlen($value['destination']) - 1) {
         $value['destination'] .= '/';
      }

      $this->postUnapplyTask = [
         'itemtype_applied'            => $notifiable->getType(),
         'items_id_applied'            => $notifiable->getID(),
         'plugin_flyvemdm_policies_id' => $policyData->getID(),
         'value'                       => $value['destination'] . $file->getField('name'),
      ];

      return true;
   }

   public function post_unapply($value, $itemtype, $itemId, PluginFlyvemdmNotifiableInterface $notifiable) {
      $task = new PluginFlyvemdmTask();
      $task->add($this->postUnapplyTask);
   }

   public function showValueInput($value = '', $itemType = '', $itemId = 0) {
      $itemtype = PluginFlyvemdmFile::class;
      $removeOnDelete = 1;
      $destination_base = '';
      $destination = '';
      if ($value !== '') {
         $value = json_decode($value, JSON_OBJECT_AS_ARRAY);
         $removeOnDelete = $value['remove_on_delete'];
         $cut = strpos($value['destination'], '/');
         if ($cut === 0 || $cut === false) {
            $cut = strlen($value['destination']);
         }
         $destination = substr($value['destination'], $cut);
         $destination_base = substr($value['destination'], 0, $cut);
      }
      $path = new PluginFlyvemdmWellknownpath();
      $path->getFromDBByPath($destination_base);
      $data['destination'] = $destination;
      $data['typeTmpl'] = $itemtype;
      $data['itemtype'] = $itemtype;
      $data['dropdown'] = [
            PluginFlyvemdmFile::dropdown([
                  'display'   => false,
                  'name'      => 'items_id',
                  'value'     => $itemId,
               ]),
            PluginFlyvemdmWellknownpath::dropdown([
            'display'   => false,
            'name'      => 'destination_base',
            'value'     => $path->getID(),
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
      $file = new PluginFlyvemdmFile();
      if ($file->getFromDB($task->getField('items_id'))) {
         $path = json_decode($task->getField('value'), JSON_OBJECT_AS_ARRAY);
         $path = $path['destination'];
         $name  = $file->getField('name');
         return "$path/$name";
      }
      return NOT_AVAILABLE;
   }

   /**
    * @param array $input
    * @return array
    */
   public function preprocessFormData($input) {
      if (isset($input['destination_base']) && isset($input['value']['destination'])) {
         $basePath = new PluginFlyvemdmWellknownpath();
         if ($basePath->getFromDB(intval($input['destination_base']))) {
            $input['value']['destination'] = $basePath->getField('name') . $input['value']['destination'];
         }
      }

      return $input;
   }

   public static function getEnumSpecificStatus() {
      return [
         'waiting' => __('Waiting', 'flyvemdm'),
      ];
   }

}
