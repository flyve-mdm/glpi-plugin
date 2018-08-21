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
 *
 * @since 0.1.0
 */
class PluginFlyvemdmEntityConfig extends CommonDBTM {

   const RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT = 128;
   const RIGHT_FLYVEMDM_APP_DOWNLOAD_URL = 256;
   const RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE = 512;

   const CONFIG_DEFINED = -3;
   const CONFIG_PARENT = -2;

   /**
    * @var bool $dohistory maintain history
    */
   public $dohistory = true;

   public static $rightname = 'flyvemdm:entity';

   private $inheritableFields = [
      'support_name',
      'support_phone',
      'support_website',
      'support_email',
      'support_address',
      'download_url',
   ];

   /**
    * Returns the name of the type
    * @param integer $nb number of item in the type
    * @return string
    */
   static function getTypeName($nb = 0) {
      return _n('Entity configuration', 'Entity configurations', $nb);
   }

   /**
    *
    * @return Boolean
    */
   static function canUpdate() {

      if (static::$rightname) {
         return Session::haveRight(static::$rightname,
            PluginFlyvemdmEntityConfig::RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT
            | PluginFlyvemdmEntityConfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL
            | PluginFlyvemdmEntityConfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE
         );
      }
   }

   function getRights($interface = 'central') {
      $values = [
         READ                                       => __('Read'),
         self::RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT    => __('Write device limit'),
         self::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL      => __('Set agent download URL'),
         self::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE => __('Set invitation tiken lifetime'),
      ];

      return $values;
   }

   /**
    * Actions done after the getFromDB method
    */
   public function post_getFromDB() {
      // find the parent entity
      $entity = new Entity();
      $entity->getFromDB($this->fields['entities_id']);
      $parentEntityId = $entity->getField('entities_id');

      foreach ($this->inheritableFields as $field) {
         if (empty($this->fields[$field])) {
            $this->fields[$field] = $this->getUsedConfig($field, $parentEntityId, $field, '');
            $this->fields["_$field"] = self::CONFIG_PARENT;
         } else {
            $this->fields["_$field"] = self::CONFIG_DEFINED;
         }
      }
   }

   /**
    * @see CommonDBTM::prepareInputForAdd()
    * @param array $input
    * @return array|bool
    */
   public function prepareInputForAdd($input) {
      if (!isset($input['id'])) {
         return false;
      }
      $input['entities_id'] = $input['id'];

      return $input;
   }

   /**
    *
    * @see CommonDBTM::prepareInputForUpdate()
    * @param array $input
    * @return array|false
    */
   public function prepareInputForUpdate($input) {
      $failure = false;

      if (!Session::haveRight(static::$rightname,
         PluginFlyvemdmEntityConfig::RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT)) {
         unset($input['device_limit']);
         Session::addMessageAfterRedirect(__('You are not allowed to change the device limit', 'flyvemdm'), false, WARNING);
         $failure = true;
      }

      if (!Session::haveRight(static::$rightname,
         PluginFlyvemdmEntityConfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL)) {
         unset($input['download_url']);
         Session::addMessageAfterRedirect(__('You are not allowed to change the download URL of the MDM agent', 'flyvemdm'), false, WARNING);
         $failure = true;
      }

      if (!Session::haveRight(static::$rightname,
         PluginFlyvemdmEntityConfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE)) {
         unset($input['agent_token_life']);
         Session::addMessageAfterRedirect(__('You are not allowed to change the invitation token life', 'flyvemdm'), false, WARNING);
         $failure = true;
      }

      // If the request is done from the API and changing a field is forbidden then fail
      if (isAPI() && $failure) {
         return false;
      }

      // If a value has the same content as the parent entity, then enable inheritance
      foreach ($this->inheritableFields as $inheritableField) {
         if (isset($input[$inheritableField])) {
            if ($input[$inheritableField] == $this->fields[$inheritableField]) {
               $input[$inheritableField] = '';
            }
         }
      }

      unset($input['entities_id']);
      unset($input['enroll_token']);
      unset($input['managed']);

      $input = $this->sanitizeTokenLifeTime($input);

      return $input;
   }

