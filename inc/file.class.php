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
 * @since 0.1.30
 */
class PluginFlyvemdmFile extends CommonDBTM {

   // name of the right in DB
   static $rightname                   = 'flyvemdm:file';

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
      return _n('File', 'Files', $nb, "flyvemdm");
   }

   /**
    * Returns the picture file for the menu
    * @return string the menu picture
    */
   public static function getMenuPicture() {
      return '../pics/picto-file.png';
   }

   /**
    *
    * @see CommonDBTM::addNeededInfoToInput()
    */
   public function addNeededInfoToInput($input) {
      $input['entities_id'] = $_SESSION['glpiactive_entity'];

      return $input;
   }

   /**
    * (non-PHPdoc)
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      // Find the added file
      if (isset($_POST['_file'][0]) && is_string($_POST['_file'][0])) {
         // from GLPI UI
         $actualFilename = $_POST['_file'][0];
         $uploadedFile = GLPI_TMP_DIR."/".$_POST['_file'][0];
      } else {
         // from API
         if (!isset($_FILES['file'])) {
            Session::addMessageAfterRedirect(__('No file uploaded', "flyvemdm"));
            return false;
         }

         if (!$_FILES['file']['error'] == 0) {
            if (!$_FILES['file']['error'] == 4) {
               Session::addMessageAfterRedirect(__('File upload failed', "flyvemdm"));
            }
            return false;
         }

         $destination = GLPI_TMP_DIR . '/' . $_FILES['file']['name'];
         if (is_readable($_FILES['file']['tmp_name']) && !is_readable($destination)) {
            // Move the file to GLPI_TMP_DIR
            if (!is_dir(GLPI_TMP_DIR)) {
               Session::addMessageAfterRedirect(__("Temp directory doesn't exist"), false, ERROR);
               return false;
            }

            // With GLPI < 9.2, the file was not moved by the API
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
               return false;
            }
         }

         $actualFilename = $_FILES['file']['name'];
         $uploadedFile = $destination;
      }

      if (!isset($input['entities_id'])) {
         $input['entities_id'] = $_SESSION['glpiactive_entity'];
      }

      if (!isset($actualFilename)) {
         Session::addMessageAfterRedirect(__('File uploaded without name', "flyvemdm"));
         return false;
      }
      $input['source'] = $input['entities_id'] . "/" . uniqid() . "_" . basename($uploadedFile);
      $destination = FLYVEMDM_FILE_PATH . "/" . $input['source'];
      $this->createEntityDirectory(dirname($destination));
      if (!rename($uploadedFile, $destination)) {
         if (!is_writable(dirname($destination))) {
            $destination = dirname($destination);
            Toolbox::logInFile('php-errors', "Plugin Flyvemdm : Directory '$destination' is not writeable");
         }
         Session::addMessageAfterRedirect(__('Failed to store the uploaded file', "flyvemdm"));
         return false;
      }

      if (!isset($input['name']) || empty($input['name'])) {
         $input['name'] = $actualFilename;
      }

      // File added, then this is the first version
      $input['version'] = '1';

      return $input;
   }

   /**
    * (non-PHPdoc)
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      // Find the added file
      if (isset($_POST['_file'][0]) && is_string($_POST['_file'][0])) {
         // from GLPI UI
         $actualFilename = $_POST['_file'][0];
         $uploadedFile = GLPI_TMP_DIR."/".$_POST['_file'][0];
      } else {
         // from API
         if (isset($_FILES['file']['error'])) {
            if (!$_FILES['file']['error'] == 0) {
               if (!$_FILES['file']['error'] == 4) {
                  Session::addMessageAfterRedirect(__('File upload failed', "flyvemdm"));
               }
               return false;
            }

            $destination = GLPI_TMP_DIR . '/' . $_FILES['file']['name'];
            if (is_readable($_FILES['file']['tmp_name']) && !is_readable($destination)) {
               // Move the file to GLPI_TMP_DIR
               if (!is_dir(GLPI_TMP_DIR)) {
                  Session::addMessageAfterRedirect(__("Temp directory doesn't exist"), false, ERROR);
                  return false;
               }

               // With GLPI < 9.2, the file was not moved by the API
               if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
                  return false;
               }
            }

            $actualFilename = $_FILES['file']['name'];
            $uploadedFile = $destination;
         }
      }

      unset($input['entities_id']);

      if (isset($uploadedFile)) {
         // A file has been uploaded
         if (!isset($actualFilename)) {
            Session::addMessageAfterRedirect(__('File uploaded without name', "flyvemdm"));
            return false;
         }
         $input['source'] = $this->fields['entities_id'] . "/" . uniqid() . "_" . basename($uploadedFile);
         $destination = FLYVEMDM_FILE_PATH . "/" . $input['source'];
         $filename = pathinfo($actualFilename, PATHINFO_FILENAME);
         if (!rename($uploadedFile, $destination)) {
            if (!is_writable(dirname($destination))) {
               $destination = dirname($destination);
               Toolbox::logInFile('php-errors', "Plugin Flyvemdm : Directory '$destination' is not writeable");
            }
            return false;
         }
         if ($filename != $this->fields['source']) {
            unlink(FLYVEMDM_FILE_PATH . "/" . $this->fields['source']);
         }
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
      $success = false;

      $fileExtension = pathinfo($source['name'], PATHINFO_EXTENSION);
      if (false && ! in_array($fileExtension, array("txt", "pdf"))) {
         $success = false;
      } else {
         $this->createEntityDirectory(dirname($destination));
         if (!move_uploaded_file($source['tmp_name'], $destination)) {
            Session::addMessageAfterRedirect(__('Could not save file', "flyvemdm"));
            $success = false;
         } else {
            Session::addMessageAfterRedirect(__('File sucessfully uploaded', "flyvemdm"));
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
      $config = Config::getConfigurationValues('flyvemdm', array('deploy_base_url'));
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
         @mkdir($dir, 0770, true);
      }
   }

   /**
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      $tab = array();
      $tab['common']                 = __s('File', "flyvemdm");

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
      $tab[3]['name']                = __('Source', 'flyvemdm');
      $tab[3]['datatype']            = 'string';
      $tab[3]['massiveaction']       = false;

      return $tab;
   }

   /**
    * @see CommonDBTM::pre_deleteItem()
    */
   public function pre_deleteItem() {
      $task = new PluginFlyvemdmTask();
      return $task->deleteByCriteria(array(
            'itemtype'  => $this->getType(),
            'items_id'  => $this->getID()
      ));
   }

   public function post_addItem() {
      global $DB;
   }

   /**
    * Actions done after the getFromFB function
    */
   public function post_getFromDB() {
      // Check the user can view this itemtype and can view this item
      if ($this->canView() && $this->canViewItem()) {
         if (isAPI()
             && (isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == 'application/octet-stream'
                 || isset($_GET['alt']) && $_GET['alt'] == 'media')) {
            $this->sendFile(); // and terminate script
         }
      }
   }

   /**
    * @see CommonDBTM::post_updateItem()
    */
   public function post_updateItem($history = 1) {
      // Check if the source changed
      if (isset($this->oldvalues['source'])) {
         $itemtype = $this->getType();
         $itemId = $this->getID();

         $task = new PluginFlyvemdmTask();
         $taskCol = $task->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
         $fleet = new PluginFlyvemdmFleet();
         $policyFactory = new PluginFlyvemdmPolicyFactory();
         foreach ($taskCol as $taskId => $taskRow) {
            $fleetId = $taskRow['plugin_flyvemdm_fleets_id'];
            if ($fleet->getFromDB($fleetId)) {
               Toolbox::logInFile('php-errors', "Plugin Flyvemdm : Could not find fleet id = '$fleetId'");
               continue;
            }
            $policy = $policyFactory->createFromDBByID($taskRow['plugin_flyvemdm_policies_id']);
            if ($task->getFromDB($taskId)) {
               $task->updateQueue($fleet, $policy->getGroup());
            }
         }
      }
   }

   /**
    * @see CommonDBTM::post_purgeItem()
    */
   public function post_purgeItem() {
      $filename = FLYVEMDM_FILE_PATH . "/" . $this->fields['source'];
      if (is_writeable($filename)) {
         unlink($filename);
      }
   }

   /**
    * Sends a file
    */
   protected function sendFile() {
      $streamSource = FLYVEMDM_FILE_PATH . "/" . $this->fields['source'];

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
         $matches = null;
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

   /**
    * Deletes files related to the entity being purged
    * @param CommonDBTM $item
    */
   public function hook_entity_purge(CommonDBTM $item) {
      $file = new static();
      $file->deleteByCriteria(array('entities_id' => $item->getField('id')), 1);
   }

   /**
    * Display a form to view, create or edit
    * @param integer $ID ID of the item to show
    * @param array $options
    */
   public function showForm($ID, $options = array()) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $twig = plugin_flyvemdm_getTemplateEngine();
      $fields              = $this->fields;
      $objectName          = autoName($this->fields["name"], "name",
            (isset($options['withtemplate']) && $options['withtemplate'] == 2),
            $this->getType(), -1);
      if ($this->isNewID($ID)) {
         $fields['filesize'] = '';
      } else {
         $fields['filesize'] = fileSize(FLYVEMDM_FILE_PATH . '/' . $fields['source']);
         $fields['filesize'] = PluginFlyvemdmCommon::convertToGiB($fields['filesize']);
      }
      $data = [
            'withTemplate' => (isset($options['withtemplate']) && $options['withtemplate'] ? "*" : ""),
            'canUpdate'    => (!$this->isNewID($ID)) && ($this->canUpdate() > 0) || $this->isNewID($ID),
            'isNewID'      => $this->isNewID($ID),
            'file'         => $fields,
            'upload'       => Html::file(['name' => 'file', 'display' => false]),
      ];
      echo $twig->render('file.html', $data);

      $this->showFormButtons($options);
   }
}
