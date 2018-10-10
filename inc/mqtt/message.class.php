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

namespace GlpiPlugin\Flyvemdm\Mqtt;

use InvalidArgumentException;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmMqttMessage {

   private $message;
   private $recipients;
   private $options = [];

   public function __construct($message, $topic, array $options = []) {
      if (!isset($message)) {
         throw new InvalidArgumentException(__('A message argument is needed', 'flyvemdm'));
      }
      $this->message = $message;

      if (!isset($topic)) {
         throw new InvalidArgumentException(__('A recipient argument is needed', 'flyvemdm'));
      }
      $this->recipients = $topic;

      if (!isset($options['qos'])) {
         $options['qos'] = 0;
      }
      if (!isset($options['retain'])) {
         $options['retain'] = 0;
      }
      $this->options = $options;
   }

   public function getRecipients() {
      return $this->recipients;
   }

   public function getMessage() {
      return $this->message;
   }

   public function getQos() {
      return $this->options['qos'];
   }

   public function getRetain() {
      return $this->options['retain'];
   }
}