#!/usr/bin/php
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

$sigTerm = false;
$sigChld = false;
$socket = null;
$maxConnections = 10;
$timeout = 1;
$debug = false;
$daemon = true;
$xdebug = false;
$pidFile = null;
$children = [];

$stdIn = null;
$stdOut = null;
$stdErr = null;

set_error_handler("errorHandler");
register_shutdown_function("shutdownHandler");

function main() {
   global $argc, $argv, $debug, $daemon, $xdebug, $pidFile;
   global $CFG_GLPI, $PLUGIN_HOOKS;

   $cmdName = basename($argv[0]);
   $pidFile = "/var/run/$cmdName.pid";

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
            $debug = true;
            break;
         case 'f':
         case 'foreground':
            $argc -= 1;
            $daemon = false;
            break;
         case 'h':
         case 'help':
            usage();
            exit(0);
            break;
         case 'x':
         case 'xdebug':
            $argc -= 1;
            $xdebug = true;
            break;
      }
   }
   if ($argc > 1) {
      print gettext('ERROR: Invalid number of arguments\n');
      usage();
      exit(1);
   }

   ini_set('max_execution_time', '0');
   ini_set('max_input_time', '0');
   set_time_limit(0);

   // Open syslog, include the process ID and also send the log to
   // standard error.
   openlog($cmdName, LOG_PID | LOG_PERROR, LOG_USER);

   // Change process name.
   cli_set_process_title($cmdName);

   daemonize();

   pcntl_signal(SIGINT,  'signalHandler');
   pcntl_signal(SIGTERM, 'signalHandler');
   pcntl_signal(SIGCHLD, 'signalHandler');

   include (__DIR__ . '/../../../inc/includes.php');

   $mqttClient = PluginFlyvemdmMqttclient::getInstance();
   $mqttClient->setHandler(PluginFlyvemdmMqtthandler::getInstance());
   $mqttClient->subscribe();
}

/**
 * Display command usage.
 */
function usage() {
   global $argv, $cmdName;

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
   printf($text, $cmdName);
}

/**
 * Signal handler function.
 *
 * @param signal The signal.
 */
function signalHandler($signal) {
   global $sigTerm, $sigChld;

   switch($signal) {
      case SIGINT:
         debug("SIGINT received ...\n");
         $sigTerm = true;
         break;
      case SIGTERM:
         debug("SIGTERM received ...\n");
         $sigTerm = true;
         break;
      case SIGCHLD:
         debug("SIGCHLD received ...\n");
         $sigChld = true;
         break;
      default:
         // Nothing to do here.
         break;
   }
}

/**
 * Process SIGCHLD signals.
 */
function handleSigChld() {
   global $sigChld, $children;

   $status = null;
   while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
      foreach ($children as $childk => $childv) {
         if ($childv !== $pid) {
            continue;
         }
         unset($children[$childk]);
         if (pcntl_wifexited($status)) {
            debug("Child (pid=%d) terminated with exit code %d\n",
                  $pid, pcntl_wexitstatus($status));
         } else {
            debug("Child (pid=%d) terminated with signal %d\n",
                  $pid, pcntl_wtermsig($status));
         }
         break;
      }
   }
   $sigChld = false;
}

/**
 * Kill all child processes.
 */
function killChld() {
   global $children;

   foreach($children as $childk => $childv) {
      if(posix_kill($childv, SIGTERM)) {
         debug("Send SIGTERM to child (pid=%d)\n", $childv);
      }
   }
   while(!empty($children)) {
      debug("Waiting for children to terminate ...\n");
      handleSigChld();
      usleep(1000);
   }
}

/**
 * Daemonize the application.
 * @see http://www.freedesktop.org/software/systemd/man/daemon.html
 * @see http://stackoverflow.com/a/17955149
 * @see https://stackoverflow.com/questions/881388/what-is-the-reason-for-performing-a-double-fork-when-creating-a-daemon
 */
function daemonize() {
   global $debug, $daemon, $pidFile, $stdIn, $stdOut, $stdErr;

   if($daemon === false) {
      return;
   }

   // Check if PID file already exists and whether a daemon is already
   // running.
   if(file_exists($pidFile)) {
      $pid = file_get_contents($pidFile);
      if(posix_kill($pid, 0) === true) {
         error("Daemon already running (pid=%d)\n", $pid);
         exit(1);
      }
      unlink($pidFile);
   }

   $pid = pcntl_fork();
   if($pid == -1) {
      error("Failed to fork process\n");
      exit(1);
   } else if($pid) { // Parent process
      exit(0);
   }

   // Make the current process a session leader.
   if(posix_setsid() < 0) {
      error("Could not detach from terminal\n");
      exit(1);
   }

   // Ignore signals.
   pcntl_signal(SIGHUP, SIG_IGN);

   // If starting a process on the command line, the shell will become the
   // session leader of that command. To create a new process group with the
   // daemon as session leader it is necessary to fork a new process again.
   $pid = pcntl_fork();
   if($pid == -1) {
      error("Failed to fork process\n");
      exit(1);
   } else if($pid) { // Parent process
      debug("Daemon process started (pid=%d)\n", $pid);
      // Exit parent process.
      exit(0);
   }

   // Change the current working directory.
   if(chdir(__DIR__) === false) {
      error("Failed to change current directory\n");
      exit(1);
   }

   // Create PID file.
   file_put_contents($pidFile, posix_getpid());

   if($debug === false) {
      // Close all of the standard file descriptors.
      if(is_resource(STDIN))  fclose(STDIN);
      if(is_resource(STDOUT)) fclose(STDOUT);
      if(is_resource(STDERR)) fclose(STDERR);
      // Create new standard file descriptors.
      $stdIn = fopen("/dev/null", "r");
      $stdOut = fopen("/dev/null", "w");
      $stdErr = fopen("/dev/null", "w");
   }
}

/**
 * Error function. Output message to system log and console in debug mode.
 * @param msg The error message.
 */
function error() {
   global $debug;

   $args = func_get_args();
   $msg = array_shift($args);
   // Log the message in syslog.
   syslog(LOG_ALERT, vsprintf($msg, $args));
   // Print the message to STDOUT if debug mode is enabled.
   if (true === $debug) {
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
function debug() {
   global $debug, $daemon;

   $args = func_get_args();
   $msg = array_shift($args);
   if (true === $debug) {
      if (false === $daemon) {
         vprintf($msg, $args);
      } else {
         syslog(LOG_DEBUG, vsprintf($msg, $args));
      }
   }
}

/**
 * The error handler.
 */
function errorHandler($errno, $errstr, $errfile, $errline) {
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
function shutdownHandler() {
   // Check if there was a fatal error.
   $error = error_get_last();
   if (!is_null($error) && (E_ERROR == $error['type'])) {
      // Log fatal errors to syslog.
      error("PHP Fatal error: %s in %s on line %d", $error['message'],
      $error['file'], $error['line']);
   }
}

main();
