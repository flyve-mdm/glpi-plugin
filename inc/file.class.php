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
 * @since 0.1.30
 */
class PluginFlyvemdmFile extends PluginFlyvemdmDeployable {

   /**
    * @var string $rightname name of the right in DB
    */
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

   public function getAdditionalLinks() {
      return [];
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
    * @return array
    */
   public function getSearchOptionsNew() {
      return $this->rawSearchOptions();
   }

   public function rawSearchOptions() {
      if (method_exists('CommonDBTM', 'rawSearchOptions')) {
         $tab = parent::rawSearchOptions();
      } else {
         $tab = parent::getSearchOptionsNew();
      }

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
         'id'            => '4',
         'table'         => $this->getTable(),
         'field'         => 'comment',
         'name'          => __('Comment'),
         'datetype'      => 'text',
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => 'glpi_entities',
         'field'         => 'completename',
         'name'          => __('Entity'),
         'datatype'      => 'dropdown'
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

   public function post_getFromDB() {
      // Check the user can view this itemtype and can view this item
      if ($this->canView() && $this->canViewItem()) {
         $filename = FLYVEMDM_FILE_PATH . '/' . $this->fields['source'];
         $isFile = is_file($filename);
         $this->fields['filesize'] = ($isFile) ? fileSize($filename) : 0;
         $this->fields['mime_type'] = ($isFile) ? mime_content_type($filename) : '';
         if (isAPI()
            && (isset($_SERVER['HTTP_ACCEPT']) && $_SERVER['HTTP_ACCEPT'] == 'application/octet-stream'
               || isset($_GET['alt']) && $_GET['alt'] == 'media')) {
            $this->sendFile(FLYVEMDM_FILE_PATH . "/" . $this->fields['source'],
               $this->fields['name'], $this->fields['filesize']); // and terminate script
         }
      }
   }

   public function post_updateItem($history = 1) {
      // Check if the source changed
      if (!isset($this->oldvalues['source'])) {
         return;
      }

      $this->deployNotification(new PluginFlyvemdmTask);
   }

   public function post_purgeItem() {
      $this->unlinkLocalFile(FLYVEMDM_FILE_PATH . "/" . $this->fields['source']);
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

      $fields = $this->fields;
      $fields['filesize'] = '';
      if (!$this->isNewID($ID)) {
         $fields['filesize'] = fileSize(FLYVEMDM_FILE_PATH . '/' . $fields['source']);
         $fields['filesize'] = Toolbox::getSize($fields['filesize']);
      }
      $data = [
         'withTemplate' => (isset($options['withtemplate']) && $options['withtemplate'] ? "*" : ""),
         'canUpdate'    => (!$this->isNewID($ID)) && ($this->canUpdate() > 0) || $this->isNewID($ID),
         'isNewID'      => $this->isNewID($ID),
         'file'         => $fields,
         'upload'       => Html::file(['name' => 'file', 'display' => false]),
         'comment'      => $fields['comment'],
      ];
      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('file.html.twig', $data);

      $this->showFormButtons($options);
   }
}
