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

namespace tests\units;

use Glpi\Test\CommonTestCase;

class PluginFlyvemdmPackage extends CommonTestCase {

   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testPrepareInputForUpdate':
         case 'testPostGetFromDB':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    * @param string $packageTable
    * @param null|string $filename
    * @param int $version
    * @return object
    */
   private function createPackage($packageTable, $filename = null, $version = 1) {
      global $DB;

      // Create an file (directly in DB)
      $uniqueString = ((null !== $filename) ? $filename : $this->getUniqueString());
      $packageName = 'com.domain.' . $uniqueString . '.application';
      $entityId = $_SESSION['glpiactive_entity'];
      $query = "INSERT INTO $packageTable (
         `package_name`,
         `alias`,
         `version`,
         `filename`,
         `entities_id`,
         `dl_filename`,
         `icon`
      )
      VALUES (
         '$packageName',
         'application',
         '1.0.5',
         '$entityId/123456789_application_" . $uniqueString . ".apk',
         '$entityId',
         'application_" . $uniqueString . ".apk',
         ''
      )";
      $DB->query($query);
      $mysqlError = $DB->error();
      $package = $this->newTestedInstance();
      $this->boolean($package->getFromDBByQuery("WHERE `package_name`='$packageName'"))
         ->isTrue($mysqlError);

      return $package;
   }

   public function providerPostGetFromDB() {
      return [
         [['isApi' => true, 'download' => false]],
         [['isApi' => true, 'download' => true]],
         [['isApi' => false, 'download' => false]],
      ];
   }

   /**
    * @tags testPostGetFromDB
    * @dataProvider providerPostGetFromDB
    * @param $argument
    */
   public function testPostGetFromDB($argument) {
      $isApi = $argument['isApi'];
      if ($isApi && $argument['download']) {
         $_SERVER['HTTP_ACCEPT'] = 'application/octet-stream';
      }

      $fileTable = \PluginFlyvemdmFile::getTable();
      $common = $this->newMockInstance(\PluginFlyvemdmCommon::class);
      $this->calling($common)->isAPI = $isApi;

      $filename = $this->getUniqueString();
      $fileSize = file_put_contents(FLYVEMDM_FILE_PATH . '/' . $filename, 'dummy');
      $this->integer($fileSize)->isGreaterThan(0);
      $file = $this->createPackage($fileTable, $filename);
      if (isAPI() && $argument['download']) {
         $this->resource($file)->isStream();
      } else {
         if (isAPI()) {
            $this->array($fields = $file->fields)->hasKeys(['filesize', 'mime_type'])
               ->integer($fields['filesize'])->isEqualTo($fileSize)
               ->string($fields['mime_type'])->isEqualTo('text');
         } else {
            $this->object($file)->isInstanceOf('PluginFlyvemdmPackage');
         }
      }
   }

}