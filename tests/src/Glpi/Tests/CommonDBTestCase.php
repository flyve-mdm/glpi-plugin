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
use atoum;

class CommonDBTestCase extends atoum {

   protected function drop_database($dbuser = '', $dbhost = '', $dbdefault = '', $dbpassword = '') {
      $cmd = $this->construct_mysql_options($dbuser, $dbhost, $dbpassword, 'mysql');

      if (is_array($cmd)) {
         return $cmd;
      }

      $cmd = 'echo "DROP DATABASE IF EXISTS \`'.$dbdefault .'\`; CREATE DATABASE \`'.$dbdefault.'\`" | ' . $cmd ." 2>&1";

      $returncode = 0;
      $output = [];
      exec(
         $cmd,
         $output,
         $returncode
      );
      array_unshift($output, "Output of '{$cmd}'");
      return [
         'returncode'   => $returncode,
         'output'       => $output
      ];
   }

   protected function load_mysql_file($dbuser = '', $dbhost = '', $dbdefault = '', $dbpassword = '', $file = null) {
      if (!file_exists($file)) {
         return [
            'returncode' => 1,
            'output' => ["ERROR: File '$file' does not exist !"]
         ];
      }

      $result = $this->construct_mysql_options($dbuser, $dbhost, $dbpassword, 'mysql');

      if (is_array($result)) {
         return $result;
      }

      $cmd = $result . " " . $dbdefault . " < ". $file ." 2>&1";

      $returncode = 0;
      $output = [];
      exec(
            $cmd,
            $output,
            $returncode
            );
      array_unshift($output, "Output of '$cmd'");
      return [
         'returncode'   => $returncode,
         'output'       => $output
      ];
   }

   protected function construct_mysql_options($dbuser = '', $dbhost = '', $dbpassword = '', $cmd_base = 'mysql') {
      if (empty($dbuser) || empty($dbhost)) {
         return [
            'returncode' => 2,
            'output' => ["ERROR: missing mysql parameters (user='{$dbuser}', host='{$dbhost}')"]
         ];
      }
      $cmd = [$cmd_base];

      if (strpos($dbhost, ':') !== false) {
         $dbhost = explode( ':', $dbhost);
         if (!empty($dbhost[0])) {
            $cmd[] = "--host ".$dbhost[0];
         }
         if (is_numeric($dbhost[1])) {
            $cmd[] = "--port ".$dbhost[1];
         } else {
            // The dbhost's second part is assumed to be a socket file if it is not numeric.
            $cmd[] = "--socket ".$dbhost[1];
         }
      } else {
         $cmd[] = "--host ".$dbhost;
      }

      $cmd[] = "--user ".$dbuser;

      if (!empty($dbpassword)) {
         $cmd[] = "-p'".urldecode($dbpassword)."'";
      }

      return implode(' ', $cmd);
   }

   protected function mysql_dump($dbuser = '', $dbhost = '', $dbpassword = '', $dbdefault = '', $file = null) {
      if (is_null($file) or empty($file)) {
         return [
            'returncode' => 1,
            'output' => ["ERROR: mysql_dump()'s file argument must neither be null nor empty"]
         ];
      }

      if (empty($dbdefault)) {
         return [
            'returncode' => 2,
            'output' => ["ERROR: mysql_dump() is missing dbdefault argument."]
         ];
      }

      $result = self::construct_mysql_options($dbuser, $dbhost, $dbpassword, 'mysqldump');
      if (is_array($result)) {
         return $result;
      }

      $cmd = $result . ' --opt '. $dbdefault.' > ' . $file;
      $returncode = 0;
      $output = [];
      exec(
            $cmd,
            $output,
            $returncode
            );
      array_unshift($output, "Output of '{$cmd}'");
      return [
         'returncode'=>$returncode,
         'output' => $output
      ];
   }
}
