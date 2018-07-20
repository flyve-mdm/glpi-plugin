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

class PluginFlyvemdmFusionInventory extends CommonTestCase {

   /**
    * @param $method
    */
   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testAddInvitationRule':
         case 'testDeleteInvitationRuleCriteria':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   /**
    * @param $method
    */
   public function afterTestMethod($method) {
      switch ($method) {
         case 'testAddInvitationRule':
         case 'testDeleteInvitationRuleCriteria':
            parent::afterTestMethod($method);
            \Session::destroy();
            break;
      }
   }

   /**
    * Run without concurrency, becauase it tests an inconsistency in DB
    * @engine inline
    */
   public function testAddInvitationRule() {
      // Create an invitation
      $invitation = $this->newMockInstance(\PluginFlyvemdmInvitation::class);
      $invitation->fields['invitation_token'] = $this->getUniqueString();
      $invitation->fields[\Entity::getForeignKeyField()] = 1;

      $fi = new \PluginFlyvemdmFusionInventory();
      $fi->addInvitationRule($invitation);

      // Test a rule exists for the entity
      $this->checkRuleAndCriteria($invitation);
      $row = $this->findRuleForEntity($invitation->fields[\Entity::getForeignKeyField()]);

      $ruleAction = new \RuleAction();
      $ruleAction->getFromDbByCrit([
         'AND' => [
            \PluginFusioninventoryInventoryRuleEntity::getForeignKeyField() => $row['id'],
            'action_type'  => 'assign',
            'field'        => \Entity::getForeignKeyField(),
            'value'        => $invitation->getField(\Entity::getForeignKeyField()),
         ]
      ]);
      $this->boolean($ruleAction->isNewItem())->isFalse();

      // Test an exception is thrown in case of inconsistency
      $input = $row;
      unset($input['id']);
      $rule = new \PluginFusioninventoryInventoryRuleEntity();
      $rule->add($input);
      $input = $ruleAction->fields;
      $input[\PluginFusioninventoryInventoryRuleEntity::getForeignKeyField()] = $rule->getID();
      unset($input['id']);
      $ruleAction->add($input);
      $fi->addInvitationRule($invitation);
      $this->string($_SESSION['MESSAGE_AFTER_REDIRECT'][1][0])->isEqualTo('Unable to get rule for entity');

      // Cleanup inconsistency in DB
      $ruleAction->delete(['id' => $ruleAction->getID()]);

      // Create an invitation again
      $invitation = $this->newMockInstance(\PluginFlyvemdmInvitation::class);
      $invitation->fields['invitation_token'] = $this->getUniqueString();
      $invitation->fields[\Entity::getForeignKeyField()] = 1;

      $fi = new \PluginFlyvemdmFusionInventory();
      $fi->addInvitationRule($invitation);

      // Test the rule criteria for the invitation exists
      // and uses the same rule as the previous invitation
      $ruleCriteria = new \RuleCriteria();
      $ruleCriteria->getFromDBByCrit([
         'AND' => [
            \PluginFusioninventoryInventoryRuleEntity::getForeignKeyField() => $row['id'],
            'criteria'  => 'tag',
            'condition' => '0',
            'pattern'   => 'invitation_' . $invitation->fields['invitation_token'],
         ]
      ]);
      $this->boolean($ruleCriteria->isNewItem())->isFalse();
   }

   public function testDeleteInvitationRuleCriteria() {
      // Create an invitation
      $invitation = $this->newMockInstance(\PluginFlyvemdmInvitation::class);
      $invitation->fields['invitation_token'] = $this->getUniqueString();
      $invitation->fields[\Entity::getForeignKeyField()] = 1;

      $fi = new \PluginFlyvemdmFusionInventory();
      $fi->addInvitationRule($invitation);
      $entityId = $invitation->fields[\Entity::getForeignKeyField()];
      $row = $this->findRuleForEntity($entityId);

      //  Test the rule criteria for the invitation exists
      $ruleCriteria = new \RuleCriteria();
      $ruleCriteria->getFromDBByCrit([
         'AND' => [
            \PluginFusioninventoryInventoryRuleEntity::getForeignKeyField() => $row['id'],
            'criteria'  => 'tag',
            'condition' => '0',
            'pattern'   => 'invitation_' . $invitation->fields['invitation_token'],
         ]
      ]);
      $this->boolean($ruleCriteria->isNewItem())->isFalse();

      $fi->deleteInvitationRuleCriteria($invitation);
      $found = $ruleCriteria->getFromDB($ruleCriteria->getID());
      $this->boolean($found)->isFalse();
   }

   private function findRuleForEntity($entityId) {
      global $DB;

      $fiRuleEntity = \PluginFusioninventoryInventoryRuleEntity::getTable();
      $request = [
         'SELECT'     => $fiRuleEntity . '.*',
         'FROM'       => $fiRuleEntity,
         'INNER JOIN' => [
            \RuleAction::getTable() => [
               'FKEY' => [
                  $fiRuleEntity           => 'id',
                  \RuleAction::getTable() => \PluginFusioninventoryInventoryRuleEntity::getForeignKeyField(),
               ],
            ],
         ],
         'WHERE'      => [
            'AND' => [
               $fiRuleEntity . '.name'
                             => \PluginFlyvemdmFusionInventory::RULE_NAME . " $entityId",
               'sub_type'    => \PluginFusioninventoryInventoryRuleEntity::class,
               'action_type' => 'assign',
               'field'       => \Entity::getForeignKeyField(),
               'value'       => $entityId,
            ],
         ],
      ];

      $result = $DB->request($request);
      $this->integer($result->count())->isEqualTo(1);
      return $result->next();
   }

   /**
    * Checks a rule exists and matches an invitation
    *
    * @param \PluginFlyvemdmInvitation $invitation invitation to  check
    * @param boolean $hasCriteria true if it is expeccted the criteria exists
    */
   public function checkRuleAndCriteria(\PluginFlyvemdmInvitation $invitation, $hasCriteria = true) {
      $entityId = $invitation->fields[\Entity::getForeignKeyField()];
      $row = $this->findRuleForEntity($entityId);

      // Test the rule criteria for the invitation exists
      $ruleCriteria = new \RuleCriteria();
      $ruleCriteria->getFromDBByCrit([
         'AND' => [
            \PluginFusioninventoryInventoryRuleEntity::getForeignKeyField() => $row['id'],
            'criteria'  => 'tag',
            'condition' => '0',
            'pattern'   => 'invitation_' . $invitation->fields['invitation_token'],
         ]
      ]);
      $this->boolean($ruleCriteria->isNewItem())->isEqualTo(!$hasCriteria);
   }
}