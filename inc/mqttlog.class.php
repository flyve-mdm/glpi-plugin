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

         $chunks = explode('/', $topic, 4);
         if ($chunks[0] == '' || !isset($chunks[3])) {
            // avoid to save invalid topic formats as starting
            // with tailing slash or empty topic strings
            continue;
         }

         $itemtype = '';
         $itemId = '';
         switch ($chunks[1]) {
            case 'fleet':
               $itemtype = PluginFlyvemdmFleet::getType();
               $itemId = $chunks[2];
               break;
            case 'agent':
               $computer = new Computer();
               $computer->getFromDBByCrit(['serial' => $chunks[2]]);
               $agent = new PluginFlyvemdmAgent();
               $agent->getFromDBByCrit([$computer::getForeignKeyField() => $computer->getID()]);
               $itemtype = $agent::getType();
               $itemId = $agent->getID();
               break;
         }

         $this->fields['date'] = date('Y-m-d H:i:s');
         $this->fields['direction'] = $direction;
         $this->fields['topic'] = $chunks[3];
         $this->fields['message'] = $msg;
         $this->fields['itemtype'] = $itemtype;
         $this->fields['items_id'] = $itemId;
         unset($this->fields['id']);
         $this->addToDB();
      }
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!self::canView()) {
         return '';
      }

      if (!($item instanceof PluginFlyvemdmNotifiableInterface)) {
         return '';
      }
      // Agent or Fleet
      if ($withtemplate) {
         return '';
      }
      $nb = 0;
      $topic = $item->getTopic();
      if ($_SESSION['glpishow_count_on_tabs'] && $topic) {
         $logs = self::findLogs($item);
         $nb = $logs->count();
      }
      return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
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
      $pager_top = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);
      $pager_bottom = Html::printAjaxPager(self::getTypeName(1), $start, $number, '', false);

      $data = [
         'empty_msg'    => 'No item found',
         'number'       => $number,
         'pager_top'    => $pager_top,
         'pager_bottom' => $pager_bottom,
         'logs'         => $rows,
         'start'        => $start,
         'stop'         => $start + $_SESSION['glpilist_limit'],
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

      if (version_compare(GLPI_VERSION, '9.3.1') >= 0) {
         $condition = [
            'FIELDS'    => ['id', 'MAX' => ['date as date'], 'topic', 'message'],
            'WHERE'     => ['itemtype' => $item::getType(), 'items_id' => $item->getID()],
            'GROUPBY'   => 'topic',
         ];
         $result = $DB->request(static::getTable(), $condition);
      } else {
         $result = $DB->query("SELECT id, MAX(date) as date, topic, message
            FROM " . static::getTable() . "
            WHERE itemtype='" . $item::getType() . "' AND items_id = '" . $item->getID() . "'
            GROUP BY topic
         ");
      }
      return $result;
   }
}
