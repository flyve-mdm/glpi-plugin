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
 * @since 0.1.0
 */
class PluginFlyvemdmMqttlog extends CommonDBTM {

   const MQTT_MAXIMUM_DURATION = 60;

   /**
    * @var string $rightname name of the right in DB
    */
   public static $rightname = 'flyvemdm:mqttlog';

   /**
    * PluginFlyvemdmMqttlog constructor.
    */
   public function __construct() {
      parent::__construct();

   }

   public function getRights($interface = 'central') {
      return [READ  => __('Read')];
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

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!self::canView()) {
         return '';
      }

      if ($item instanceof PluginFlyvemdmNotifiableInterface) {
         // Agent or Fleet
         if (!$withtemplate) {
            $nb = 0;
            $topic = $item->getTopic();
            if ($_SESSION['glpishow_count_on_tabs'] && $topic) {
               $logs = self::findLogs($item);
               $nb = $logs->count();
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item instanceof PluginFlyvemdmNotifiableInterface) {
         // Agent or Fleet
         self::showMqttLogs($item);
         return true;
      }
   }

   /**
    * @param PluginFlyvemdmNotifiableInterface $item
    */
   public static function showMqttLogs(PluginFlyvemdmNotifiableInterface $item) {
      if (!self::canView()) {
         return;
      }

      $start = isset($_GET["start"]) ? intval($_GET["start"]) : 0;

      // get items
      $rows = [];
      $topic = $item->getTopic();
      if ($topic) {
         foreach (self::findLogs($item) as $id => $row) {
            $rows[] = [
               'ID'      => $row['id'],
               'date'    => $row['date'],
               'topic'   => $row['topic'],
               'message' => $row['message'],
            ];
         }
      }
      $number = count($rows);

      // get the pager
      $pager = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);

      $data = [
         'empty_msg' => 'No item found',
         'number'    => $number,
         'pager'     => $pager,
         'logs'      => $rows,
         'start'     => $start,
         'stop'      => $start + $_SESSION['glpilist_limit'],
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('mqttlog_list.html.twig', $data);
   }


   /**
    * Get the broker message logs of a notifiable item
    *
    * @param PluginFlyvemdmNotifiableInterface $item
    * @return DBmysqlIterator
    */
   public static function findLogs(PluginFlyvemdmNotifiableInterface $item) {
      global $DB;

      $condition = [
         'FIELDS' => ['id', 'date', 'topic', 'message'],
         'WHERE'           => ['topic' => ['LIKE', $item->getTopic() . '%']],
         'GROUPBY'         => 'topic',
      ];

      $result = $DB->request(static::getTable(), $condition);
      return $result;
   }
}
