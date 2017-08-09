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
 * @since 0.1.0
 */
class PluginFlyvemdmUpkparser {

   /**
    * @var \ApkParser\Parser $apkParser APK parser instalce
    */
   protected $apkParser;
   protected $apkFilename;

   public function __construct($upkFile) {
      $zip = new ZipArchive();
      $this->apkFilename = tempnam(GLPI_TMP_DIR, 'upk_');
      if ($zip->open($upkFile) && $this->apkFilename !== null) {
         if ($manifestHandle = $zip->getStream('UPKManifest.xml')) {
            $manifestContent = stream_get_contents($manifestHandle);
            fclose($manifestHandle);

            $manifest = simplexml_load_string($manifestContent);
            $apkFile = $manifest->application[0]['fileName'];
            $fileHandle = $zip->getStream($apkFile);
            if ($fileHandle) {
               file_put_contents($this->apkFilename, $fileHandle);
               fclose($fileHandle);
               $this->apkParser = new \ApkParser\Parser($this->apkFilename);
            }
         }
      }
   }

   /**
    * get instalce of APK parser
    */
   public function getApkParser() {
      return $this->apkParser;
   }

   /**
    * destructor
    */
   public function __destruct() {
      unlink($this->apkFilename);
   }
}