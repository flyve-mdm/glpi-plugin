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
 @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmCommon
{
   const SEMVER_VERSION_REGEX = '#\bv?(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)(?:-[\da-z\-]+(?:\.[\da-z\-]+)*)?(?:\+[\da-z\-]+(?:\.[\da-z\-]+)*)?\b#i';

   public static function convertToGiB($size) {
      $units = ['KiB', 'MiB', 'GiB', 'TiB'];
      $unit = 'B';

      while ($size > 1024 && count($units) > 0) {
         $size = $size / 1024;
         $unit = array_shift($units);
      }
      $size = Html::formatNumber($size, false, 2);
      return "$size $unit";
   }

   public static function getMassiveActions($massiveactionparams) {
      ob_start();
      Html::showMassiveActions($massiveactionparams);
      $html = ob_get_clean();

      return $html;
   }

}