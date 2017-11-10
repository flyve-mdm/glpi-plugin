<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2016 by the FusionInventory Development Team.
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
 * @author    Thierry Bugier Pineau
 * @author    Volker Theile <volker.theile@openmediavault.org>
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

/*
 * based on RPC daemon of Openmediavault
 * https://raw.githubusercontent.com/openmediavault/openmediavault/master/deb/openmediavault/usr/sbin/omv-engined
 */

abstract class Daemon implements DaemonInterface {
   private static $sigTerm = false;
   private static $sigChld = false;
   private $socket = null;
   private $maxConnections = 10;
   private $timeout = 1;
   private static $debug = false;
   private static $daemon = true;
   private $xdebug = false;
   private $pidFile = null;
   private static $children = [];

   private $stdIn = null;
   private $stdOut = null;
   private $stdErr = null;

   function main($argc, $argv) {
      set_error_handler(__CLASS__ . '::errorHandler');
      register_shutdown_function(__CLASS__ . '::shutdownHandler');

      $this->cmdName = basename($argv[0]);
      $this->pidFile = "/var/run/$this->cmdName.pid";

      // Check the command line arguments. Exit and display usage if
      // nessecary.
      $cmdArgs = [
         'd::' => 'debug::',
         'f::' => 'foreground::',
         'h::' => 'help::',
         'x::' => 'xdebug::'
      ];
      $options = getopt(implode('', array_keys($cmdArgs)), $cmdArgs);
      foreach ($options as $optionk => $optionv) {
         switch ($optionk) {
            case 'd':
            case 'debug':
               $argc -= 1;
               self::$debug = true;
               break;
            case 'f':
            case 'foreground':
               $argc -= 1;
               self::$daemon = false;
               break;
            case 'h':
            case 'help':
               $this->usage();
               exit(0);
               break;
            case 'x':
            case 'xdebug':
               $argc -= 1;
               $this->xdebug = true;
               break;
         }
      }
      if ($argc > 1) {
         print gettext('ERROR: Invalid number of arguments\n');
         $this->usage();
         exit(1);
      }

      ini_set('max_execution_time', '0');
      ini_set('max_input_time', '0');
      set_time_limit(0);

      // Open syslog, include the process ID and also send the log to
      // standard error.
      openlog($this->cmdName, LOG_PID | LOG_PERROR, LOG_USER);

      // Change process name.
      cli_set_process_title($this->cmdName);

      $this->daemonize();

      if (!pcntl_signal(SIGINT,  __CLASS__ . '::signalHandler')
          || !pcntl_signal(SIGTERM, __CLASS__ . '::signalHandler')
          || !pcntl_signal(SIGCHLD, __CLASS__ . '::signalHandler')) {
         $this->debug('Failed to setup signal handlers' . PHP_EOL);
      }
      // These mthods are for debug purpose, but seems avaialble starting PHP 7.1 only (undocumented)
      // http://php.net/manual/fr/migration71.new-functions.php
      //$this->debug(pcntl_signal_get_handler(SIGINT) . PHP_EOL);
      //$this->debug(pcntl_signal_get_handler(SIGTERM) . PHP_EOL);
      //$this->debug(pcntl_signal_get_handler(SIGCHLD) . PHP_EOL);

      $this->loop();
   }

   /**
    * Display command usage.
    */
   private function usage() {
      $text = <<<EOF
   The MQTT subscriber daemon. MQTT messages will be received via a socket.
   Usage:
     %s [options]

   OPTIONS:
     -d --debug       Enable debug mode
     -f --foreground  Run in foreground
     -h --help        Print a help text
     -x --xdebug      Enable XDebug compatibility mode

EOF;
      printf($text, $this->cmdName);
   }

   /**
    * Signal handler function.
    *
    * @param signal The signal.
    */
   public static function signalHandler($signal) {
      switch ($signal) {
         case SIGINT:
            self::debug("SIGINT received ...\n");
            self::$sigTerm = true;
            break;
         case SIGTERM:
            self::debug("SIGTERM received ...\n");
            self::$sigTerm = true;
            break;
         case SIGCHLD:
            self::debug("SIGCHLD received ...\n");
            self::$sigChld = true;
            break;
         default:
            // Nothing to do here.
            break;
      }
   }

