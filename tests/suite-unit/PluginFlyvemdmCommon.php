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
 * @author    Domingo Oropeza
 * @copyright Copyright © 2018 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use atoum;
use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmCommon extends atoum {

   /**
    * @tags testGetMassiveActions
    */
   public function testGetMassiveActions() {
      $class = $this->testedClass->getClass();
      $result = $class::getMassiveActions([]);
      $this->given($class)->string($result)->contains("autoOpen: false")
         ->contains("modal: true")->contains('title: "Actions"')
         ->contains("ajax/massiveaction.php");
   }

   /**
    * @tags testGetEnumValues
    */
   public function testGetEnumValues() {
      $class = $this->testedClass->getClass();
      $table = \PluginFlyvemdmAgent::getTable();
      $result = $class::getEnumValues($table, 'id');
      $this->given($class)->array($result)->isEmpty();
      $result = $class::getEnumValues($table, 'mdm_type');
      $this->given($class)->array($result)->isNotEmpty()->containsValues(['android', 'apple']);
   }

   /**
    * @tags testGenerateUUID
    */
   public function testGenerateUUID() {
      $this->given($this->newTestedInstance)
         ->string($this->testedInstance->generateUUID())
         ->matches('/\w{8}-\w{4}-4\w{3}-[8,9,A,B]\w{3}-\w{12}/i');
   }

   /**
    * @tags testParseXML
    */
   public function testParseXML() {
      $class = $this->testedClass->getClass();

      // invalid XML
      $this->boolean($class::parseXML('loremIpsum'))->isFalse();

      $xml = base64_decode(CommonTestCase::AgentXmlInventory(uniqid('sn')));

      // using non UTF8 charset
      $this->object($class::parseXML(iconv("UTF-8", "ISO-8859-1",
         $xml)))->isInstanceOf('\SimpleXMLElement');
      $this->object($class::parseXML($xml))->isInstanceOf('\SimpleXMLElement');

      // valid XML
      $this->object($class::parseXML($xml))->isInstanceOf('\SimpleXMLElement');
   }

   /**
    * @tags testSaveInventoryFile
    */
   public function testSaveInventoryFile() {
      $class = $this->testedClass->getClass();
      $filename = uniqid('invitation');
      $fileContent = 'loremIpsum';
      $class::saveInventoryFile($fileContent, $filename);
      $inventoryExists = file_get_contents(FLYVEMDM_INVENTORY_PATH . "/debug_" . $filename . ".xml");
      $this->string($inventoryExists)->isEqualTo($fileContent);
   }
}
