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
 * @since 0.1.0
 */
class PluginStorkmdmPackage extends CommonDBTM {

   /**
    * @var string $rightname name of the right in DB
    */
   static $rightname                   = 'storkmdm:package';

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
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb=0) {
      global $LANG;
      return _n('Package', 'Packages', $nb, "storkmdm");
   }

   /**
    * @deprecated
    * {@inheritDoc}
    * @see CommonGLPI::defineTabs()
    */
   public function defineTabs($options = array()) {
      $tab = array();
      $this->addDefaultFormTab($tab);
      $this->addStandardTab('Notepad', $tab, $options);
      $this->addStandardTab('Log', $tab, $options);

      return $tab;
   }

   /**
    * Returns the tab name of this itemtype, depending on the itemtype on which it will be displayed
    * If the tab shall not display then returns an empty string
    * @param CommonGLPI $item on which the tab will show
    * @param number $withtemplate template mode for $item : 0 = no template - 1 = edit template - 2 = from template
    * @return translated|string
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $CFG_GLPI;

      switch ($item->getType()) {
         case 'Software' :
            return _n('Package Stork MDM', 'Packages Stork MDM', Session::getPluralNumber(), "storkmdm");

      }
      return '';
   }

   /**
    *  Display the content of the tab provided by this itemtype
    * @deprecated
    * @param CommonGLPI $item
    * @param number $tabnum
    * @param number $withtemplate
    */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if ($item->getType() == 'Software') {
         self::showForSoftware($item);
         return true;
      }
   }

   /**
    * Display a form to view, create or edit
    * @deprecated
    * @param integer $ID ID of the item to show
    * @param unknown $options
    */
   public function showForm($ID, $options=array()) {
      global $CFG_GLPI, $DB;

      $this->initForm($ID, $options);
      $this->showFormHeader();

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __s('Name') .
            (isset($options['withtemplate']) && $options['withtemplate']?"*":"") .
            "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && $options['withtemplate']==2),
                             $this->getType(), -1);
      Html::autocompletionTextField($this, 'name', array('value' => $objectName));
      echo "</td>";

      echo "<td>" . $this->fields["filename"] . " <br>". __s('Upload package', "storkmdm") . "</td><td>" .
            "<br><input type='file' name='filename' />" .
            "</td>";

      echo '</tr>';

      $this->showFormButtons($options);
   }

   /**
    * Gets the maximum file size allowed for uploads from PHP configuration
    * @return integer maximum file size
    */
   protected static function getMaxFileSize() {
      $val = trim(ini_get('post_max_size'));
      $last = strtolower($val[strlen($val)-1]);
      switch($last) {
         case 'g':
            $val *= 1024;
         case 'm':
            $val *= 1024;
         case 'k':
            $val *= 1024;
      }

      return $val;
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
         $input = false;
      } else {
         try {
            $destination = STORKMDM_PACKAGE_PATH . "/" . $input['entities_id'] . "/" . uniqid() . "_" . basename($_FILES['file']['name']);
            if ($this->saveUploadedFile($_FILES['file'], $destination)) {
               $fileExtension = pathinfo($destination, PATHINFO_EXTENSION);
               $filename = pathinfo($destination, PATHINFO_FILENAME);
               if ($fileExtension == "apk") {
                  $apk = new \ApkParser\Parser($destination);
               } elseif ($fileExtension == "upk") {
                  $upkParser = new PluginStorkmdmUpkparser($destination);
                  $apk = $upkParser->getApkParser();
               }
               $manifest               = $apk->getManifest();
               $iconResourceId         = $manifest->getApplication()->getIcon();
               $labelResourceId        = $manifest->getApplication()->getLabel();
               $iconResources          = $apk->getResources($iconResourceId);
               $apkLabel               = $apk->getResources($labelResourceId);
               $input['icon']          = base64_encode(stream_get_contents($apk->getStream($iconResources[0])));
               $input['name']          = $manifest->getPackageName();
               if ( (!isset($input['alias'])) || (strlen($input['alias']) == 0) ) {
                  $input['alias']         = $apkLabel[0]; // Get the first item
               }
               $input['version']       = $manifest->getVersionName();
               $input['version_code']  = $manifest->getVersionCode();
               $input['filename']      = $input['entities_id'] . "/" . basename($filename) . '.' . $fileExtension;
               $input['filesize']      = fileSize($destination);
               $input['dl_filename']   = basename($_FILES['file']['name']);
            } else {
               Session::addMessageAfterRedirect(__('Unable to save the file', "storkmdm"));
               $input = false;
            }
         } catch (Exception $e) {
            // Ignore exceptions for now
            Session::addMessageAfterRedirect(__('Could not parse the APK file', "storkmdm"));
            $input = false;
         }
      }

      return $input;
   }

   /**
    * (non-PHPdoc)
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      if (isset ($_FILES['file']['error']) && !$_FILES['file']['error'] == 0) {
         if (!$_FILES['file']['error'] == 4) {
            Session::addMessageAfterRedirect(__('Could not upload package file', "storkmdm"));
         }
         $input['filename'] = $this->fields['filename'];
      } else {
         if (isset ($_FILES['file'])) {
            try {
               $destination = STORKMDM_PACKAGE_PATH . "/" . $this->fields['entities_id'] . "/" . uniqid() . "_" . basename($_FILES['file']['name']);
               if ($this->saveUploadedFile($_FILES['file'], $destination)) {
                  $apk = new \ApkParser\Parser($destination);
                  $manifest = $apk->getManifest();
                  // check the new application has the same fqname than the older one
                  if ($this->fields['name'] != $manifest->getPackageName()) {
                     //unlink($destination);
                     //return false;
                  }
                  $iconResourceId         = $manifest->getApplication()->getIcon();
                  $labelResourceId        = $manifest->getApplication()->getLabel();
                  $iconResource           = $apk->getResources($iconResourceId);
                  $apkLabel               = $apk->getResources($labelResourceId);
                  $input['icon']          = base64_encode(stream_get_contents($apk->getStream($iconResources[0])));
                  $input['name']          = $manifest->getPackageName();
                  $input['version']       = $manifest->getVersionName();
                  $input['version_code']  = $manifest->getVersionCode();
                  $input['filename']      = $this->fields['entities_id'] . "/" . basename($filename) . '.' . $fileExtension;
                  $input['filesize']      = fileSize($destination);
                  $input['dl_filename']   = basename($_FILES['file']['name']);

                  unlink(STORKMDM_PACKAGE_PATH . "/" . $this->fields['filename']);
               } else {
                  return $false;
               }
            } catch (Exception $e) {
               // Ignore exceptions for now
               $input = false;
            }
         } else {
            // No application has been uploaded
            unset($input['icon']);
            unset($input['version']);
            unset($input['filesize']);
            unset($input['filename']);
            unset($input['dl_filename']);
         }
      }

      return $input;
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
      if (isset($this->oldvalues['filename'])) {
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
               $fleet_policy->publishPolicies($fleet, $policy->getGroup());
            }
         }
      }
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
    * @see CommonDBTM::post_purgeItem()
    */
   public function post_purgeItem() {
      $filename = STORKMDM_PACKAGE_PATH . "/" . $this->fields['filename'];
      if (is_writable($filename)) {
         unlink($filename);
      }
   }

   /**
    * @brief move and rename the uploaded file
    * Checks for file extension before moving the file
    * @return canonical saved filename, '' if an error occured
    */
   public function saveUploadedFile($source, $destination) {
      global $CFG_GLPI;
      $success = false;

      $fileExtension = pathinfo($source['name'], PATHINFO_EXTENSION);

      if (! in_array($fileExtension, array("apk", "upk"))) {
         $success = false;
      } else {
         $this->createEntityDirectory(dirname($destination));
         if  (!move_uploaded_file($source['tmp_name'], $destination)) {
            $success = false;
         } else {
            $success = true;
         }
      }
      return $success;
   }

   /**
    * Create a directory
    * @param unknown $dir
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
      $tab['common']                 = __s('Package ', "storkmdm");

      $i = 1;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'name';
      $tab[$i]['name']                = __('Name');
      $tab[$i]['datatype']            = 'itemlink';
      $tab[$i]['massiveaction']       = false;

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'id';
      $tab[$i]['name']                = __('ID');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'number';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'alias';
      $tab[$i]['name']                = __('alias', 'storkmdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'version';
      $tab[$i]['name']                = __('version', 'storkmdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'icon';
      $tab[$i]['name']                = __('icon', 'storkmdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'image';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'filesize';
      $tab[$i]['name']                = __('filesize', 'storkmdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      return $tab;
   }

   /**
    * Get the download URL for the application
    * @return boolean|string
    */
   public function getFileURL() {
      $config = Config::getConfigurationValues('storkmdm', array('deploy_base_url'));
      $deployBaseURL = $config['deploy_base_url'];

      if ($deployBaseURL === null) {
         return false;
      }

      $URL = $deployBaseURL . '/package/' . $this->fields['filename'];
      return $URL;
   }

   protected function sendFile() {
      $streamSource = STORKMDM_PACKAGE_PATH . "/" . $this->fields['filename'];

      if (!file_exists($streamSource) || !is_file($streamSource)) {
         header("HTTP/1.0 404 Not Found");
         exit(0);
      }

      $size = filesize($streamSource);
      $begin = 0;
      $end = $size - 1;
      $mimeType = 'application/octet-stream';
      $time = date('r', filemtime($streamSource));

      $fileHandle = @fopen($streamSource, 'rb');
      if (!$fileHandle) {
         header ("HTTP/1.0 500 Internal Server Error");
         exit(0);
      }

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
         header ("HTTP/1.0 500 Internal Server Error");
         exit(0);
      }

      // send headers to ensure the client is able to detect an corrupted download
      // example : less bytes than the expected range
      header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
      header('Pragma: private'); /// IE BUG + SSL
      header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
      header("Content-disposition: attachment; filename=\"" . $this->fields['dl_filename'] . "\"");
      header("Content-type: $mimeType");
      header("Last-Modified: $time");
      header('Accept-Ranges: bytes');
      header('Content-Length:' . ($end - $begin + 1));
      header("Content-Range: bytes $begin-$end/$size");
      header("Content-Transfer-Encoding: binary\n");
      header('Connection: close');

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

      exit(0);
   }


   /**
    * Uninstall from GLPI
    */
   public static function uninstall() {
      global $DB;

      ProfileRight::deleteProfileRights(array(
         self::$rightname
      ));
      unset($_SESSION["glpiactiveprofile"][self::$rightname]);

      foreach (array('Notepad', 'DisplayPreference', 'Log') as $itemtype) {
         $item = new $itemtype();
         $item->deleteByCriteria(array('itemtype' => __CLASS__));
      }

      $table = getTableForItemType(__CLASS__);
      $DB->query("DROP TABLE IF EXISTS `$table`") or die($DB->error());
   }
}
