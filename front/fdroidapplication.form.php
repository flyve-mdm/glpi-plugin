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

$fdroidApplication = new PluginFlyvemdmFDroidApplication();
if (isset($_POST['import'])) {
   unset($_POST['_skip_checks']);
   $fdroidApplication->update(['id' => $_POST['id'], 'import_status' => 'to_import']);
   Html::back();
} else if (isset($_POST['update'])) {
   $fdroidApplication->check($_POST['id'], UPDATE);
   $fdroidApplication->update(['id' => $_POST['id'], 'is_auto_upgradable' => $_POST['is_auto_upgradable']]);
   Html::back();
} else {
   $fdroidApplication->check($_GET['id'], READ);
   Html::header(
      PluginFlyvemdmFDroidApplication::getTypeName(Session::getPluralNumber()),
      '',
      'admin',
      PluginFlyvemdmMenu::class,
      'fdroid application'
   );

   $menu = new PluginFlyvemdmMenu();
   $menu->displayMenu('mini');

   $fdroidApplication->display([
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
