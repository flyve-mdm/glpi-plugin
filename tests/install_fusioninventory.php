<?php

/**
 * This script wraps the instaler of Fusion Ivnentory to allow installation on GLPI 9.4+
 * workarounding an instllation issue related to Plugin::checkState()
 * Without calling this method, Fusin Inventory is not added in the DB and prevents
 * its cli instaler to work (call to a not loaded method in setup.php)
 * 
 * The scripts also force a custom CONFIG_DIR path similarly to cli installer in
 * GLPI 9.5
 */
if (!$glpiConfigDir = getenv('GLPI_CONFIG_DIR')) {
    echo "Environment var GLPI_CONFIG_DIR is not set" . PHP_EOL;
    exit(1);
}
 
define('GLPI_ROOT', realpath(__DIR__ . '/../../../'));
define("GLPI_CONFIG_DIR", GLPI_ROOT . "/$glpiConfigDir");
if (!file_exists(GLPI_CONFIG_DIR . '/config_db.php')) {
   echo GLPI_ROOT . "/$glpiConfigDir/config_db.php missing. Did GLPI successfully initialized ?\n";
   exit(1);
}
unset($glpiConfigDir);

$_SERVER["SCRIPT_FILENAME"] = realpath(__DIR__ . '/../../fusioninventory/scripts/cli_install.php');
include (__DIR__ . "/../../../inc/includes.php");
$plugin = new Plugin();
$plugin->checkStates(true);
require_once __DIR__ . '/../../fusioninventory/scripts/cli_install.php';