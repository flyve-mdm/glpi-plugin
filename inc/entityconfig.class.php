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
 *
 * @since 0.1.0
 */
class PluginStorkmdmEntityconfig extends CommonDBTM {

   const RIGHT_STORKMDM_DEVICE_COUNT_LIMIT      = 128;
   const RIGHT_STORKMDM_APP_DOWNLOAD_URL        = 256;
   const RIGHT_STORKMDM_INVITATION_TOKEN_LIFE   = 512;
   /**
    * @var bool $dohistory maintain history
    */
   public $dohistory                   = true;

   public static $rightname            = 'storkmdm:entity';

   static function getTypeName($nb=0) {
      return _n('Entity configuration', 'Entity configurations', $nb);
   }

   /**
    *
    * @return Boolean
    */
   static function canUpdate() {

      if (static::$rightname) {
         return Session::haveRight(static::$rightname, PluginStorkmdmEntityconfig::RIGHT_STORKMDM_DEVICE_COUNT_LIMIT
               | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_APP_DOWNLOAD_URL
               | PluginStorkmdmEntityconfig::RIGHT_STORKMDM_INVITATION_TOKEN_LIFE
         );
      }
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      global $DB;

      if (!isset($input['id'])) {
         return false;
      }
      if (!isset($input['download_url'])) {
         $input['download_url'] = addslashes(PLUGIN_STORKMDM_AGENT_DOWNLOAD_URL);
      }
      $input['entities_id'] = $input['id'];

      return $input;
   }

   /**
    *
    * {@inheritDoc}
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      if (!Session::haveRight(static::$rightname, PluginStorkmdmEntityconfig::RIGHT_STORKMDM_DEVICE_COUNT_LIMIT)) {
         unset($input['device_limit']);
      }

      if (!Session::haveRight(static::$rightname, PluginStorkmdmEntityconfig::RIGHT_STORKMDM_APP_DOWNLOAD_URL)) {
         unset($input['download_url']);
      }

      if (!Session::haveRight(static::$rightname, PluginStorkmdmEntityconfig::RIGHT_STORKMDM_INVITATION_TOKEN_LIFE)) {
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
            Session::addMessageAfterRedirect(__('The agent token life invalid or too long', 'storkmdm'));
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
      // Determine if the entity has been created by StorkMDM
      $managed = ( $item instanceof PluginStorkmdmEntity ) ? '1' : '0';

      $config = Config::getConfigurationValues('storkmdm', array('default_device_limit'));

      // Create entity configuration
      $entityconfig = new PluginStorkmdmEntityconfig();
      $entityconfig->add([
            'id'           => $item->getID(),
            'managed'      => $managed,
            'enroll_token' => $entityconfig->setEnrollToken(),
            'device_limit' => $config['default_device_limit'],
      ]);

      // Create subdirectories for aplications and files
      $packagesDir = STORKMDM_PACKAGE_PATH . "/" . $item->getID();
      if (!is_dir($packagesDir) && !is_readable($packagesDir)) {
         if (! @mkdir($packagesDir, 0770, true)) {
            Toolbox::logInFile("php-errors", "Could not create directory $packagesDir");
            // TODO : handle error here
         }
      }

      $filesDir = STORKMDM_FILE_PATH . "/" . $item->getID();
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
      if (! PluginStorkmdmToolbox::recursiveRmdir(STORKMDM_PACKAGE_PATH . "/" . $item->getID())) {
         // TODO : handle error here
      }
      if (! PluginStorkmdmToolbox::recursiveRmdir(STORKMDM_FILE_PATH . "/" . $item->getID())) {
         // TODO : handle error here
      }
   }

   /**
    * Generate a displayable token for enrollment
    */
   protected function setEnrollToken() {
      return bin2hex(openssl_random_pseudo_bytes(32));
   }
   
   public function getFromDBOrCreate($ID) {
      if (!$this->getFromDB($ID)) {
         $config = Config::getConfigurationValues('storkmdm', array('default_device_limit'));

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

   /**
    * {@inheritDoc}
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      global $CFG_GLPI;

      $tab = array();
      $tab['common']                 = __s('Invitation', "storkmdm");

      $i = 2;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'id';
      $tab[$i]['name']                = __('ID');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'number';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'enroll_token';
      $tab[$i]['name']                = __('Entity enroll token', 'storkmdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'agent_token_life';
      $tab[$i]['name']                = __('Invitation token lifetime', 'storkmdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'download_url';
      $tab[$i]['name']                = __('dowlnoad URL', 'storkmdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'device_limit';
      $tab[$i]['name']                = __('Device limit', 'storkmdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      return $tab;
   }

}
