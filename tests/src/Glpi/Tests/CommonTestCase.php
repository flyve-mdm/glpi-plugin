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

namespace Glpi\Tests;
use Session;
use Html;
use DB;
use Auth;

abstract class CommonTestCase extends CommonDBTestCase {
   protected $str = null;

   public function beforeTestMethod($method) {
      self::resetGLPILogs();
   }

   protected function resetState() {
      self::resetGLPILogs();

      $DBvars = get_class_vars('DB');
      $this->drop_database(
         $DBvars['dbuser'],
         $DBvars['dbhost'],
         $DBvars['dbdefault'],
         $DBvars['dbpassword']
      );

      $this->load_mysql_file($DBvars['dbuser'],
         $DBvars['dbhost'],
         $DBvars['dbdefault'],
         $DBvars['dbpassword'],
         './save.sql'
      );
   }

   protected function resetGLPILogs() {
      // Reset error logs
      file_put_contents(GLPI_LOG_DIR."/sql-errors.log", '');
      file_put_contents(GLPI_LOG_DIR."/php-errors.log", '');
   }

   protected function login($name, $password, $noauto = false) {
      global $LOADED_PLUGINS, $AJAX_INCLUDE, $PLUGINS_INCLUDED;

      $glpi_use_mode = $_SESSION['glpi_use_mode'];
      $this->terminateSession(); // force clean up current session

      $LOADED_PLUGINS = null;
      $PLUGINS_INCLUDED = null;
      $AJAX_INCLUDE = null;

      \Session::start();
      $_SESSION['glpi_use_mode'] = $glpi_use_mode;

      $auth = new \Auth();
      if (defined('GLPI_PREVER') && version_compare('9.2', rtrim(GLPI_VERSION, '-dev'), 'lt')) {
         // GLPI 9.3 and upper has this method
         $result = $auth->login($name, $password, $noauto, false, 'local');
      } else {
         // older versions use this one
         $result = $auth->Login($name, $password, $noauto);
      }
      include GLPI_ROOT . "/inc/includes.php";

      return $result;
   }

   public function afterTestMethod($method) {
      // Check logs
      $fileSqlContent = file_get_contents(GLPI_LOG_DIR."/sql-errors.log");
      $filePhpContent = file_get_contents(GLPI_LOG_DIR."/php-errors.log");

      $class = static::class;
      $class = str_replace('\\', '_', $class);
      if ($fileSqlContent != '') {
         rename(GLPI_LOG_DIR."/sql-errors.log", GLPI_LOG_DIR."/sql-errors__${class}__$method.log");
      }
      if ($fileSqlContent != '') {
         rename(GLPI_LOG_DIR."/php-errors.log", GLPI_LOG_DIR."/php-errors__${class}__$method.log");
      }

      // Reset log files
      self::resetGLPILogs();

      // Test content
      $this->variable($fileSqlContent)->isEqualTo('', 'sql-errors.log not empty' . PHP_EOL . $fileSqlContent);
      $this->variable($filePhpContent)->isEqualTo('', 'php-errors.log not empty' . PHP_EOL . $filePhpContent);
   }

   protected function loginWithUserToken($userToken) {
      // Login as guest user
      $_REQUEST['user_token'] = $userToken;
      self::login('', '', false);
      unset($_REQUEST['user_token']);
   }

   /**
    * Get a unique random string
    */
   protected function getUniqueString() {
      if (is_null($this->str)) {
         return $this->str = uniqid('str');
      }
      return $this->str .= 'x';
   }

   protected function getUniqueEmail() {
      return $this->getUniqueString() . "@example.com";
   }

   public function getMockForItemtype($classname, $methods = []) {
      // create mock
      $mock = $this->getMockBuilder($classname)
                   ->setMethods($methods)
                   ->getMock();

      //Override computation of table to match the original class name
      // see CommonDBTM::getTable()
      $dbUtils = new \DbUtils;
      $_SESSION['glpi_table_of'][get_class($mock)] = $dbUtils->getTableForItemType($classname);

      return $mock;
   }

   protected function terminateSession() {
      // based on glpi logout script
      Session::destroy();

      //Remove cookie to allow new login
      $cookie_name = session_name() . '_rememberme';
      $cookie_path = ini_get('session.cookie_path');

      if (isset($_COOKIE[$cookie_name])) {
         setcookie($cookie_name, '', time() - 3600, $cookie_path);
         unset($_COOKIE[$cookie_name]);
      }
   }

   protected function restartSession() {
      if (session_status() != PHP_SESSION_ACTIVE) {
         session_start();
         session_regenerate_id();
         session_id();
         //$_SESSION["MESSAGE_AFTER_REDIRECT"] = [];
      }
   }

   /**
    * Tests the session has a specific message
    * this may be replaced by a custom asserter for atoum
    * @see http://docs.atoum.org/en/latest/asserters.html#custom-asserter
    *
    * @param string $message
    * @param integer $message_type
    */
   protected function sessionHasMessage($message, $message_type = INFO) {
      if (!is_array($message)) {
         $message = [$message];
      }
      $this->array($_SESSION['MESSAGE_AFTER_REDIRECT'][$message_type])
         ->containsValues($message);
   }
}
