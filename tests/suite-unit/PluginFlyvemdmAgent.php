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

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmAgent extends CommonTestCase {

   /**
    * @param $method
    */
   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testShowForm':
         case 'testShowForFleet':
         case 'testShowDangerZone':
         case 'testPrepareInputForAdd':
         case 'testGetAgents':
         case 'testAddDefaultJoin':
         case 'testAddDefaultWhere':
         case 'testGetTopic':
         case 'testGetFleet':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    * @param $method
    */
   public function afterTestMethod($method) {
      switch ($method) {
         case 'testShowForm':
         case 'testShowForFleet':
         case 'testShowDangerZone':
         case 'testPrepareInputForAdd':
         case 'testGetAgents':
         case 'testAddDefaultJoin':
         case 'testAddDefaultWhere':
         case 'testGetTopic':
         case 'testGetFleet':
            parent::afterTestMethod($method);
            $this->terminateSession();
            break;
      }
   }

   /**
    * @tags testClass
    */
   public function testClass() {
      $this->testedClass->hasConstant('ENROLL_DENY');
      $this->testedClass->hasConstant('ENROLL_INVITATION_TOKEN');
      $this->testedClass->hasConstant('ENROLL_ENTITY_TOKEN');
      $this->testedClass->hasConstant('DEFAULT_TOKEN_LIFETIME');
      $this->given($this->testedClass)
         ->integer(\PluginFlyvemdmAgent::ENROLL_DENY)->isEqualTo(0)
         ->integer(\PluginFlyvemdmAgent::ENROLL_INVITATION_TOKEN)->isEqualTo(1)
         ->integer(\PluginFlyvemdmAgent::ENROLL_ENTITY_TOKEN)->isEqualTo(2)
         ->string(\PluginFlyvemdmAgent::DEFAULT_TOKEN_LIFETIME)->isEqualTo('P7D');
      $class = $this->testedClass->getClass();
      $this->given($class)->string($class::$rightname)->isEqualTo('flyvemdm:agent');
   }

   /**
    * @tags testGetEnumMdmType
    */
   public function testGetEnumMdmType() {
      $instance = $this->newTestedInstance();
      $result = $instance->getEnumMdmType();
      $this->array($result)->hasKeys(['android', 'apple'])
         ->string($result['android'])->isEqualTo('Android')
         ->string($result['apple'])->isEqualTo('Apple');
   }

   /**
    * @tags testGetTypeName
    */
   public function testGetTypeName() {
      $instance = $this->newTestedInstance();
      $this->string($instance->getTypeName(1))->isEqualTo('Agent')
         ->string($instance->getTypeName(3))->isEqualTo('Agents');
   }

   /**
    * @tags testGetMenuPicture
    */
   public function testGetMenuPicture() {
      $instance = $this->newTestedInstance();
      $this->string($instance->getMenuPicture())->isEqualTo('fa-tablet');
   }

   /**
    * @tags testGetRights
    */
   public function testGetRights() {
      $instance = $this->newTestedInstance();
      $this->array($result = $instance->getRights())->containsValues([
         'Create',
         'Read',
         'Update',
         ['short' => 'Purge', 'long' => 'Delete permanently'],
      ]);
   }

   /**
    * @tags testDefineTabs
    */
   public function testDefineTabs() {
      $instance = $this->newTestedInstance();
      $result = $instance->defineTabs();
      $this->array($result)->values
         ->string[0]->isEqualTo('Agent')
         ->string[1]->isEqualTo('Historical');
   }

   /**
    * @tags testShowForm
    * @engine inline
    */
   public function testShowForm() {
      $agent = $this->createAgent([]);
      $instance = $this->newTestedInstance();
      ob_start();
      $instance->showForm($agent->getID());
      $result = ob_get_contents();
      ob_end_clean();
      $formAction = preg_quote("/plugins/flyvemdm/front/agent.form.php", '/');
      $this->string($result)
         ->matches("#method='post' action='.+?" . $formAction . "'#")
         ->contains("input type='hidden' name='entities_id' value='0'")
         ->contains("type='text' name='name'")
         ->matches('#(?:input type="hidden"|select) .*?name="computers_id"#')
         ->matches('#(?:input type="hidden"|select) .*?name="plugin_flyvemdm_fleets_id"#')
         ->contains('input type="hidden" name="_glpi_csrf_token"');
   }

   /**
    * @tags testShowDangerZone
    * @engine inline
    */
   public function testShowDangerZone() {
      $agent = $this->createAgent([]);
      $instance = $this->newTestedInstance();
      ob_start();
      $instance->showDangerZone($agent);
      $result = ob_get_contents();
      ob_end_clean();
      $formAction = preg_quote("/plugins/flyvemdm/front/agent.form.php", '/');
      $this->string($result)
         ->matches("#method='post' action='.+?" . $formAction . "'#")
         ->contains("input type='hidden' name='entities_id'")
         ->matches("#input type='checkbox' .+? name='lock'#")
         ->matches("#input type='checkbox' .+? name='wipe'#")
         ->contains('input type="submit" value="Unenroll" name="unenroll"')
         ->contains('input type="hidden" name="_glpi_csrf_token"');
   }

   /**
    * @tags testShowForFleet
    */
   public function testShowForFleet() {
      ob_start();
      \PluginFlyvemdmAgent::showForFleet(new \PluginFlyvemdmFleet());
      $result = ob_get_contents();
      ob_end_clean();
      $this->string($result)->contains('There is no agent yet');
   }

   /**
    * @tags testCanViewItem
    */
   public function testCanViewItem() {
      // Simulate a profile different of guest
      $config = \Config::getConfigurationValues('flyvemdm', ['guest_profiles_id']);
      $_SESSION['glpiactiveprofile']['id'] = $config['guest_profiles_id'] + 1;

      $testedInstance = $this->newTestedInstance;
      $this->boolean($testedInstance->canViewItem())->isFalse();

      // Simulate a profile equal to guest
      $_SESSION['glpiactiveprofile']['id'] = $config['guest_profiles_id'];
      $testedInstance = $this->newTestedInstance;
      $this->boolean($testedInstance->canViewItem())->isFalse();
   }

   /**
    * @tags testCanUpdateItem
    */
   public function testCanUpdateItem() {
      // Simulate a profile different of agent
      $oldProfile = (isset($_SESSION['glpiactiveprofile']['id'])) ? $_SESSION['glpiactiveprofile']['id'] : null;
      $config = \Config::getConfigurationValues('flyvemdm', ['agent_profiles_id']);
      $_SESSION['glpiactiveprofile']['id'] = $config['agent_profiles_id'] + 1;

      $testedInstance = $this->newTestedInstance;
      $this->boolean($testedInstance->canUpdateItem())->isFalse();

      // Simulate a profile equal to agent
      $_SESSION['glpiactiveprofile']['id'] = $config['agent_profiles_id'];
      $testedInstance = $this->newTestedInstance;
      $this->boolean($testedInstance->canUpdateItem())->isFalse();
      $_SESSION['glpiactiveprofile']['id'] = $oldProfile;
   }

   /**
    * @tags testCanDeleteItem
    */
   public function testCanDeleteItem() {
      // Simulate a profile different of agent
      $config = \Config::getConfigurationValues('flyvemdm', ['agent_profiles_id']);
      $_SESSION['glpiactiveprofile']['id'] = $config['agent_profiles_id'] + 1;

      $testedInstance = $this->newTestedInstance;
      $this->boolean($testedInstance->canDeleteItem())->isFalse();

      // Simulate a profile equal to agent
      $_SESSION['glpiactiveprofile']['id'] = $config['agent_profiles_id'];
      $testedInstance = $this->newTestedInstance;
      $this->boolean($testedInstance->canDeleteItem())->isFalse();
   }

   /**
    * @tags testGetTopicsToCleanup
    */
   public function testGetTopicsToCleanup() {
      $expected = array_merge(CommonTestCase::commandList(), CommonTestCase::policyList());
      $topics = \PluginFlyvemdmAgent::getTopicsToCleanup();
      $this->array($topics)->size->isEqualTo(count($expected));
      $this->array($topics)->containsValues($expected,
         "Not found policies" . PHP_EOL . json_encode(array_diff($topics, $expected),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
      );
   }

   /**
    * @tags testGetAgents
    * @engine inline
    */
   public function testGetAgents() {
      $instance = $this->createAgent([]);
      $agents = [$instance->getID() => $instance];
      $output = $instance->getAgents();
      $this->array($output)->size->isEqualTo(count($agents));
      foreach ($output as $agent) {
         $this->object($agent)->isInstanceOf(\PluginFlyvemdmAgent::class);
         $this->boolean(isset($agents[$agent->getID()]))->isTrue();
      }
   }

   /**
    * @tags testAddDefaultJoin
    */
   public function testAddDefaultJoin() {
      $ref_table = \PluginFlyvemdmAgent::getTable();
      $result = \PluginFlyvemdmAgent::addDefaultJoin($ref_table, []);
      $this->string($result)->isEmpty();

      $config = \Config::getConfigurationValues('flyvemdm', ['guest_profiles_id']);
      $guestProfileId = $config['guest_profiles_id'];
      $_SESSION['glpiactiveprofile']['id'] = $guestProfileId;

      $result = \PluginFlyvemdmAgent::addDefaultJoin($ref_table, []);
      $computerTable = \Computer::getTable();
      $join = "LEFT JOIN `$computerTable` AS `c` ON `$ref_table`.`computers_id`=`c`.`id` ";
      $this->string($result)->isEqualTo($join);
   }

   /**
    * @tags testAddDefaultWhere
    */
   public function testAddDefaultWhere() {
      $instance = $this->newTestedInstance();
      $result = $instance::addDefaultWhere();
      $this->string($result)->isEmpty();
   }

   /**
    * @tags testGetTopic
    * @engine inline
    */
   public function testGetTopic() {
      $instance = $this->newTestedInstance();
      $this->variable($instance->getTopic())->isNull();

      $instance = $this->createAgent([]);
      $serial = $instance->getComputer()->getField('serial');
      $entityId = $instance->getField('entities_id');
      $this->string($instance->getTopic())->isEqualTo($entityId . '/agent/' . $serial);
   }

   /**
    * @tags testGetPackages
    */
   public function testGetPackages() {
      $instance = $this->newTestedInstance();
      $this->array($instance->getPackages())->isEmpty();
   }

   /**
    * @tags testGetFiles
    */
   public function testGetFiles() {
      $instance = $this->newTestedInstance();
      $this->array($instance->getFiles())->isEmpty();
   }

   /**
    * @tags testGetFleet
    * @engine inline
    */
   public function testGetFleet() {
      $instance = $this->newTestedInstance();
      $this->variable($instance->getFleet())->isNull();

      $instance = $this->createAgent([]);
      $this->object($instance->getFleet())->isInstanceOf('PluginFlyvemdmFleet');
   }

   /**
    * @tags testGetComputer
    */
   public function testGetComputer() {
      $instance = $this->newTestedInstance();
      $this->variable($instance->getComputer())->isNull();
   }

   /**
    * @tags testRefreshPersistedNotifications
    */
   public function testRefreshPersistedNotifications() {
      $instance = $this->newTestedInstance();
      $this->variable($instance->refreshPersistedNotifications())->isNull();
      // TODO: complete this test
   }

   /**
    * @tags testIsNotifiable
    */
   public function testIsNotifiable() {
      $instance = $this->newTestedInstance();
      $this->boolean($instance->isNotifiable())->isTrue();
   }

   /**
    * @tags testGetSpecificValueToDisplay
    */
   public function testGetSpecificValueToDisplay() {
      $instance = $this->newTestedInstance();
      $this->string($instance->getSpecificValueToDisplay('', ''))->isEqualTo('');
      $this->string($instance->getSpecificValueToDisplay('is_online',
         0))->contains('plugin-flyvemdm-offline');
      $this->string($instance->getSpecificValueToDisplay('is_online',
         1))->contains('plugin-flyvemdm-online');
      $this->string($instance->getSpecificValueToDisplay('mdm_type',
         'android'))->contains('Android');
   }

   /**
    * @tags testUpdateLastContact
    */
   public function testUpdateLastContact() {
      $instance = $this->newMockInstance(\PluginFlyvemdmAgent::class);
      $instance->fields['is_online'] = 0;
      $instance->getMockController()->update = true;
      $instance->updateLastContact('', '');
      $this->mock($instance)->call('update')->never();
   }

   /**
    * @tags testUnsubscribe
    */
   public function testUnsubscribe() {
      $instance = $instance = $this->newMockInstance(\PluginFlyvemdmAgent::class);
      $instance->getMockController()->update = true;
      $topic = '0/agent/lorem';
      $instance->getMockController()->getTopic = $topic;
      $instance->getMockController()->notify = null;
      $instance->unsubscribe();
      $this->mock($instance)->call('notify')
         ->withAtLeastArguments([$topic.'/Subscription'])->once();
   }

   /**
    * @tags testGetByTopic
    */
   public function testGetByTopic() {
      $instance = $this->newMockInstance(\PluginFlyvemdmAgent::class);
      if (method_exists($instance, 'getFromDBByRequest')) {
         $instance->getMockController()->getFromDbByRequest = true;
      } else {
         $instance->getMockController()->getFromDBByQuery = true;
      }
      $this->boolean($instance->getByTopic(''))->isFalse();
      $this->boolean($instance->getByTopic('0/lorem/'))->isFalse();
      $this->boolean($instance->getByTopic('0/agent/'))->isFalse();
      $this->boolean($instance->getByTopic('0/agent/serial/'))->isTrue();
   }

   public function providerUpdateInput() {
      $agentXmlInventory = CommonTestCase::AgentXmlInventory(uniqid('sn'));
      return [
         'invalid fleet change' => [
            'input'    => ['plugin_flyvemdm_fleets_id' => 'lorem'],
            'expected' => [
               'result'  => false,
               'message' => 'The fleet of the device does not longer exists',
            ],
         ],
         'change to a non-existing fleet' => [
            'input'    => ['plugin_flyvemdm_fleets_id' => 'lorem'],
            'expected' => ['result' => false, 'message' => 'The target fleet does not exists'],
            'extra'    => ['mockFleet' => 1],
         ],
         'ping not enrolled device' => [
            'input'    => ['_ping_request' => 'lorem'],
            'expected' => ['result' => false, 'message' => 'The device is not enrolled yet'],
         ],
         'geolocate not enrolled device' => [
            'input'    => ['_geolocate_request' => 'lorem'],
            'expected' => ['result' => false, 'message' => 'The device is not enrolled yet'],
         ],
         'inventory not enrolled device' => [
            'input'    => ['_inventory_request' => 'lorem'],
            'expected' => ['result' => false, 'message' => 'The device is not enrolled yet'],
         ],
         'lock' => [
            'input'    => ['lock' => 'lorem'],
            'expected' => ['result' => ['lock' => '1']],
         ],
         'wipe' => [
            'input'    => ['wipe' => 'lorem'],
            'expected' => ['result' => ['wipe' => '1']],
         ],
         'lock and wipe' => [
            'input'    => ['lock' => 'lorem', 'wipe' => 'lorem'],
            'expected' => ['result' => ['wipe' => '1']],
         ],
         'unenroll' => [
            'input' => ['_unenroll_request' => 'lorem'],
            'expected' => [
               'result' => [
                  '_unenroll_request' => 'lorem',
                  'enroll_status'     => 'unenrolling',
               ],
            ],
         ],
         'agent response for inventory' => [
            'input'    => ['_inventory' => $agentXmlInventory],
            'expected' => [
               'result' => [
                  '_inventory'   => $agentXmlInventory,
                  'last_contact' => $_SESSION['glpi_currenttime'],
               ],
            ],
            'extra'    => ['isAgent' => true],
         ],
         'agent response for online status' => [
            'input'    => ['is_online' => 1],
            'expected' => [
               'result' => [
                  'is_online'    => '1',
                  'last_contact' => $_SESSION['glpi_currenttime'],
               ],
            ],
            'extra'    => ['isAgent' => true],
         ],
         'agent response for offline status' => [
            'input'    => ['is_online' => 0],
            'expected' => [
               'result' => [
                  'is_online'    => '0',
                  'last_contact' => $_SESSION['glpi_currenttime'],
               ],
            ],
            'extra'    => ['isAgent' => true],
         ],
         'agent response for invalid status' => [
            'input'    => ['is_online' => 'lorem'],
            'expected' => ['result' => false, 'message' => 'Invalid status value'],
            'extra'    => ['isAgent' => true],
         ],
      ];
   }

   /**
    * @dataProvider providerUpdateInput
    * @tags testGetprepareInputForUpdate
    *
    * @param array $input
    * @param array $expected
    * @param array $extraArguments
    */
   public function testGetprepareInputForUpdate(array $input, array $expected, array $extraArguments = []) {
      $instance = $this->newMockInstance(\PluginFlyvemdmAgent::class);
      $instance->fields['plugin_flyvemdm_fleets_id'] = isset($extraArguments['mockFleet']) ? $extraArguments['mockFleet'] : null;
      $instance->fields['wipe'] = isset($extraArguments['mockWipe']) ? $extraArguments['mockWipe'] : 0;
      if (isset($extraArguments['isAgent']) && $extraArguments['isAgent']) {
         $config = \Config::getConfigurationValues('flyvemdm', ['agent_profiles_id']);
         $_SESSION['glpiactiveprofile']['id'] = $config['agent_profiles_id'];
      } else {
         $_SESSION['glpiactiveprofile']['id'] = 1;
      }
      $instance->getMockController()->update = true;
      $instance->getMockController()->getTopic = isset($extraArguments['mockTopic']) ? $extraArguments['mockTopic'] : null;
      $instance->getMockController()->notify = null;
      $result = $instance->prepareInputForUpdate($input);
      if ($expected['result'] === false) {
         $this->assertInvalidResult($result, $expected['message']);
      } else {
         $this->variable($result)->isEqualTo($expected['result']);
      }
   }

}
