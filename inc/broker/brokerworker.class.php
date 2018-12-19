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


use GlpiPlugin\Flyvemdm\Interfaces\BrokerReceiverInterface;

class BrokerWorker {
   private $receiver;
   private $bus;

   public function __construct(BrokerReceiverInterface $receiver, BrokerBus $bus) {
      $this->receiver = $receiver;
      $this->bus = $bus;
   }

   /**
    * Receive the messages and dispatch them to the bus.
    */
   public function run() {
      if (\function_exists('pcntl_signal')) {
         pcntl_signal(SIGTERM, function () {
            $this->receiver->stop();
         });
      }

      $this->receiver->receive(function ($envelope) {
         if (null === $envelope) {
            return;
         }
         $this->bus->dispatch($envelope->with(new BrokerReceivedMessage()));
      });
   }
}