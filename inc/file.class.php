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
 * @since 0.1.30
 */
class PluginStorkmdmFile extends CommonDBTM {

   // name of the right in DB
   static $rightname                   = 'storkmdm:file';

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85)
    */
   protected $usenotepad               = true;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi >=0.85)
    */
   protected $usenotepadRights         = true;

   /**
    * Localized name of the type
    * @param integer $nb  number of item in the type (default 0)
    */
   public static function getTypeName($nb=0) {
      global $LANG;

      return _n('File', 'Files', $nb, "storkmdm");
   }

   /**
    *
    * {@inheritDoc}
    * @see CommonDBTM::addNeededInfoToInput()
    */
   public function addNeededInfoToInput($input) {
      global $DB;

      $input['entities_id'] = $_SESSION['glpiactive_entity'];

      return $input;
   }

   /**
    * (non-PHPdoc)
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      if (!isset($_FILES['file'])) {
         Session::addMessageAfterRedirect(__('No file uploaded', "storkmdm"));
         return false;
      }

      if (isset ($_FILES['file']['error']) && !$_FILES['file']['error'] == 0) {
         if (!$_FILES['file']['error'] == 4) {
            Session::addMessageAfterRedirect(__('File upload failed', "storkmdm"));
         }
         return false;
      }

      $input['entities_id'] = $_SESSION['glpiactive_entity'];

      $destination = STORKMDM_FILE_PATH . "/" . $input['entities_id'] . "/" . uniqid() . "_" . basename($_FILES['file']['name']);
      if (!$this->saveUploadedFile($_FILES['file'], $destination)) {
         if (!is_writable(dirname($destination))) {
            $destination = dirname($destination);
            Toolbox::logInFile('php-errors', "Plugin Storkmdm : Directory '$destination' is not writeable");
         }
         return false;
      }
      $input['source'] = $input['entities_id'] . "/" . basename($destination);

      // File added, then this is the first version
      $input['version'] = '1';

      return $input;
   }

   /**
    * (non-PHPdoc)
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      if (isset ($_FILES['file']['error']) && !$_FILES['file']['error'] == 0) {
         if (!$_FILES['file']['error'] == 4) {
            Session::addMessageAfterRedirect(__('File upload failed', "storkmdm"));
         }
         return false;
      }

      unset($input['entities_id']);

      if (isset($_FILES['file'])) {
         // A file has been uploaded
         $input['source'] = $this->fields['entities_id'] . "/" . uniqid() . "_" . basename($_FILES['file']['name']);
         $destination = STORKMDM_FILE_PATH . "/" . $input['entities_id'] . "/" . uniqid() . "_" . basename($_FILES['file']['name']);
         if (!$this->saveUploadedFile($_FILES['file'], $destination)) {
            if (!is_writable(dirname($destination))) {
               $destination = dirname($destination);
               Toolbox::logInFile('php-errors', "Plugin Storkmdm : Directory '$destination' is not writeable");
            }
            return false;
         }
         unlink(STORKMDM_FILE_PATH . "/" . $this->fields['source']);
         $input['source']      = $input['entities_id'] . "/" . basename($destination);
      } else {
         // No file uploaded
         unset($input['source']);
      }

      // File updated, then increment its version
      $input['version'] = $this->fields['version']++;

      return $input;
   }

   /**
    * move and rename the uploaded file
    * @return canonical saved filename, '' if an error occured
    */
   public function saveUploadedFile($source, $destination) {
      global $CFG_GLPI;

      $success = false;

      $fileExtension = pathinfo($source['name'], PATHINFO_EXTENSION);
      if (false && ! in_array($fileExtension, array("txt", "pdf"))) {
         $success = false;
      } else {
         $this->createEntityDirectory(dirname($destination));
         if  (!move_uploaded_file($source['tmp_name'], $destination)) {
            Session::addMessageAfterRedirect(__('Could not save file', "storkmdm"));
            $success = false;
         } else {
            Session::addMessageAfterRedirect(__('File sucessfully uploaded', "storkmdm"));
            $success = true;
         }
      }

      return $success;
   }

   /**
    * get the URL to download the file
    * @return string|boolean URL of the file
    */
   public function getFileURL() {
      $config = Config::getConfigurationValues('storkmdm', array('deploy_base_url'));
      $deployBaseURL = $config['deploy_base_url'];
      if ($deployBaseURL === null) {
         return false;
      }

      $URL = $deployBaseURL . '/file/' . $this->fields['source'];
      return $URL;
   }

   /**
    * Create a directory
    * @param string $dir
    */
   protected function createEntityDirectory($dir) {
      if (!is_dir($dir)) {
         @mkdir($dir, 0770, false);
      }
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      global $CFG_GLPI;

      $tab = array();
      $tab['common']                 = __s('File', "storkmdm");

      $tab[1]['table']               = self::getTable();
      $tab[1]['field']               = 'name';
      $tab[1]['name']                = __('Name');
      $tab[1]['datatype']            = 'itemlink';
      $tab[1]['massiveaction']       = false;

      $tab[2]['table']               = self::getTable();
      $tab[2]['field']               = 'id';
      $tab[2]['name']                = __('ID');
      $tab[2]['massiveaction']       = false;
      $tab[2]['datatype']            = 'number';

      $tab[3]['table']               = self::getTable();
      $tab[3]['field']               = 'source';
      $tab[3]['name']                = __('Source', 'storkmdm');
      $tab[3]['datatype']            = 'string';
      $tab[3]['massiveaction']       = false;

      return $tab;
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::pre_deleteItem()
    */
   public function pre_deleteItem() {
      global $DB;

      $fleet_policy = new PluginStorkmdmFleet_Policy();
      return $fleet_policy->deleteByCriteria(array(
            'itemtype'  => $this->getType(),
            'items_id'  => $this->getID()
      ));
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::post_addItem()
    */
   public function post_addItem() {
      global $DB;
   }

   public function post_getFromDB() {
      // Check the user can view this itemtype and can view this item
      if ($this->canView() && $this->canViewItem()) {
         if (isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == 'application/octet-stream'
               || isset($_GET['alt']) && $_GET['alt'] == 'media') {
            $this->sendFile(); // and terminate script
         }
      }
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::post_updateItem()
    */
   public function post_updateItem($history = 1) {
      // Check if the source changed
      if (isset($this->oldvalues['source'])) {
         $itemtype = $this->getType();
         $itemId = $this->getID();

         $fleet_policy = new PluginStorkmdmFleet_Policy();
         $fleet_policyCol = $fleet_policy->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
         $fleet = new PluginStorkmdmFleet();
         $policyFactory = new PluginStorkmdmPolicyFactory();
         foreach ($fleet_policyCol as $fleet_policyId => $fleet_policyRow) {
            $fleetId = $fleet_policyRow['plugin_storkmdm_fleets_id'];
            if ($fleet->getFromDB($fleetId)) {
               Toolbox::logInFile('php-errors', "Plugin Storkmdm : Could not find fleet id = '$fleetId'");
               continue;
            }
            $policy = $policyFactory->createFromDBByID($fleet_policyRow['plugin_storkmdm_policies_id']);
            if ($fleet_policy->getFromDB($fleet_policyId)) {
               //$fleet_policy->publishPolicies($fleet, $policy->getGroup());
               $fleet_policy->updateQueue($fleet, $policy->getGroup());
            }
         }
      }
   }

   /**
    * {@inheritDoc}
    * @see CommonDBTM::post_purgeItem()
    */
   public function post_purgeItem() {
      $filename = STORKMDM_FILE_PATH . "/" . $this->fields['source'];
      if (is_writeable($filename)) {
         unlink($filename);
      }
   }

   protected function sendFile() {
      $streamSource = STORKMDM_FILE_PATH . "/" . $this->fields['source'];

      // Ensure the file exists
      if (!file_exists($streamSource) || !is_file($streamSource)) {
         header("HTTP/1.0 404 Not Found");
         exit(0);
      }

      // Download range defaults to the full file
      // get file metadata
      $size = filesize($streamSource);
      $begin = 0;
      $end = $size - 1;
      $mimeType = 'application/octet-stream';
      $time = date('r', filemtime($streamSource));

      // Open the file
      $fileHandle = @fopen($streamSource, 'rb');
      if (!$fileHandle) {
         header ("HTTP/1.0 500 Internal Server Error");
         exit(0);
      }

      // set range if specified by the client
      if (isset($_SERVER['HTTP_RANGE'])) {
         if (preg_match('/bytes=\h*(\d+)?-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
            if (!empty($matches[1])) {
               $begin = intval($matches[1]);
            }
            if (!empty($matches[2])) {
               $end = min(intval($matches[2]), $end);
            }
         }
      }

      // seek to the begining of the range
      $currentPosition = $begin;
      if (fseek($fileHandle, $begin, SEEK_SET) < 0) {
         header("HTTP/1.0 500 Internal Server Error");
         exit(0);
      }

      // send headers to ensure the client is able to detect a corrupted download
      // example : less bytes than the expected range
      // send meta data
      // setup client's cache behavior
      header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
      header('Pragma: private'); /// IE BUG + SSL
      header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
      header("Content-disposition: attachment; filename=\"" . $this->fields['name'] . "\"");
      header("Content-type: $mimeType");
      header("Last-Modified: $time");
      header('Accept-Ranges: bytes');
      header('Content-Length:' . ($end - $begin + 1));
      header("Content-Range: bytes $begin-$end/$size");
      header("Content-Transfer-Encoding: binary\n");
      header('Connection: close');

      // Prepare HTTP response
      if ($begin > 0 || $end < $size - 1) {
         header('HTTP/1.0 206 Partial Content');
      } else {
         header('HTTP/1.0 200 OK');
      }

      // Sends bytes until the end of the range or connection closed
      while (!feof($fileHandle) && $currentPosition < $end && (connection_status() == 0)) {
         // allow a few seconds to send a few KB.
         set_time_limit(10);
         $content = fread($fileHandle, min(1024 * 16, $end - $currentPosition + 1));
         if ($content === false) {
            header("HTTP/1.0 500 Internal Server Error", true); // Replace previously sent headers
            exit(0);
         } else {
            print $content;
         }
         flush();
         $currentPosition += 1024 * 16;
      }

      // Endnow to prevent any unwanted bytes
      exit(0);
   }

}
