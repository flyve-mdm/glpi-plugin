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

class ProfileRight extends CommonTestCase {

   /**
    * @tags testAgentProfileRights
    */
   public function testAgentProfileRights() {
      $config = \Config::getConfigurationValues('flyvemdm', ['agent_profiles_id']);
      $this->array($config)->hasKey('agent_profiles_id');
      $this->integer((int) $config['agent_profiles_id'])->isGreaterThan(0);

      $profileId = $config['agent_profiles_id'];

      // Expected rights
      $rightsSet = [
         \PluginFlyvemdmAgent::$rightname        => READ,
         \PluginFlyvemdmPackage::$rightname      => READ,
         \PluginFlyvemdmFile::$rightname         => READ,
         \PluginFlyvemdmEntityConfig::$rightname => READ,
      ];

      $profileRight = $this->newTestedInstance();
      $rights = $profileRight::getProfileRights(
         $profileId,
         []
      );

      // Check rights
      foreach ($rightsSet as $key => $value) {
         $this->integer((int) $rights[$key])->isEqualTo($value);
         unset($rights[$key]);
      }

      // Check all other righs are set to 0
      foreach ($rights as $key => $value) {
         $this->integer((int) $rights[$key])->isEqualTo(0, "Right $key is not equal to $value");
      }
   }

   /**
    * @tags testSuperAdminProfileRights
    */
   public function testSuperAdminProfileRights() {
      global $DB;

      $profileId = 4;      // Super admin profile ID

      // Expected rights
      $rightsSet = [
         \PluginFlyvemdmAgent::$rightname          => READ | UPDATE | PURGE | READNOTE | UPDATENOTE,
         \PluginFlyvemdmFleet::$rightname          => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         \PluginFlyvemdmPackage::$rightname        => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         \PluginFlyvemdmFile::$rightname           => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         \PluginFlyvemdmGeolocation::$rightname    => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
         \PluginFlyvemdmPolicy::$rightname         => READ,
         \PluginFlyvemdmPolicyCategory::$rightname => READ,
         \PluginFlyvemdmWellknownpath::$rightname  => ALLSTANDARDRIGHT,
         \PluginFlyvemdmProfile::$rightname        => \PluginFlyvemdmProfile::RIGHT_FLYVEMDM_USE,
         \PluginFlyvemdmEntityConfig::$rightname   => READ
            | \PluginFlyvemdmEntityConfig::RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT
            | \PluginFlyvemdmEntityConfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL
            | \PluginFlyvemdmEntityConfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE,
         \PluginFlyvemdmInvitation::$rightname     => ALLSTANDARDRIGHT,
         \PluginFlyvemdmInvitationLog::$rightname  => READ,
         \PluginFlyvemdmTaskstatus::$rightname     => READ,
      ];

      $profileRight = $this->newTestedInstance();
      $rights = $profileRight::getProfileRights(
         $profileId,
         array_keys($rightsSet)
      );

      // Check rights
      foreach ($rightsSet as $key => $value) {
         $this->integer((int) $rights[$key])->isEqualTo($value);
      }

      $request = [
         'FROM' => $profileRight::getTable(),
         'WHERE' => ['AND' =>
            ['name' => ['LIKE', 'flyvemdm:%']],
            ['profiles_id' => $profileId],
         ]
      ];
      $result = $DB->request($request);
      $this->integer($result->count())->isEqualTo(count($rightsSet));
   }

   /**
    * @tags testGuestProfileRights
    */
   public function testGuestProfileRights() {
      $config = \Config::getConfigurationValues('flyvemdm', ['guest_profiles_id']);
      $this->array($config)->hasKey('guest_profiles_id');
      $this->integer((int) $config['guest_profiles_id'])->isGreaterThan(0);

      $profileId = $config['guest_profiles_id'];
      // Expected rights
      $rightsSet = [
         \PluginFlyvemdmAgent::$rightname   => READ | CREATE,
         \PluginFlyvemdmPackage::$rightname => READ,
         \PluginFlyvemdmFile::$rightname    => READ,
      ];

      $profileRight = $this->newTestedInstance();
      $rights = $profileRight::getProfileRights(
         $profileId,
         []
      );

      // Check rights
      foreach ($rightsSet as $key => $value) {
         $this->integer((int) $rights[$key])->isEqualTo($value);
         unset($rights[$key]);
      }

      // Check all other righs are set to 0
      foreach ($rights as $key => $value) {
         $this->integer((int) $rights[$key])->isEqualTo(0, "Right $key is not equal to $value");
      }
   }
}
