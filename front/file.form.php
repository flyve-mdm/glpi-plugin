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

// Most content of this file has been altered to make a temporary endpoint for package upload
// TODO: urgent - handle file uploads from the rest api


include ('../../../inc/includes.php');
if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
   header("OK", false, 200);
   die();
}

// Get session from token (really ugly !) $_SESSION
$api = new APIRest();
$api->parseIncomingParams();
$api->retrieveSession();

//Session::checkRight("storkmdm:storkmdm", PluginStorkmdmProfile::RIGHT_STORKMDM_USE);
if (! Session::haveRight('storkmdm:storkmdm', PluginStorkmdmProfile::RIGHT_STORKMDM_USE)) {
   header("Not allowed", false, 401);
   die();
}

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$file = new PluginStorkmdmFile();
$_POST['add'] = '';
if (isset($_POST['add'])) {
   //$file->check(-1, CREATE, $_POST);
   $jsonAnswer = array();
   if ($file->canCreate()) {
      if ($newID = $file->add($_POST)) {
         $jsonAnswer = [
               'id'  => $newID,
         ];
         if ($_SESSION['glpibackcreated']) {
            //Html::redirect($package->getFormURL() . "?id=" . $newID);
         }
      }
   } else {
      header("Not allowed", false, 401);
   }
   echo json_encode($jsonAnswer, JSON_UNESCAPED_SLASHES);
   die();
   //Html::back();
} else {
   die();
}