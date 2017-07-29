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
 *
 * @since 0.1.0
 */
class PluginFlyvemdmEntityconfig extends CommonDBTM {

   const RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT      = 128;
   const RIGHT_FLYVEMDM_APP_DOWNLOAD_URL        = 256;
   const RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE   = 512;

   const CONFIG_DEFINED                         = -3;
   const CONFIG_PARENT                          = -2;

   /**
    * @var bool $dohistory maintain history
    */
   public $dohistory                   = true;

   public static $rightname            = 'flyvemdm:entity';

   static function getTypeName($nb=0) {
      return _n('Entity configuration', 'Entity configurations', $nb);
   }

   /**
    *
    * @return Boolean
    */
   static function canUpdate() {

      if (static::$rightname) {
         return Session::haveRight(static::$rightname, PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT
               | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL
               | PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE
         );
      }
   }

   public function post_getFromDB() {
      // find the parent entity
      $entity = new Entity();
      $entity->getFromDB($this->fields['entities_id']);
      $parentEntityId = $entity->getField('entities_id');

      $fieldsToRecurse = [
            'support_name'    => '',
            'support_phone'   => '',
            'support_website' => '',
            'support_email'   => '',
            'support_address' => '',
      ];
      foreach ($fieldsToRecurse as $field => $default) {
         if (empty($this->fields[$field])) {
            $this->fields[$field] = $this->getUsedConfig($field, $parentEntityId, $field, $default);
            $this->fields["_$field"] = self::CONFIG_PARENT;
         } else {
            $this->fields["_$field"] = self::CONFIG_DEFINED;
         }
      }
   }

   /**
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      global $DB;

      if (!isset($input['id'])) {
         return false;
      }
      if (!isset($input['download_url'])) {
         $input['download_url'] = PLUGIN_FLYVEMDM_AGENT_DOWNLOAD_URL;
      }
      $input['entities_id'] = $input['id'];

      return $input;
   }

   /**
    *
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      if (!Session::haveRight(static::$rightname, PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT)) {
         unset($input['device_limit']);
      }

      if (!Session::haveRight(static::$rightname, PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL)) {
         unset($input['download_url']);
      }

      if (!Session::haveRight(static::$rightname, PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE)) {
         unset($input['agent_token_life']);
      }

      unset($input['entities_id']);
      unset($input['enroll_token']);
      unset($input['managed']);

      $input = $this->sanitizeTokenLifeTime($input);

      return $input;
   }

   protected function sanitizeTokenLifeTime($input) {
      if (isset($input['agent_token_life'])) {
         // Sanitize agent_token_life (see DataInterval)
         if (strlen($input['agent_token_life']) < 3) {
            unset($input['agent_token_life']);
         }

         if (! ($input['agent_token_life'][0] == 'P' && substr($input['agent_token_life'], -1) == 'D')) {
            unset($input['agent_token_life']);
         }
         $days = intval(substr($input['agent_token_life'], 1, -1));
         if ($days < 1 || $days > 180) {
            // 1 day minimum and 180 days maximum
            Session::addMessageAfterRedirect(__('The agent token life invalid or too long', 'flyvemdm'));
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
      $managed = ( $item instanceof PluginFlyvemdmEntity ) ? '1' : '0';

      $config = Config::getConfigurationValues('flyvemdm', array('default_device_limit'));

      // Create entity configuration
      $entityconfig = new PluginFlyvemdmEntityconfig();
      $entityconfig->add([
            'id'           => $item->getID(),
            'managed'      => $managed,
            'enroll_token' => $entityconfig->setEnrollToken(),
            'device_limit' => $config['default_device_limit'],
      ]);

      // Create subdirectories for aplications and files
      $packagesDir = FLYVEMDM_PACKAGE_PATH . "/" . $item->getID();
      if (!is_dir($packagesDir) && !is_readable($packagesDir)) {
         if (! @mkdir($packagesDir, 0770, true)) {
            Toolbox::logInFile("php-errors", "Could not create directory $packagesDir");
            // TODO : handle error here
         }
      }

      $filesDir = FLYVEMDM_FILE_PATH . "/" . $item->getID();
      if (!is_dir($filesDir) && !is_readable($filesDir)) {
         if (! @mkdir($filesDir, 0770, true)) {
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
      $entityConfig->deleteByCriteria(array('entities_id' => $item->getField('id')), 1);

      // Delete folders for the entity
      PluginFlyvemdmToolbox::recursiveRmdir(FLYVEMDM_PACKAGE_PATH . "/" . $item->getID());
      PluginFlyvemdmToolbox::recursiveRmdir(FLYVEMDM_FILE_PATH . "/" . $item->getID());
   }

   /**
    * Generate a displayable token for enrollment
    */
   protected function setEnrollToken() {
      return bin2hex(openssl_random_pseudo_bytes(32));
   }

   public function getFromDBOrCreate($ID) {
      if (!$this->getFromDB($ID)) {
         $config = Config::getConfigurationValues('flyvemdm', array('default_device_limit'));

         $this->add([
               'id'              => $ID,
               'enroll_token'    => '',
               'device_limit' => $config['default_device_limit'],
         ]);
         return true;
      } else {
         return true;
      }
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $tabNames = array();
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
    * @param $default_value            value to return (default -2)
    **/
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

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

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

   public function showFormForEntity(Entity $item) {
      $ID = $item->fields['id'];
      if (!$this->getFromDBByCrit(['entities_id' => $ID])) {
         $this->add([
               'id'                 => $ID,
               'support_name'       => self::CONFIG_PARENT,
               'support_phone'      => self::CONFIG_PARENT,
               'support_website'    => self::CONFIG_PARENT,
               'support_email'      => self::CONFIG_PARENT,
               'support_address'    => self::CONFIG_PARENT,
         ]);
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
      echo $twig->render('entity_entityconfig.html', $data);

      $item->showFormButtons(array('candel' => false, 'formfooter' => false));
   }


   /**
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      $tab = array();
      $tab['common']                 = __s('Invitation', "flyvemdm");

      $i = 2;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'id';
      $tab[$i]['name']                = __('ID');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'number';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'enroll_token';
      $tab[$i]['name']                = __('Entity enroll token', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'agent_token_life';
      $tab[$i]['name']                = __('Invitation token lifetime', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'download_url';
      $tab[$i]['name']                = __('dowlnoad URL', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'device_limit';
      $tab[$i]['name']                = __('Device limit', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'support_phone';
      $tab[$i]['name']                = __('Support phone', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['nosearch']            = true;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'support_website';
      $tab[$i]['name']                = __('Support website', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['nosearch']            = true;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'support_email';
      $tab[$i]['name']                = __('Support email', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['nosearch']            = true;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'support_address';
      $tab[$i]['name']                = __('Support address', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['nosearch']            = true;
      $tab[$i]['datatype']            = 'text';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'entities_id';
      $tab[$i]['name']                = __('Entity');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'dropdown';

      return $tab;
   }

}
