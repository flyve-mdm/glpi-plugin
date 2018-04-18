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

include '../../../inc/includes.php';
$plugin = new Plugin();
if (!$plugin->isActivated('flyvemdm')) {
   Html::displayNotFoundError();
}

Session::checkRight('flyvemdm:flyvemdm', PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE);
Session::checkRight('config', UPDATE);

$config = new Config();
$pluginConfig = $pluginFlyvemdmContainer->make(PluginFlyvemdmConfig::class);
if (isset($_POST['update']) || isset($_POST['back'])) {
   $config->update($_POST);
   Html::redirect(Toolbox::getItemTypeFormURL(PluginFlyvemdmConfig::class));
} else if (isset($_POST['addDocTypes'])) {
   $pluginConfig->addDocumentTypes();
   Html::redirect(Toolbox::getItemTypeFormURL(PluginFlyvemdmConfig::class));
} else {
   // Header

   Html::header(
      __('Configuration'),
      '',
      'admin',
      PluginFlyvemdmMenu::class,
      'config'
   );

   $menu = $pluginFlyvemdmContainer->make(PluginFlyvemdmMenu::class);
   $menu->displayMenu('mini');
   // To add vertical space after the menu
   echo '<div class="navigationheader">&nbsp;</div>';

   $pluginConfig->display(['id' => 1]);
   // Footer

   if (strstr($_SERVER['PHP_SELF'], 'popup')) {
      Html::popFooter();
   } else {
      Html::footer();
   }
}
