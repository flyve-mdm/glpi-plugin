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

namespace tests\units\GlpiPlugin\Flyvemdm\Fcm;


use Flyvemdm\Tests\CommonTestCase;

class FcmEnvelope extends CommonTestCase {

   private $scope = [['type' => 'fcm', 'token' => 'Sup3rT0k3n']];
   private $topic = ['lorem/ipsum/dolor'];

   protected function providerContext() {
      return [
         'empty' => [
            'context' => [[]],
            'expected' => 'The scope argument is needed (push type and token)',
         ],
         'invalid scope' => [
            'context' => ['lorem' => ['key' => '']],
            'expected' => 'The scope argument is needed (push type and token)',
         ],
         'invalid type' => [
            'context' => ['scope' => [['key' => '']]],
            'expected' => 'The scope argument is needed (push type and token)',
         ],
         'invalid token' => [
            'context' => ['scope' => [['type' => 'fcm']]],
            'expected' => 'The scope argument is needed (push type and token)',
         ],
         'invalid topic' => [
            'context' => ['scope' => $this->scope],
            'expected' => 'A topic argument is needed',
         ],
      ];
   }
   /**
    * @tags testException
    * @dataProvider providerContext
    * @param array $context
    * @param string $expected
    */
   public function testException($context, $expected) {
      $this->exception(function () use ($context) {
         $this->newTestedInstance($context);
      })->hasMessage($expected);
   }

   /**
    * @tags testEnvelope
    */
   public function testEnvelope() {
      $instance = $this->newTestedInstance(['scope' => $this->scope, 'topic' => $this->topic]);
      $this->array($instance->getContext('scope'))->child[0](function ($child) {
         $scope = $this->scope[0];
         $child->hasKeys(['type', 'token'])->values
            ->string[0]->isEqualTo($scope['type'])
            ->string[1]->isEqualTo($scope['token']);
      });
      $context = $instance->getContext('topic');
      $this->string($context[0])->isEqualTo('lorem-ipsum-dolor');
   }

}