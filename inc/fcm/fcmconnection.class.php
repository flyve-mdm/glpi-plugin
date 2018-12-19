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
 * @copyright Copyright © 2018 Teclib
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace GlpiPlugin\Flyvemdm\Fcm;

use Sly\NotificationPusher\Adapter\Gcm;
use ZendService\Google\Gcm\Message as GcmMessage;
use Sly\NotificationPusher\Model\Push;
use Sly\NotificationPusher\PushManager;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class FcmConnection {

   /**
    * @var PushManager
    */
   private $pushManager;

   /**
    * @var Gcm
    */
   private $adapter;

   /**
    * @var \Toolbox
    */
   private $toolbox;

   public function __construct(PushManager $pushManager, Gcm $adapter, \Toolbox $toolbox) {
      $this->pushManager = $pushManager;
      $this->adapter = $adapter;
      $this->toolbox = $toolbox;
   }

   /**
    * @return Gcm
    */
   public function getAdapter() {
      return $this->adapter;
   }

   /**
    * @param Push $push
    */
   public function addPush(Push $push) {
      $this->pushManager->add($push);
   }

   /**
    * @return \Sly\NotificationPusher\Collection\PushCollection
    */
   public function push() {
      try {
         return $this->pushManager->push();
      } catch (\RuntimeException $e) {
         $this->logExceptionEvent($e);
      }
   }

   /**
    * @return \Sly\NotificationPusher\Model\ResponseInterface
    */
   public function getResponse() {
      return $this->pushManager->getResponse();
   }

   /**
    * Do a dry-run push for testing the service
    * @param GcmMessage $gcmMessage
    * @param $deviceToken
    * @return boolean
    */
   public function testConnection(GcmMessage $gcmMessage, $deviceToken) {
      try {
         $adapter = $this->adapter;
         $client = $adapter->getOpenedClient();
         $client->setApiKey($adapter->getParameter('apiKey'));
         $gcmMessage->setDryRun(true);
         $gcmMessage->setData(['test'=>'lorem']);
         $gcmMessage->addRegistrationId($deviceToken);
         $client->send($gcmMessage);
         return true;
      } catch (\RuntimeException $e) {
         $this->logExceptionEvent($e);
         return false;
      }
   }

   /**
    * @param \RuntimeException $e
    */
   protected function logExceptionEvent(\RuntimeException $e) {
      $error = "Exception while connecting to the broker : " . $e->getMessage();
      $trace = $e->getTraceAsString();
      $toolbox = $this->toolbox;
      $toolbox::logInFile("fcm", "$error\n$trace\n\n");
   }
}