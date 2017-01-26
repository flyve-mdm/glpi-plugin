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
class PluginStorkmdmMqttlog extends CommonDBTM {

   const MQTT_MAXIMUM_DURATION = 60;

   /**
    * @deprecated
    * @var unknown $phpMqtt
    */
   protected $phpMqtt;

   /**
    * @deprecated
    * @var unknown $beginTimestamp
    */
   protected $beginTimestamp;

   public function __construct() {
      parent::__construct();

      $this->beginTimestamp = time();

   }

   /**
    * Name of the type
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb=0) {
      return _n('MQTT subscriber', 'MQTT subscribers', $nb, "storkmdm");
   }

   /**
    * Save in DB an incoming MQTT message
    * @param String $topic topic
    * @param String $msg Message
    */
   public function saveIngoingMqttMessage($topic, $msg) {
      $this->saveMqttMessage("I", $topic, $msg);
   }


   /**
    * Save in the DB an outgoing MQTT message
    * @param array $topicList array of topics. String allowed for a single topic
    * @param String $msg Message
    */
   public function saveOutgoingMqttMessage($topicList, $msg) {
      $this->saveMqttMessage("O", $topicList, $msg);
   }

   /**
    * Save MQTT messages sent or received
    * @param String $direction I for input O for output
    * @param array $topicList array of topics. String allowed for a single topic
    * @param String $msg Message
    */
   protected function saveMqttMessage($direction, $topicList, $msg) {
      global $DB;

      if (! is_array($topicList)) {
         $topicList = array($topicList);
      }
      $msg = $DB->escape($msg);
      foreach ($topicList as $topic) {
         $topic = $DB->escape($topic);
         $this->fields['date'] = date('Y-m-d H:i:s');
         $this->fields['direction'] = $direction;
         $this->fields['topic'] = $topic;
         $this->fields['message'] = $msg;
         unset($this->fields['id']);
         $this->addToDB();
      }
   }
}
