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

include ('../../../inc/includes.php');
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

$market = new PluginFlyvemdmFDroidMarket();
if (isset($_POST["add"])) {
   $market->check(-1, CREATE, $_POST);
   if ($newID = $market->add($_POST)) {
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($market->getFormURL() . "?id=" . $newID);
      }
   }
   Html::back();
} else if (isset($_POST["update"])) {
   $market->check($_POST['id'], UPDATE);
   $market->update($_POST);
   Html::back();
} else if (isset($_POST["purge"])) {
   $market->check($_POST['id'], PURGE);
   $market->delete($_POST, 1);
   $market->redirectToList();
} else if (isset($_POST['refresh'])) {
   $fdroidMarket = new PluginFlyvemdmFDroidMarket();
   $fdroidMarket->getFromDB((int) $_POST['id']);
   $volume = $fdroidMarket->updateRepository();
   Html::back();
} else {
   $market->check($_GET['id'], READ);
   Html::header(
      PluginFlyvemdmFDroidApplication::getTypeName(Session::getPluralNumber()),
      '',
      'admin',
      PluginFlyvemdmMenu::class,
      'fdroid market'
   );

   $menu = new PluginFlyvemdmMenu();
   $menu->displayMenu('mini');

   $market->display([
      'id' => $_GET['id'],
      'withtemplate' => $_GET['withtemplate']
   ]);

   // Footer
   if (strstr($_SERVER['PHP_SELF'], 'popup')) {
      Html::popFooter();
   } else {
      Html::footer();
   }
}
