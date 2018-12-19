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

namespace GlpiPlugin\Flyvemdm\Broker;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


class BrokerHandlerLocator {
   /**
    * @param $message
    * @return callable|null
    * @throws \Exception
    */
   public function resolve($message) {
      $class = \get_class($message);
      if ($handler = $this->getHandler($class)) {
         return $handler;
      }
      foreach (class_implements($class, false) as $interface) {
         if ($handler = $this->getHandler($interface)) {
            return $handler;
         }
      }
      foreach (class_parents($class, false) as $parent) {
         if ($handler = $this->getHandler($parent)) {
            return $handler;
         }
      }
      throw new \Exception(sprintf(__('No handler for message "%s".', 'flyvemdm'), $class));
   }

   /**
    * @param $class
    * @return callable|null
    */
   protected function getHandler($class) {
      if (class_exists($class)) {
         return new $class;
      }
      return null;
   }
}