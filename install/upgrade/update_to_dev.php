<?php
/**
 LICENSE

This file is part of the flyvemdm plugin.

Order plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Order plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI; along with flyvemdm. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
@package   flyvemdm
@author    the flyvemdm plugin team
@copyright Copyright (c) 2015 flyvemdm plugin team
@license   GPLv2+ http://www.gnu.org/licenses/gpl.txt
@link      https://github.com/teclib/flyvemdm
@link      http://www.glpi-project.org/
@since     0.1.0
----------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

function plugin_flyvemdm_update_to_dev(Migration $migration) {
   global $DB;

   $migration->setVersion(PLUGIN_FLYVEMDM_VERSION);

}