   /**
    * Process SIGCHLD signals.
    */
   private static function handleSigChld() {
      $status = null;
      while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
         foreach (self::$children as $childk => $childv) {
            if ($childv !== $pid) {
               continue;
            }
            unset(self::$children[$childk]);
            if (pcntl_wifexited($status)) {
               self::debug("Child (pid=%d) terminated with exit code %d\n",
                     $pid, pcntl_wexitstatus($status));
            } else {
               self::debug("Child (pid=%d) terminated with signal %d\n",
                     $pid, pcntl_wtermsig($status));
            }
            break;
         }
      }
      self::$sigChld = false;
   }

   /**
    * Kill all child processes.
    */
   private static function killChld() {
      foreach (self::$children as $childv) {
         if (posix_kill($childv, SIGTERM)) {
            self::debug("Send SIGTERM to child (pid=%d)\n", $childv);
         }
      }
      while (!empty(self::$children)) {
         debug("Waiting for children to terminate ...\n");
         self::handleSigChld();
         usleep(1000);
      }
   }

   /**
    * Daemonize the application.
    * @see http://www.freedesktop.org/software/systemd/man/daemon.html
    * @see http://stackoverflow.com/a/17955149
    * @see https://stackoverflow.com/questions/881388/what-is-the-reason-for-performing-a-double-fork-when-creating-a-daemon
    */
   private function daemonize() {
      if (!self::$daemon) {
         return;
      }

      // Check if PID file already exists and whether a daemon is already
      // running.
      if (file_exists($this->pidFile)) {
         $pid = file_get_contents($this->pidFile);
         if(posix_kill($pid, 0) === true) {
            self::error("Daemon already running (pid=%d)\n", $pid);
            exit(1);
         }
         unlink($this->pidFile);
      }

      $pid = pcntl_fork();
      if ($pid == -1) {
         self::error("Failed to fork process\n");
         exit(1);
      } else if($pid) { // Parent process
         exit(0);
      }

      // Make the current process a session leader.
      if (posix_setsid() < 0) {
         self::error("Could not detach from terminal\n");
         exit(1);
      }

      // Ignore signals.
      pcntl_signal(SIGHUP, SIG_IGN);

      // If starting a process on the command line, the shell will become the
      // session leader of that command. To create a new process group with the
      // daemon as session leader it is necessary to fork a new process again.
      $pid = pcntl_fork();
      if ($pid == -1) {
         self::error("Failed to fork process\n");
         exit(1);
      } else if($pid) { // Parent process
         self::debug("Daemon process started (pid=%d)\n", $pid);
         // Exit parent process.
         exit(0);
      }

      // Change the current working directory.
      if (chdir(__DIR__) === false) {
         self::error("Failed to change current directory\n");
         exit(1);
      }

      // Create PID file.
      file_put_contents($this->pidFile, posix_getpid());

      if (!$this->debug) {
         // Close all of the standard file descriptors.
         if (is_resource(STDIN))  fclose(STDIN);
         if (is_resource(STDOUT)) fclose(STDOUT);
         if (is_resource(STDERR)) fclose(STDERR);
         // Create new standard file descriptors.
         $this->stdIn = fopen("/dev/null", "r");
         $this->stdOut = fopen("/dev/null", "w");
         $this->stdErr = fopen("/dev/null", "w");
      }
   }

   /**
    * Error function. Output message to system log and console in debug mode.
    * @param msg The error message.
    */
   private static function error() {
      $args = func_get_args();
      $msg = array_shift($args);
      // Log the message in syslog.
      syslog(LOG_ALERT, vsprintf($msg, $args));
      // Print the message to STDOUT if debug mode is enabled.
      if (self::$debug) {
         // Append a new line if necessary.
         if ("\n" !== substr($msg, -1))
            $msg .= "\n";
            // Print the message to STDOUT.
            vprintf($msg, $args);
      }
   }

   /**
    * Debug function. Output message to syslog or console in debug mode.
    * @param msg The debug message.
    */
   private static function debug() {
      $args = func_get_args();
      $msg = array_shift($args);
      if (self::$debug) {
         if (!self::$daemon) {
            vprintf($msg, $args);
         } else {
            syslog(LOG_DEBUG, vsprintf($msg, $args));
         }
      }
   }

   /**
    * The error handler.
    */
   public static function errorHandler($errno, $errstr, $errfile, $errline) {
      switch ($errno) {
         case E_RECOVERABLE_ERROR:
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            break;
         default:
            // Nothing to do here.
            break;
      }
      // Don't execute the PHP internal error handler.
      return true;
   }

   /**
    * The function for execution on shutdown.
    */
   public static function shutdownHandler() {
      // Check if there was a fatal error.
      $error = error_get_last();
      if (!is_null($error) && (E_ERROR == $error['type'])) {
         // Log fatal errors to syslog.
         self::error("PHP Fatal error: %s in %s on line %d", $error['message'],
         $error['file'], $error['line']);
      }
   }

   protected function getDebug() {
      return self::$debug;
   }
}
