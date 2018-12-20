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

include '../../../inc/includes.php';
$plugin = new Plugin();
if (!$plugin->isActivated('flyvemdm')) {
   Html::displayNotFoundError();
}

Session::checkRight('flyvemdm:flyvemdm', PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE);

if (!isset($_GET['id'])) {
   $_GET['id'] = '';
}

if (!isset($_GET['withtemplate'])) {
   $_GET['withtemplate'] = '';
}

$package = new PluginFlyvemdmPackage();
if (isset($_POST['add'])) {
   $package->check(-1, CREATE, $_POST);
   $package->add($_POST);
   Html::back();
} else if (isset($_POST['update'])) {
   $package->check($_POST['id'], UPDATE, $_POST);
   $package->update($_POST);
   Html::back();
} else if (isset($_POST['purge'])) {
   $package->check($_POST['id'], PURGE);
   $package->delete($_POST, 1);
   $package->redirectToList();
} else {
   Html::header(
         PluginFlyvemdmPackage::getTypeName(Session::getPluralNumber()),
         '',
         'admin',
         PluginFlyvemdmMenu::class,
         'package'
         );

   $menu = new PluginFlyvemdmMenu();
   $menu->displayMenu('mini');

   $package->display([
      'id' => $_GET['id'],
      'withtemplate' => $_GET["withtemplate"]
   ]);

   // Footer
   if (strstr($_SERVER['PHP_SELF'], 'popup')) {
      Html::popFooter();
   } else {
      Html::footer();
   }
}
