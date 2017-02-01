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

include ('../../../inc/includes.php');
Session::checkRight("flyvemdm:flyvemdm", PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$fleet = new PluginFlyvemdmFleet();
if (isset($_POST["add"])) {
   $fleet->check(-1, CREATE, $_POST);
   if ($newID = $fleet->add($_POST)) {
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($fleet->getFormURL() . "?id=" . $newID);
      }
   }
   Html::back();
} else if (isset($_POST["update"])) {
   $fleet->check($_POST['id'], UPDATE);
   $fleet->update($_POST);
   Html::back();
} else if (isset($_POST["purge"])) {
   $fleet->check($_POST['id'], PURGE);
   $fleet->delete($_POST, 1);
   $fleet->redirectToList();
} else {
   Html::header(
         PluginFlyvemdmFleet::getTypeName(Session::getPluralNumber()),
         "",
         "tools",
         "PluginFlyvemdmMenu",
         "fleet"
   );
   $fleet->display(array('id' => $_GET["id"],
         'withtemplate' => $_GET["withtemplate"]));

   // Footer
   if (strstr($_SERVER['PHP_SELF'], "popup")) {
      Html::popFooter();
   } else {
      Html::footer();
   }
}