   /**
    * Sanitizes the token life time of the agent
    * @param array $input
    * @return array|false the agent token life time
    */
   protected function sanitizeTokenLifeTime(array $input) {
      if (isset($input['agent_token_life'])) {
         // Sanitize agent_token_life (see DataInterval)
         if (strlen($input['agent_token_life']) < 3) {
            unset($input['agent_token_life']);
         }

         if (!($input['agent_token_life'][0] == 'P' && substr($input['agent_token_life'],
               -1) == 'D')) {
            unset($input['agent_token_life']);
         }
         $days = intval(substr($input['agent_token_life'], 1, -1));
         if ($days < 1 || $days > 180) {
            // 1 day minimum and 180 days maximum
            Session::addMessageAfterRedirect(__('The agent token life invalid or too long',
               'flyvemdm'));
            return false;
         }
      }
      return $input;
   }

   /**
    * create folders and initial setup of the entity related to MDM
    * @param CommonDBTM $item
    */
   public function hook_entity_add(CommonDBTM $item) {
      // Determine if the entity has been created by FlyveMDM
      $managed = '0';

      $config = Config::getConfigurationValues('flyvemdm', ['default_device_limit']);

      // Create entity configuration
      $entityconfig = new PluginFlyvemdmEntityConfig();
      $entityconfig->add([
         'id'           => $item->getID(),
         'managed'      => $managed,
         'enroll_token' => $entityconfig->setEnrollToken(),
         'device_limit' => $config['default_device_limit'],
      ]);

      // Create subdirectories for aplications and files
      $packagesDir = FLYVEMDM_PACKAGE_PATH . "/" . $item->getID();
      if (!is_dir($packagesDir) && !is_readable($packagesDir)) {
         if (!@mkdir($packagesDir, 0770, true)) {
            Toolbox::logInFile("php-errors", "Could not create directory $packagesDir");
            // TODO : handle error here
         }
      }

      $filesDir = FLYVEMDM_FILE_PATH . "/" . $item->getID();
      if (!is_dir($filesDir) && !is_readable($filesDir)) {
         if (!@mkdir($filesDir, 0770, true)) {
            Toolbox::logInFile("php-errors", "Could not create directory $filesDir");
            // TODO : handle error here
         }
      }
   }

   /**
    * Cleanup MDM related data for the entity being deleted
    * @param CommonDBTM $item
    */
   public function hook_entity_purge(CommonDBTM $item) {
      $entityConfig = new static();
      $entityConfig->deleteByCriteria(['entities_id' => $item->getField('id')], 1);

      // Delete folders for the entity
      PluginFlyvemdmCommon::recursiveRmdir(FLYVEMDM_PACKAGE_PATH . "/" . $item->getID());
      PluginFlyvemdmCommon::recursiveRmdir(FLYVEMDM_FILE_PATH . "/" . $item->getID());
   }

   /**
    * Generate a displayable token for enrollment
    */
   protected function setEnrollToken() {
      return bin2hex(openssl_random_pseudo_bytes(32));
   }

   /**
    * Retrieve the entity or create it
    * @param string $ID
    * @return boolean true if succeed
    */
   public function getFromDBOrCreate($ID) {
      if (!$this->getFromDB($ID)) {
         $config = Config::getConfigurationValues('flyvemdm', ['default_device_limit']);

         $this->add([
            'id'           => $ID,
            'enroll_token' => '',
            'device_limit' => $config['default_device_limit'],
         ]);
         return true;
      } else {
         return true;
      }
   }

   /**
    * Gets the tabs name
    * @param CommonGLPI $item
    * @param integer $withtemplate
    * @return array Containing the tabs name
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $tabNames = [];
      if (!$withtemplate) {
         if ($item->getType() == 'Entity') {
            $tabNames[1] = __('Flyve MDM supervision', 'flyvemdm');
         }
      }
      return $tabNames;
   }

   /**
    * Retrieve data of current entity or parent entity
    *
    * @since version 0.84 (before in entitydata.class)
    *
    * @param $fieldref        string   name of the referent field to know if we look at parent entity
    * @param $entities_id
    * @param $fieldval        string   name of the field that we want value (default '')
    * @param integer $default_value value to return (default -2)
    *
    * @return integer
    */
   static function getUsedConfig($fieldref, $entities_id, $fieldval = '', $default_value = -2) {

      // for calendar
      if (empty($fieldval)) {
         $fieldval = $fieldref;
      }

      $entity = new Entity();
      $entityConfig = new self();
      // Search in entity data of the current entity
      if ($entity->getFromDB($entities_id)) {
         // Value is defined : use it
         if ($entityConfig->getFromDB($entities_id)) {
            if (is_numeric($default_value)
               && ($entityConfig->fields[$fieldref] != self::CONFIG_PARENT)) {
               return $entityConfig->fields[$fieldval];
            }
            if (!is_numeric($default_value)) {
               return $entityConfig->fields[$fieldval];
            }
         }
      }

      // Entity data not found or not defined : search in parent one
      if ($entities_id > 0) {
         if ($entity->getFromDB($entities_id)) {
            $ret = self::getUsedConfig($fieldref, $entity->fields['entities_id'], $fieldval,
               $default_value);
            return $ret;
         }
      }

      return $default_value;
   }

