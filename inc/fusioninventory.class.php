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

 use GlpiPlugin\Flyvemdm\Exception\FusionInventoryRuleInconsistency;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmFusionInventory {

   const RULE_NAME = 'Flyve MDM invitation to entity';

   /**
    * Creates or updates an entity rule
    *
    * @param PluginFlyvemdmInvitation $invitation invitation to handle
    */
   public function addInvitationRule(PluginFlyvemdmInvitation $invitation) {
      $entityId = $invitation->getField(Entity::getForeignKeyField());
      try {
         $rule = $this->getRule($entityId);
      } catch (FusionInventoryRuleInconsistency $exception) {
         Session::addMessageAfterRedirect(__('Unable to get rule for entity', 'flyvemdm'),
            true, ERROR);
         return;
      }

      $ruleCriteria = new RuleCriteria();
      $ruleCriteria->add([
         Rule::getForeignKeyField() => $rule->getID(),
         'criteria'                 => 'tag',
         'condition'                => '0',
         'pattern'                  => $this->getRuleCriteriaValue($invitation),
      ]);

      // Activate the rule
      $rule->update([
         'id'        => $rule->getID(),
         'is_active' => '1',
      ]);
   }

   /**
    * Deletes a rule criteria for an entity assignment rule
    *
    * @param PluginFlyvemdmInvitation $invitation invitation to handle
    */
   public function deleteInvitationRuleCriteria(PluginFlyvemdmInvitation $invitation) {
      global $DB;

      $entityId = $invitation->getField(Entity::getForeignKeyField());
      $ruleFk = PluginFusioninventoryInventoryRuleEntity::getForeignKeyField();
      $request = [
         'SELECT' => RuleCriteria::getTable() . '.*',
         'FROM' => RuleCriteria::getTable(),
         'INNER JOIN' => [
            PluginFusioninventoryInventoryRuleEntity::getTable() => [
               'FKEY' => [
                  PluginFusioninventoryInventoryRuleEntity::getTable() => 'id',
                  RuleCriteria::getTable() => $ruleFk,
               ]
            ],
            RuleAction::getTable() => [
               'FKEY' => [
                  PluginFusioninventoryInventoryRuleEntity::getTable() => 'id',
                  RuleAction::getTable() => $ruleFk,
               ]
            ]
         ],
         'WHERE' => [
            'AND' => [
               'pattern'   => $this->getRuleCriteriaValue($invitation),
               'criteria'  => 'tag',
            ]
         ]
      ];
      $result = $DB->request($request);
      if ($result->count() !== 1) {
         return;
      }

      $row = $result->next();
      $ruleCriteria = new RuleCriteria();
      $ruleCriteria->delete([
         'id' => $row['id']
      ]);

      $ruleId = $row[$ruleFk];
      $rows = $ruleCriteria->find("`$ruleFk` = '$ruleId' AND `criteria` = 'tag' AND `condition` = '0'");
      if (count($rows) === 0) {
         $rule = new PluginFusioninventoryInventoryRuleEntity();
         $rule->update([
            'id' => $row[$ruleFk],
            'is_active' => '0',
         ]);
      }
   }

   /**
    * gets a entity identification tag derivated from an invitation
    * @param PluginFlyvemdmInvitation $invitation
    * @return string
    */
   private function getRuleCriteriaValue(PluginFlyvemdmInvitation $invitation) {
      return 'invitation_' . $invitation->getField('invitation_token');
   }

   /**
    * Finds a rule
    *
    * @param integer $entityId ID of the entity assigned by the rule
    * @param boolean $create If the rule does not exists, create it
    *
    * @return PluginFusioninventoryInventoryRuleEntity|null
    * @throws FusionInventoryRuleInconsistency
    */
   private function getRule($entityId, $create = true) {
      global $DB;

      $request = [
         'SELECT' => PluginFusioninventoryInventoryRuleEntity::getTable() . '.*',
         'FROM' => PluginFusioninventoryInventoryRuleEntity::getTable(),
         'INNER JOIN' => [
            RuleAction::getTable() => [
               'FKEY' => [
                  PluginFusioninventoryInventoryRuleEntity::getTable() => 'id',
                  RuleAction::getTable() => PluginFusioninventoryInventoryRuleEntity::getForeignKeyField()
               ],
            ]
         ],
         'WHERE'  => [
            'AND' => [
               PluginFusioninventoryInventoryRuleEntity::getTable() . '.name'
                  => self::RULE_NAME . " $entityId",
               'sub_type'     => PluginFusioninventoryInventoryRuleEntity::class,
               'action_type'  => 'assign',
               'field'        => Entity::getForeignKeyField(),
               'value'        => $entityId,
            ]
         ]
      ];

      $result = $DB->request($request);
      if ($result->count() === 1) {
         $rule = new PluginFusioninventoryInventoryRuleEntity();
         $row = $result->next();
         $rule->getFromDB($row['id']);
         return $rule;
      }
      if ($result->count() > 1) {
         throw new FusionInventoryRuleInconsistency(__('Import rule is not unique'));
      }

      if (!$create) {
         return null;
      }

      $rule = new PluginFusioninventoryInventoryRuleEntity();
      $rule->add([
         'sub_type'     => PluginFusioninventoryInventoryRuleEntity::class,
         'entities_id'  => '0',
         'is_recursive' => '0',
         'name'         => self::RULE_NAME . " $entityId",
         'description'  => 'Automatically generated by an invitation to enrol. Do not change its name',
         'is_active'    => '0',
         'condition'    => '0',
         'match'        => Rule::OR_MATCHING,
      ]);
      $ruleAction = new RuleAction();
      $ruleAction->add([
         Rule::getForeignKeyField() => $rule->getID(),
         'action_type'              => 'assign',
         'field'                    => Entity::getForeignKeyField(),
         'value'                    => $entityId,
      ]);
      return $rule;
   }
}