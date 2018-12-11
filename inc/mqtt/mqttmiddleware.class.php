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
 * @author    Domingo Oropeza <doropeza@teclib.com>
 * @copyright Copyright © 2018 Teclib
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace GlpiPlugin\Flyvemdm\Mqtt;

use GlpiPlugin\Flyvemdm\Broker\BrokerReceivedMessage;
use GlpiPlugin\Flyvemdm\Interfaces\BrokerEnvelopeAwareInterface;
use GlpiPlugin\Flyvemdm\Interfaces\BrokerMiddlewareInterface;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class MqttMiddleware implements BrokerMiddlewareInterface, BrokerEnvelopeAwareInterface {

   /**
    * @param object $envelope
    * @param callable $next
    * @return mixed
    */
   public function handle($envelope, callable $next) {
      if (null === $envelope->get(MqttEnvelope::class)) {
         // is not a mqtt message
         return $next($envelope);
      }
      if ($envelope->get(BrokerReceivedMessage::class)) {
         // It's a received message. Do not send it back:
         return $next($envelope);
      }

      $sender = new MqttSender(MqttConnection::getInstance());
      if ($sender) {
         $sender->send($envelope);
      }
      return $next($envelope);
   }
}