   /**
    * Shows the tab content
    * @param CommonGLPI $item
    * @param integer $tabnum
    * @param integer $withtemplate
    * @return bool|void
    */
   public static function displayTabContentForItem(
      CommonGLPI $item,
      $tabnum = 1,
      $withtemplate = 0
   ) {
      if ($item->getType() == 'Entity') {
         $config = new self();
         $config->showFormForEntity($item);
      }
   }

   /**
    * is the parameter ID must be considered as new one ?
    *
    * @param integer $ID ID of the item (-1 if new item)
    *
    * @return boolean
    **/
   static function isNewID($ID) {
      return (($ID < 0) || !strlen($ID));
   }

   /**
    * Displays form when the item is displayed from a related entity
    * @param Entity $item
    */
   public function showFormForEntity(Entity $item) {
      $ID = $item->fields['id'];
      if (!$this->getFromDBByCrit(['entities_id' => $ID])) {
         $this->add(['id' => $ID]);
         // To set virtual fields about inheritance
         $this->post_getFromDB();
      }
      $this->initForm($ID);
      $this->showFormHeader(['formtitle' => __('Helpdesk information', 'flyvemdm')]);
      $canedit = static::canUpdate();

      $fields = $this->fields;

      $data = [
         'canEdit'      => $canedit,
         'entityConfig' => $fields,
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('entity_entityconfig.html.twig', $data);

      $item->showFormButtons(['candel' => false, 'formfooter' => false]);
   }

   /**
    * Can the entity get one more agent ?
    * Checks if the device count limit is reached
    *
    * @return boolean true if the limit not reached, false otherwise
    */
   public function canAddAgent($entityId) {
      if ($this->isNewItem()) {
         return false;
      }

      $maxAgents = $this->fields['device_limit'];
      $DbUtils = new DbUtils();
      $deviceCount = $DbUtils->countElementsInTable(PluginFlyvemdmAgent::getTable(), ['entities_id' => $entityId]);
      if ($maxAgents > 0 && $deviceCount >= $maxAgents) {
         // Too many devices
         return false;
      }

      return  true;
   }

   /**
    * @return array
    */
   public function getSearchOptionsNew() {
      return $this->rawSearchOptions();
   }

   public function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => __s('Invitation', 'flyvemdm'),
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
         'table'         => $this->getTable(),
         'field'         => 'enroll_token',
         'name'          => __('Entity enroll token'),
         'massiveaction' => false,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '4',
         'table'         => $this->getTable(),
         'field'         => 'agent_token_life',
         'name'          => __('Invitation token lifetime'),
         'massiveaction' => false,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => $this->getTable(),
         'field'         => 'download_url',
         'name'          => __('dowlnoad URL'),
         'massiveaction' => false,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '6',
         'table'         => $this->getTable(),
         'field'         => 'device_limit',
         'name'          => __('Device limit'),
         'massiveaction' => false,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '7',
         'table'         => $this->getTable(),
         'field'         => 'support_phone',
         'name'          => __('Support phone'),
         'massiveaction' => false,
         'nosearch'      => true,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '8',
         'table'         => $this->getTable(),
         'field'         => 'support_website',
         'name'          => __('Support website'),
         'massiveaction' => false,
         'nosearch'      => true,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '9',
         'table'         => $this->getTable(),
         'field'         => 'support_email',
         'name'          => __('Support email'),
         'massiveaction' => false,
         'nosearch'      => true,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '10',
         'table'         => $this->getTable(),
         'field'         => 'support_address',
         'name'          => __('Support address'),
         'massiveaction' => false,
         'nosearch'      => true,
         'datatype'      => 'text',
      ];

      $tab[] = [
         'id'            => '11',
         'table'         => $this->getTable(),
         'field'         => 'entities_id',
         'name'          => __('Entity'),
         'massiveaction' => false,
         'datatype'      => 'dropdown',
      ];

      return $tab;
   }
}
