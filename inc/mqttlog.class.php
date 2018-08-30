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
 * @since 0.1.0
 */
class PluginFlyvemdmMqttlog extends CommonDBTM {

   const MQTT_MAXIMUM_DURATION = 60;

   /**
    * PluginFlyvemdmMqttlog constructor.
    */
   public function __construct() {
      parent::__construct();

   }

   /**
    * Name of the type
    * @param $nb  integer  number of item in the type (default 0)
    * @return string
    */
   public static function getTypeName($nb = 0) {
      return _n('MQTT subscriber', 'MQTT subscribers', $nb, "flyvemdm");
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
    * @param array|string $topicList array of topics. String allowed for a single topic
    * @param String $msg Message
    */
   public function saveOutgoingMqttMessage($topicList, $msg) {
      $this->saveMqttMessage("O", $topicList, $msg);
   }

   /**
    * Save MQTT messages sent or received
    * @param string $direction I for input O for output
    * @param array|string $topicList array of topics. String allowed for a single topic
    * @param string $msg Message
    */
   protected function saveMqttMessage($direction, $topicList, $msg) {
      global $DB;

      if (!is_array($topicList)) {
         $topicList = [$topicList];
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
