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
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

include '../../../inc/includes.php';
$plugin = new Plugin();
if (!$plugin->isActivated('flyvemdm')) {
   Html::displayNotFoundError();
}

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$flyveFile = new PluginFlyvemdmFile();
if (isset($_POST['add'])) {
   $flyveFile->check(-1, CREATE, $_POST);
   $flyveFile->add($_POST);
   Html::back();
} else if (isset($_POST['update'])) {
   $flyveFile->check(-1, UPDATE, $_POST);
   $flyveFile->update($_POST);
   Html::back();
} else {
   $flyveFile->check($_GET['id'], READ);
   Html::header(
         PluginFlyvemdmFile::getTypeName(Session::getPluralNumber()),
         '',
         'admin',
         PluginFlyvemdmMenu::class,
         'file'
   );

   $menu = new PluginFlyvemdmMenu();
   $menu->displayMenu('mini');

   $flyveFile->display([
      'id' => $_GET["id"], 'withtemplate' => $_GET["withtemplate"]
   ]);

   // Footer
   if (strstr($_SERVER['PHP_SELF'], "popup")) {
      Html::popFooter();
   } else {
      Html::footer();
   }
}
