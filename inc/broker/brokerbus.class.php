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
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace GlpiPlugin\Flyvemdm\Broker;

use GlpiPlugin\Flyvemdm\Interfaces\BrokerEnvelopeAwareInterface;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class BrokerBus {

   private $middlewareHandlers;
   /**
    * @var GlpiPlugin\Flyvemdm\Interfaces\BrokerMiddlewareInterface[]|null
    */
   private $indexedMiddlewareHandlers;

   /**
    * @param GlpiPlugin\Flyvemdm\Interfaces\BrokerMiddlewareInterface[]|iterable $middlewareHandlers
    */
   public function __construct($middlewareHandlers = []) {
      $this->middlewareHandlers = $middlewareHandlers;
   }

   /**
    * Dispatches the given message.
    *
    * The bus can return a value coming from handlers, but is not required to do so.
    *
    * @param object|BrokerEnvelope $message The message or the message pre-wrapped in an envelope
    *
    * @return mixed
    */
   public function dispatch($message) {
      if (!\is_object($message)) {
         throw new \InvalidArgumentException(sprintf(
            __('Invalid type for message argument. Expected object, but got "%s".', 'flyvemdm'),
            \gettype($message)));
      }
      return \call_user_func($this->callableForNextMiddleware(0,
         BrokerEnvelope::wrap($message)), $message);
   }

   private function callableForNextMiddleware(
      $index,
      BrokerEnvelope $currentEnvelope
   ) {
      if (null === $this->indexedMiddlewareHandlers) {
         $this->indexedMiddlewareHandlers = \is_array($this->middlewareHandlers) ? array_values($this->middlewareHandlers) : iterator_to_array($this->middlewareHandlers,
            false);
      }
      if (!isset($this->indexedMiddlewareHandlers[$index])) {
         return function () {
         };
      }
      $middleware = $this->indexedMiddlewareHandlers[$index];
      return function ($message) use ($middleware, $index, $currentEnvelope) {
         if ($message instanceof BrokerEnvelope) {
            $currentEnvelope = $message;
         } else {
            $message = $currentEnvelope->withMessage($message);
         }
         if (!$middleware instanceof BrokerEnvelopeAwareInterface) {
            // Do not provide the envelope if the middleware cannot read it:
            $message = $message->getMessage();
         }
         return $middleware->handle($message,
            $this->callableForNextMiddleware($index + 1, $currentEnvelope));
      };
   }
}