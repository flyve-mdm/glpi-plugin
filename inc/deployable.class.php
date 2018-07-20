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

use GlpiPlugin\Flyvemdm\Exception\TaskPublishPolicyPolicyNotFoundException;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 2.0
 */
abstract class PluginFlyvemdmDeployable extends CommonDBTM {

   /**
    * @var Psr\Container\ContainerInterface
    */
   protected $container;

   public function __construct() {
      global $pluginFlyvemdmContainer;

      $this->container = $pluginFlyvemdmContainer;
      parent::__construct();
   }

   /**
    * Sends a file
    * @param string $streamSource full path and filename of file to send
    * @param string $filename alias of the file to be saved
    * @param integer $size of the file
    */
   protected function sendFile($streamSource, $filename, $size) {

      // Ensure the file exists
      if (!file_exists($streamSource) || !is_file($streamSource)) {
         header("HTTP/1.0 404 Not Found");
         exit(0);
      }

      // Download range defaults to the full file
      // get file metadata
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
      header("Content-disposition: attachment; filename=\"" . $filename . "\"");
      header("Content-type: $mimeType");
      header("Last-Modified: $time");
      header('Accept-Ranges: bytes');
      header('Content-Length:' . ($end - $begin + 1));
      header("Content-Range: bytes $begin-$end/$size");
      header("Content-Transfer-Encoding: binary\n");
      header('Connection: close');

      $httpStatus = 'HTTP/1.0 200 OK';
      if ($begin > 0 || $end < $size - 1) {
         $httpStatus = 'HTTP/1.0 206 Partial Content';
      }
      header($httpStatus);

      // Sends bytes until the end of the range or connection closed
      while (!feof($fileHandle) && $currentPosition < $end && (connection_status() == 0)) {
         // allow a few seconds to send a few KB.
         set_time_limit(10);
         $content = fread($fileHandle, min(1024 * 16, $end - $currentPosition + 1));
         if ($content === false) {
            header("HTTP/1.0 500 Internal Server Error", true); // Replace previously sent headers
            exit(0);
         }
         print $content;
         flush();
         $currentPosition += 1024 * 16;
      }

      // Endnow to prevent any unwanted bytes
      exit(0);
   }

   /**
    * Publish MQTT message for each deploy task
    *
    * @param PluginFlyvemdmTask $task
    */
   protected function deployNotification(PluginFlyvemdmTask $task) {
      $itemtype = $this->getType();
      $itemId = $this->getID();
      $tasks = $task->find("`itemtype`='$itemtype' AND `items_id`='$itemId'");
      foreach ($tasks as $taskId => $taskRow) {
         $notifiableType = $taskRow['itemtype_applied'];
         $notifiable = $this->container->make($notifiableType);
         $notifiableId = $taskRow['items_id_applied'];
         if ($notifiable->getFromDB($notifiableId)) {
            Toolbox::logInFile('php-errors',
               "Plugin Flyvemdm : Could not find notifiable id = '$notifiableId'");
            continue;
         }
         if ($task->getFromDB($taskId)) {
            try {
               $task->publishPolicy($notifiable);
               $task->createTaskStatuses($notifiable);
            } catch (TaskPublishPolicyPolicyNotFoundException $exception) {
               Session::addMessageAfterRedirect(__("Deploy notification failed", 'flyvemdm'),
                  false, INFO, true);
            }
         }
      }
   }

   /**
    * Get the download URL for the application
    * @param $deployableItem
    * @return boolean|string
    */
   protected function getFileURL($deployableItem) {
      $config = Config::getConfigurationValues('flyvemdm', ['deploy_base_url']);
      $deployBaseURL = $config['deploy_base_url'];
      if ($deployBaseURL === null) {
         return false;
      }
      // deployable
      return $deployBaseURL . '/'.$deployableItem.'/' . $this->fields['filename'];
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
    * @param string $filename full path and filename to be unlinked
    */
   protected function unlinkLocalFile($filename) {
      if (is_writeable($filename)) {
         unlink($filename);
      }
   }
}