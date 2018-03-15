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

class PluginFlyvemdmFile extends CommonTestCase {

   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testPrepareInputForUpdate':
         case 'testPostGetFromDB':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    * @param string $fileTable
    * @param null|string $userFilename
    * @param int $version
    * @return object
    */
   private function createFile($fileTable, $userFilename = null, $version = 1) {
      global $DB;

      // Create an file (directly in DB)
      $entityId = $_SESSION['glpiactive_entity'];
      $fileName = ((null !== $userFilename) ? $userFilename : $this->getUniqueString());
      $destination = $entityId . '/dummy_file_' . $fileName;
      if (!is_dir($directory = FLYVEMDM_FILE_PATH . "/" . $entityId)) {
         @mkdir($directory);
      }
      $fileSize = file_put_contents(FLYVEMDM_FILE_PATH . '/' . $destination, 'dummy');
      $this->integer($fileSize)->isGreaterThan(0);
      $query = "INSERT INTO $fileTable (
         `name`,
         `source`,
         `entities_id`,
         `version`
      ) VALUES (
         '$fileName',
         '$destination',
         '$entityId',
         '$version'
      )";
      $DB->query($query);
      $mysqlError = $DB->error();
      $file = $this->newTestedInstance();
      $file->getFromDBByCrit(['name' => $fileName]);
      $this->boolean($file->isNewItem())->isFalse($mysqlError);

      return $file;
   }

   public function providerPrepareInputForAdd() {
      return [
         /*
         // This test case needs dependency injection bacause a mock is required
         [
            'isApi' => true,
            'input' => [
               'entities_id' => '0',
            ],
            'upload' => [
               'file' => [
                  'tmp_name' => '/tmp/unit-test-uploaded',
                  'error' => 0
               ]
            ],
            'expected' => [
               'version' => 1,
            ]
         ],
         */
         [
            'isApi'    => false,
            'input'    => [
               'entities_id' => '0',
            ],
            'upload'   => [
               '_file' => [
                  0 => 'document.pdf',
               ],
            ],
            'expected' => [
               'version' => 1,
            ],
         ],
      ];
   }

   public function providerPrepareInputForUpdate() {
      return [
         /*
          // This test case needs dependency injection bacause a mock is required
          [
          'isApi' => true,
          'input' => [
          'entities_id' => '0',
          ],
          'upload' => [
          'file' => [
          'tmp_name' => '/tmp/unit-test-uploaded',
          'error' => 0
          ]
          ],
          'expected' => [
          'version' => 1,
          ]
          ],
          */
         [
            'isApi'    => false,
            'input'    => [
               'entities_id' => '0',
               'version'     => '5',
            ],
            'upload'   => [
               '_file' => [
                  0 => 'document.pdf',
               ],
            ],
            'expected' => [
               'version' => 6,
            ],
         ],
      ];
   }

   /**
    * @tags testPrepareInputForAdd
    * @dataProvider providerPrepareInputForAdd
    * @param boolean $isApi
    * @param array $input
    * @param array $upload
    * @param array|boolean $expected
    */
   public function testPrepareInputForAdd($isApi, $input, $upload, $expected) {
      // backup altered superglobals
      if ($isApi) {
         $backupFiles = $_FILES;
         $_FILES = $upload;
      } else {
         $backupPost = $_POST;
         $_POST = $upload;
      }

      // put a file in the actual FS
      if ($isApi) {
         if (isset($upload['file']['tmp_name'])) {
            file_put_contents($upload['file']['tmp_name'], 'dummy');
         }
      } else {
         if (isset($upload['_file'][0])) {
            file_put_contents(GLPI_TMP_DIR . '/' . $upload['_file'][0], 'dummy');
         }
      }

      $common = $this->newMockInstance(\PluginFlyvemdmCommon::class);
      $this->calling($common)->isAPI = $isApi;
      $file = $this->newTestedInstance();
      $output = $file->prepareInputForAdd($input);
      if ($expected === false) {
         $this->boolean($output)->isFalse();
      } else {
         $this->integer((int)$output['version'])->isEqualTo($expected['version'],
            json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));
         $this->array($output)->hasKey('source');
      }

      // restore altered superglobals
      if ($isApi) {
         $_FILES = $backupFiles;
      } else {
         $_POST = $backupPost;
      }
   }

   /**
    * @tags testPrepareInputForUpdate
    * @dataProvider providerPrepareInputForUpdate
    * @param boolean $isApi
    * @param array $input
    * @param array $upload
    * @param array|boolean $expected
    */
   public function testPrepareInputForUpdate($isApi, $input, $upload, $expected) {
      // backup altered superglobals
      if ($isApi) {
         $backupFiles = $_FILES;
         $_FILES = $upload;
      } else {
         $backupPost = $_POST;
         $_POST = $upload;
      }

      // put a file in the actual FS
      if ($isApi) {
         if (isset($upload['file']['tmp_name'])) {
            file_put_contents($upload['file']['tmp_name'], 'dummy');
         }
      } else {
         if (isset($upload['_file'][0])) {
            file_put_contents(GLPI_TMP_DIR . '/' . $upload['_file'][0], 'dummy');
         }
      }

      $common = $this->newMockInstance(\PluginFlyvemdmCommon::class);
      $this->calling($common)->isAPI = $isApi;

      $fileTable = \PluginFlyvemdmFile::getTable();
      $file = $this->createFile($fileTable, null, $input['version']);

      $output = $file->prepareInputForUpdate($input);
      if ($expected === false) {
         $this->boolean($output)->isFalse();
      } else {
         $this->integer($output['version'])->isEqualTo($expected['version'],
               json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));
         $this->array($output)->hasKey('source');
      }

      // restore altered superglobals
      if ($isApi) {
         $_FILES = $backupFiles;
      } else {
         $_POST = $backupPost;
      }
   }

   public function providerPostGetFromDB() {
      return [
         [['isApi' => false, 'download' => false]],
         [['isApi' => true, 'download' => false]],
         //[['isApi' => true, 'download' => true]], // this assert needs mocking the isAPI function
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

      $file = $this->createFile(\PluginFlyvemdmFile::getTable());
      if ($isApi && $argument['download']) {
         $this->resource($file)->isStream();
      } else if ($isApi) {
         $this->array($fields = $file->fields)->hasKeys(['filesize', 'mime_type'])
            ->integer($fields['filesize'])->isGreaterThan(0)
            ->string($fields['mime_type'])->isEqualTo('text/plain');
      } else {
         $this->object($file)->isInstanceOf('PluginFlyvemdmFile');
      }
   }
}
