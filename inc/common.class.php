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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmCommon
{
   const SEMVER_VERSION_REGEX = '#\bv?(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[\da-z\-]+(?:\.[\da-z\-]+)*)?(?:\+[\da-z\-]+(?:\.[\da-z\-]+)*)?\b#i';

   /**
    * Display massive actions
    * @param array $massiveactionparams
    * @return string an HTML
    */
   public static function getMassiveActions($massiveactionparams) {
      ob_start();
      Html::showMassiveActions($massiveactionparams);
      $html = ob_get_clean();

      return $html;
   }

   /**
    * Return an array of values from enum fields
    * @param string $table name
    * @param string $field name
    * @return array
    */
   public static function getEnumValues($table, $field) {
      global $DB;

      $enum = [];
      if ($res = $DB->query( "SHOW COLUMNS FROM `$table` WHERE Field = '$field'" )) {
         $data = $DB->fetch_array($res);
         $type = $data['Type'];
         $matches = null;
         preg_match("/^enum\(\'(.*)\'\)$/", $type, $matches);
         if (isset($matches[1])) {
            $enum = explode("','", $matches[1]);
         }
      }

      return $enum;
   }

   /**
    * @return string pseudo-random UUID version 4
    */
   public static function generateUUID() {
      return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

         // 32 bits for "time_low"
         mt_rand(0, 0xffff), mt_rand(0, 0xffff),

         // 16 bits for "time_mid"
         mt_rand(0, 0xffff),

         // 16 bits for "time_hi_and_version",
         // four most significant bits holds version number 4
         mt_rand(0, 0x0fff) | 0x4000,

         // 16 bits, 8 bits for "clk_seq_hi_res",
         // 8 bits for "clk_seq_low",
         // two most significant bits holds zero and one for variant DCE1.1
         mt_rand(0, 0x3fff) | 0x8000,

         // 48 bits for "node"
         mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
      );
   }

   /**
    * delete a directory and its content recursive
    * @param string $dir
    * @return bool
    */
   public static function recursiveRmdir($dir) {
      if (!file_exists($dir)) {
         return true;
      }
      if (!is_dir($dir)) {
         return unlink($dir);
      }
      $dirContent = scandir($dir);
      foreach ($dirContent as $item) {
         if ($item == '.' || $item == '..') {
            continue;
         }
         if (!self::recursiveRmdir($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
         }
      }
      return rmdir($dir);
   }

   /**
    * Mockable front for isAPI function of GLPI
    */
   public function isAPI() {
      return isAPI();
   }

   /**
    * Get the maximum value of a column for a given itemtype
    * @param CommonDBTM $item
    * @param string $condition
    * @param string $fieldName
    * @return NULL|string
    */
   public static function getMax(CommonDBTM $item, $condition, $fieldName) {
      if (version_compare(GLPI_VERSION, '9.4') < 0) {
         $order = "`$fieldName` DESC";
      } else {
         $order = ["$fieldName DESC"];
      }
      $rows = $item->find($condition, $order, '1');
      $line = array_pop($rows);
      if ($line === null) {
         return null;
      }
      return $line[$fieldName];
   }

   /**
    * http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    * @param string $haystack
    * @param string $needle
    * @return bool
    */
   public static function startsWith($haystack, $needle) {
      // search backwards starting from haystack length characters from the end
      return $needle === '' || strrpos($haystack, $needle, -strlen($haystack)) !== false;
   }

   /**
    * http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
    * @param string $haystack
    * @param string $needle
    * @return bool
    */
   public static function endsWith($haystack, $needle) {
      // search forward starting from end minus needle length characters
      return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack,
         $needle, $temp) !== false);
   }

   /**
    * Check XML format using part of the logic from FusionInventory
    *
    * @see PluginFusioninventoryCommunication::handleOCSCommunication()
    *
    * @param mixed $xml the xml string
    * @return SimpleXMLElement|boolean
    */
   public static function parseXML($xml) {
      if (($pxml = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA))) {
         return $pxml;
      }
      if (($pxml = @simplexml_load_string(utf8_encode($xml), 'SimpleXMLElement', LIBXML_NOCDATA))) {
         return $pxml;
      }

      $xml = preg_replace('/<FOLDER>.*?<\/SOURCE>/', '', $xml);
      $pxml = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
      return $pxml;
   }

   /**
    * @param mixed $content the xml string
    * @param string $filename the filename to be saved
    */
   public static function saveInventoryFile($content, $filename) {
      if (!is_dir(FLYVEMDM_INVENTORY_PATH)) {
         @mkdir(FLYVEMDM_INVENTORY_PATH, 0770, true);
      }
      $filename = ($filename) ? $filename : date("Ymd_Hi");
      file_put_contents(FLYVEMDM_INVENTORY_PATH . "/debug_" . $filename . ".xml", $content);
   }

   public static function getGlpiVersion() {
      return defined('GLPI_PREVER')
             ? GLPI_PREVER
             : GLPI_VERSION;
   }

   /**
    * Is the current user profile the agent profile (mdm device account) ?
    *
    * @return boolean
    */
   public static function isAgent() {
      $config = Config::getConfigurationValues('flyvemdm', ['agent_profiles_id']);
      return ($_SESSION['glpiactiveprofile']['id'] == $config['agent_profiles_id']);
   }

   /**
    * @param array $input
    * @return boolean
    */
   public static function checkAgentResponse(array $input) {
      if (!isset($input['_ack']) || !$input['_ack']) {
         return false;
      }

      return true;
   }

   /**
    * Is the curent loged user the machine account of this agent ?
    *
    * @param PluginFlyvemdmAgent $item
    * @return boolean
    */
   public static function isCurrentUser(PluginFlyvemdmAgent $item) {
      if ($item->isNewItem()) {
         return false;
      }
      return Session::getLoginUserID() == $item->getField(User::getForeignKeyField());
   }

   public static function convertTableColumnToTimestamp($tablename, Migration $migration){
      global $DB;

      $migration->displayWarning("DATETIME fields must be converted to TIMESTAMP for timezones to work for table ".$tablename." database ".$DB->dbdefault);

      $tbl_iterator = $DB->request([
         'SELECT'       => ['information_schema.columns.table_name as TABLE_NAME'],
         'FROM'         => 'information_schema.columns',
         'INNER JOIN'   => [
            'information_schema.tables' => [
               'ON' => [
                  'information_schema.tables.table_name',
                  'information_schema.columns.table_name', [
                     'AND' => ['information_schema.tables.table_type' => 'BASE TABLE']
                  ]
               ]
            ]
         ],
         'WHERE'       => [
            'information_schema.columns.table_schema' => $DB->dbdefault,
            'information_schema.columns.data_type'    => 'datetime',
            'information_schema.columns.table_name'   => $tablename
         ],
         'ORDER'       => [
            'information_schema.columns.table_name'
         ]
      ]);

      $migration->displayWarning(sprintf( __('Found %s table(s) requiring migration.'),
            $tbl_iterator->count()
         ));

      if ($tbl_iterator->count() === 0) {
         $migration->displayWarning(__('No migration needed.'));
         return; // Success
      }

      while ($table = $tbl_iterator->next()) {
         $tablealter = ''; // init by default

         // get accurate info from information_schema to perform correct alter
         $col_iterator = $DB->request([
            'SELECT' => [
               'table_name AS TABLE_NAME',
               'column_name AS COLUMN_NAME',
               'column_default AS COLUMN_DEFAULT',
               'column_comment AS COLUMN_COMMENT',
               'is_nullable AS IS_NULLABLE',
            ],
            'FROM'   => 'information_schema.columns',
            'WHERE'  => [
               'table_schema' => $DB->dbdefault,
               'table_name'   => $table['TABLE_NAME'],
               'data_type'    => 'datetime'
            ]
         ]);

         while ($column = $col_iterator->next()) {
            $nullable = false;
            $default = null;
            //check if nullable
            if ('YES' === $column['IS_NULLABLE']) {
               $nullable = true;
            }

            //guess default value
            if (is_null($column['COLUMN_DEFAULT']) && !$nullable) { // no default
               $default = null;
            } else if ((is_null($column['COLUMN_DEFAULT']) || strtoupper($column['COLUMN_DEFAULT']) == 'NULL') && $nullable) {
               $default = "NULL";
            } else if (!is_null($column['COLUMN_DEFAULT']) && strtoupper($column['COLUMN_DEFAULT']) != 'NULL') {
               if ($column['COLUMN_DEFAULT'] < '1970-01-01 00:00:01') {
                  // Prevent default value to be out of range (lower to min possible value)
                  $defaultDate = new \DateTime('1970-01-01 00:00:01', new \DateTimeZone('UTC'));
                  $defaultDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                  $default = $defaultDate->format("Y-m-d H:i:s");
               } else if ($column['COLUMN_DEFAULT'] > '2038-01-19 03:14:07') {
                  // Prevent default value to be out of range (greater to max possible value)
                  $defaultDate = new \DateTime('2038-01-19 03:14:07', new \DateTimeZone('UTC'));
                  $defaultDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                  $default = $defaultDate->format("Y-m-d H:i:s");
               } else {
                  $default = $column['COLUMN_DEFAULT'];
               }
            }

            //build alter
            $tablealter .= "\n\t MODIFY COLUMN ".$DB->quoteName($column['COLUMN_NAME'])." TIMESTAMP";
            if ($nullable) {
               $tablealter .= " NULL";
            } else {
               $tablealter .= " NOT NULL";
            }
            if ($default !== null) {
               if ($default !== 'NULL') {
                  $default = "'" . $DB->escape($default) . "'";
               }
               $tablealter .= " DEFAULT $default";
            }
            if ($column['COLUMN_COMMENT'] != '') {
               $tablealter .= " COMMENT '".$DB->escape($column['COLUMN_COMMENT'])."'";
            }
            $tablealter .= ",";
         }
         $tablealter =  rtrim($tablealter, ",");

         // apply alter to table
         $query = "ALTER TABLE " . $DB->quoteName($table['TABLE_NAME']) . " " . $tablealter.";\n";

         $migration->displayWarning(sprintf(__('Running %s'), $query));

         $result = $DB->query($query);
         if (false === $result) {
            $message = sprintf(
               __('Update of `%s` failed with message "(%s) %s".'),
               $table['TABLE_NAME'],
               $DB->errno(),
               $DB->error()
            );
            $migration->displayWarning($message);
         }
      }
   }

   /**
    * Determines the timezone to use
    * @return String timezone
    */
   public static function guessTimezone() {
      global $DB;
      //At the first connection if the user has defined a timezone in these preferences
      //GLPI set it in the $_SESSION
      if (isset($_SESSION['glpi_tz'])) {
         $zone = $_SESSION['glpi_tz'];
      } else {
         //If not set in $_SESSION try to load timezone from GLPI gerneral config
         $conf_tz = Config::getConfigurationValues('core', ['timezone']);
         //If empty load default timezone from PHP
         $zone = !empty($conf_tz['timezone']) ? $conf_tz['timezone'] : date_default_timezone_get();
      }

      return $zone;
   }
}
