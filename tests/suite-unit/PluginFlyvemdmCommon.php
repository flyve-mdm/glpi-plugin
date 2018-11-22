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
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
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
      $class = $this->testedClass->getClass();
      $this->string($class::generateUUID())
         ->matches('/\w{8}-\w{4}-4\w{3}-[8,9,A,B]\w{3}-\w{12}/i');
   }

   /**
    * @return array
    */
   protected function providerRecursiveRmdir() {
      $filename = uniqid('file_').'.ext';
      $dirname = uniqid('folder_');
      return [
         'Check file not exist' => [
            'data'     => [
               'path'   => '',
               'file'   => $filename,
               'delete' => $filename,
               'create' => false,
            ],
            'expected' => true,
         ],
         'Delete directory'     => [
            'data'     => [
               'path'   => $dirname . '/' . $dirname . '/',
               'file'   => $filename,
               'delete' => '../', // top dir name
               'create' => true,
            ],
            'expected' => true,
         ],
      ];
   }

   /**
    * @dataProvider providerRecursiveRmdir
    * @tags testRecursiveRmdir
    * @param array $data
    * @param mixed $expected
    */
   public function testRecursiveRmdir($data, $expected) {
      $class = $this->testedClass->getClass();
      $fullPath = GLPI_TMP_DIR . '/' . $data['path'];
      if ($data['create']) {
         mkdir($fullPath, 0777, true);
         file_put_contents($fullPath . $data['file'], 'lorem ipsum');
      }
      $resource = realpath($fullPath .$data['delete']);
      $this->boolean($class::recursiveRmdir($resource))->isEqualTo($expected);
   }

   /**
    * @tags testIsAPI
    */
   public function testIsAPI() {
      $class = $this->newTestedInstance();
      $this->boolean($class->isAPI())->isFalse();
   }

   /**
    * @tags testGetMax
    */
   public function testGetMax() {
      $class = $this->testedClass->getClass();
      $item = $this->newMockInstance(\CommonDBTM::class);
      $item->getMockController()->find = [];
      $this->variable($class::getMax($item, 'condition', 'fieldname'))->isNull();
      $item->getMockController()->find = [['fieldname' => 2]];
      $this->integer($class::getMax($item, 'condition', 'fieldname'))->isEqualTo(2);
   }

   /**
    * Used as provider for start/ends with methods
    * @return array
    */
   protected function providerStringSearch() {
      return [
         'Empty needle'  => [
            'data'     => ['needle' => '', 'haystack' => 'test_string'],
            'expected' => [true, true],
         ],
         'Test needle 1' => [
            'data'     => ['needle' => 'string', 'haystack' => 'test_string'],
            'expected' => [false, true],
         ],
         'Test needle 2' => [
            'data'     => ['needle' => 'test', 'haystack' => 'test_string'],
            'expected' => [true, false],
         ],
      ];
   }

   /**
    * @tags testStartsWith
    * @dataProvider providerStringSearch
    * @param array $data
    * @param mixed $expected
    */
   public function testStartsWith($data, $expected) {
      $class = $this->testedClass->getClass();
      $this->boolean($class::startsWith($data['haystack'],
         $data['needle']))->isEqualTo($expected[0]);
   }

   /**
    * @tags testEndsWith
    * @dataProvider providerStringSearch
    * @param array $data
    * @param mixed $expected
    */
   public function testEndsWith($data, $expected) {
      $class = $this->testedClass->getClass();
      $this->boolean($class::endsWith($data['haystack'],
         $data['needle']))->isEqualTo($expected[1]);
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
      $class::recursiveRmdir(FLYVEMDM_INVENTORY_PATH);
      $this->string($inventoryExists)->isEqualTo($fileContent);
   }

   /**
    * @tags testIsAgent
    */
   public function testIsAgent() {
      $class = $this->testedClass->getClass();

      // Simulate a profile different of agent
      $config = \Config::getConfigurationValues('flyvemdm', ['agent_profiles_id']);
      $_SESSION['glpiactiveprofile']['id'] = $config['agent_profiles_id'] + 1;
      $this->boolean($class::isAgent())->isFalse();

      // Simulate a profile equal to agent
      $_SESSION['glpiactiveprofile']['id'] = $config['agent_profiles_id'];
      $this->boolean($class::isAgent())->isTrue();
   }

   /**
    * @tags testIsCurrentUser
    */
   public function testIsCurrentUser() {
      $_SESSION["glpiID"] = 1;
      $class = $this->testedClass->getClass();
      $this->boolean($class::isCurrentUser(new \PluginFlyvemdmAgent()))->isFalse();
      $agent = $this->newMockInstance(\PluginFlyvemdmAgent::class);
      $agent->getMockController()->isNewItem = false;
      $agent->getMockController()->getField = $_SESSION["glpiID"];
      $this->boolean($class::isCurrentUser($agent))->isTrue();
   }
}
