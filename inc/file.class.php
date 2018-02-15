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
 * @since 0.1.30
 */
class PluginFlyvemdmFile extends CommonDBTM {

   // name of the right in DB
   static $rightname = 'flyvemdm:file';

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi < 0.85)
    */
   protected $usenotepad = true;

   /**
    * @var bool $usenotepad enable notepad for the itemtype (GLPi >=0.85)
    */
   protected $usenotepadRights = true;

   /**
    * Localized name of the type
    * @param integer $nb number of item in the type (default 0)
    * @return string
    */
   public static function getTypeName($nb = 0) {
      return _n('File', 'Files', $nb, "flyvemdm");
   }

   /**
    * Returns the URI to the picture file relative to the front/folder of the plugin
    * @return string URI to the picture file
    */
   public static function getMenuPicture() {
      return 'fa-file';
   }

   public function addNeededInfoToInput($input) {
      $input['entities_id'] = $_SESSION['glpiactive_entity'];

      return $input;
   }

   public function prepareInputForAdd($input) {
      list($actualFilename, $uploadedFile) = $this->getUploadedFile(true);
      if ($actualFilename === null) {
         return false;
      }

      if (!isset($input['entities_id'])) {
         $input['entities_id'] = $_SESSION['glpiactive_entity'];
      }

      if (!isset($actualFilename)) {
         Session::addMessageAfterRedirect(__('File uploaded without name', 'flyvemdm'));
         return false;
      }
      $input['source'] = $input['entities_id'] . "/" . uniqid() . "_" . basename($uploadedFile);
      $destination = FLYVEMDM_FILE_PATH . "/" . $input['source'];
      $this->createEntityDirectory(dirname($destination));
      if (!rename($uploadedFile, $destination)) {
         if (!file_exists(dirname($destination))) {
            $destination = dirname($destination);
            Toolbox::logInFile('php-errors',
               "Plugin Flyvemdm : Directory '$destination' is not writeable");
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

   public function prepareInputForUpdate($input) {
      list($actualFilename, $uploadedFile) = $this->getUploadedFile(true);

      unset($input['entities_id']);

      if ($actualFilename !== null) {
         // A file has been uploaded
         if (!isset($actualFilename)) {
            Session::addMessageAfterRedirect(__('File uploaded without name', 'flyvemdm'));
            return false;
         }
         $input['source'] = $this->fields['entities_id'] . "/" . uniqid() . "_" . basename($uploadedFile);
         $destination = FLYVEMDM_FILE_PATH . "/" . $input['source'];
         $filename = pathinfo($actualFilename, PATHINFO_FILENAME);
         if (!rename($uploadedFile, $destination)) {
            if (!is_writable(dirname($destination))) {
               $destination = dirname($destination);
               Toolbox::logInFile('php-errors',
                  "Plugin Flyvemdm : Directory '$destination' is not writeable");
            }
            return false;
         }
         if ($filename != $this->fields['source']) {
            if (file_exists(FLYVEMDM_FILE_PATH . "/" . $this->fields['source'])) {
               unlink(FLYVEMDM_FILE_PATH . "/" . $this->fields['source']);
            }
         }
         // File updated, then increment its version
         $input['version'] = $this->fields['version'] + 1;
      } else {
         // No file uploaded
         unset($input['source']);
      }

      return $input;
   }

   /**
    * Gets the filename and the saved copy of an upload
    * @param boolean $uploadMandatory Is the upload mandatory ?
    * @return array filename of the upload, and path to the uploaded file
    */
   private function getUploadedFile($uploadMandatory = false) {
      // Find the added file
      $common = new PluginFlyvemdmCommon();
      if (!$common->isAPI()) {
         // from GLPI UI
         if (isset($_POST['_file'][0]) && is_string($_POST['_file'][0])) {
            $actualFilename = $_POST['_file'][0];
            $uploadedFile = GLPI_TMP_DIR . '/' . $_POST['_file'][0];
            return [$actualFilename, $uploadedFile];
         }
         return [null, null];
      }

      if ($uploadMandatory && !isset($_FILES['file'])) {
         Session::addMessageAfterRedirect(__('No file uploaded', 'flyvemdm'));
         return [null, null];
      }

      if (!$_FILES['file']['error'] == 0) {
         if (!$_FILES['file']['error'] == 4) {
            Session::addMessageAfterRedirect(__('File upload failed', 'flyvemdm'));
         }
         return [null, null];
      }

      $destination = GLPI_TMP_DIR . '/' . $_FILES['file']['name'];
      if (is_readable($_FILES['file']['tmp_name']) && !is_readable($destination)) {
         // Move the file to GLPI_TMP_DIR
         if (!is_dir(GLPI_TMP_DIR)) {
            Session::addMessageAfterRedirect(__('Temp directory doesn\'t exist', 'flyvemdm'), false, ERROR);
            return [null, null];
         }
      }

      $actualFilename = $_FILES['file']['name'];
      $uploadedFile = $destination;

      return [$actualFilename, $uploadedFile];
   }

   /**
    * get the URL to download the file
    * @return string|boolean URL of the file
    */
   public function getFileURL() {
      $config = Config::getConfigurationValues('flyvemdm', ['deploy_base_url']);
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
    * @return array
    */
   public function getSearchOptionsNew() {
      $tab = parent::getSearchOptionsNew();

      $tab[] = [
         'id'                 => 'common',
         'name'               => __s('File', 'flyvemdm'),
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
         'field'         => 'source',
         'name'          => __('Source'),
         'datatype'      => 'string',
         'massiveaction' => false,
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comment'),
         'datetype'           => 'text',
      ];

      return $tab;
   }

   public function pre_deleteItem() {
      $task = new PluginFlyvemdmTask();
      return $task->deleteByCriteria([
         'itemtype' => $this->getType(),
         'items_id' => $this->getID(),
      ]);
   }

   public function post_addItem() {
      global $DB;
   }

   /**
    * Actions done after the getFromFB method
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

   public function post_updateItem($history = 1) {
      // Check if the source changed
      if (isset($this->oldvalues['source'])) {
         $itemtype = $this->getType();
         $itemId = $this->getID();

         $task = new PluginFlyvemdmTask();
         $taskCol = $task->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
         $fleet = new PluginFlyvemdmFleet();
         foreach ($taskCol as $taskId => $taskRow) {
            $fleetId = $taskRow['plugin_flyvemdm_fleets_id'];
            if ($fleet->getFromDB($fleetId)) {
               Toolbox::logInFile('php-errors',
                  "Plugin Flyvemdm : Could not find fleet id = '$fleetId'");
               continue;
            }
            if ($task->getFromDB($taskId)) {
               $task->publishPolicy($fleet);
            }
         }
      }
   }

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
         header("HTTP/1.0 500 Internal Server Error");
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
      $file->deleteByCriteria(['entities_id' => $item->getField('id')], 1);
   }

   /**
    * Display a form to view, create or edit
    * @param integer $ID ID of the item to show
    * @param array $options
    */
   public function showForm($ID, array $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $twig = plugin_flyvemdm_getTemplateEngine();
      $fields = $this->fields;
      $objectName = autoName($this->fields["name"], "name",
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
         'comment'      => $fields['comment'],
      ];
      echo $twig->render('file.html', $data);

      $this->showFormButtons($options);
   }
}
