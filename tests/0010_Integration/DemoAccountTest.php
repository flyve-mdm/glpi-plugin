<?php
/**
 LICENSE

Copyright (C) 2016 Teclib'
Copyright (C) 2010-2016 by the FusionInventory Development Team.

This file is part of Flyve MDM Plugin for GLPI.

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
You should have received a copy of the GNU Affero General Public License
along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
------------------------------------------------------------------------------
@author    Thierry Bugier Pineau
@copyright Copyright (c) 2016 Flyve MDM plugin team
@license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
@link      https://github.com/flyvemdm/backend
@link      http://www.glpi-project.org/
------------------------------------------------------------------------------
*/

class DemoAccountTest extends ApiRestTestCase
{

   protected static $registeredUser;
   protected static $registeredPass;

   public static function setupBeforeClass() {
      parent::setupBeforeClass();

      config::setConfigurationValues('storkmdm', [
            'demo_mode'    => 1,
            'webapp_url'   => 'https://localhost',
      ]);

      self::$registeredUser = 'johndoe@localhost.local';
      self::$registeredPass = 'password';
   }

   public function inactiveAccountProvider() {
      return [
            'not activated' => [
                  'name'      => 'notactivated@localhost.local',
                  'password'  => 'password',
                  'firstname' => 'not',
                  'realname'  => 'activated',
            ],
      ];
   }

   public function activeAccountProvider() {
      return [
            'active'      => [
                  'name'      => 'active@localhost.local',
                  'password'  => 'password',
                  'firstname' => 'is',
                  'realname'  => 'active',
            ],
      ];
   }

   public function expiredAccountProvider() {
      return [
            'expired'   => [
                  'name'      => 'expired@localhost.local',
                  'password'  => 'password',
                  'firstname' => 'is',
                  'realname'  => 'active',
            ],
      ];
   }

   public function toRemindAccountProvider() {
      return [
            'nearlyexpired'      => [
                  'name'      => 'nearlyexpired@localhost.local',
                  'password'  => 'password',
                  'firstname' => 'will',
                  'realname'  => 'expire',
            ],
      ];
   }

   public function allAccountsProvider() {
      return array_merge(
            $this->inactiveAccountProvider(),
            $this->activeAccountProvider(),
            $this->expiredAccountProvider(),
            $this->toRemindAccountProvider()
      );
   }

   public function activeAndExpiredAccountProvider() {
      return array_merge(
            $this->activeAccountProvider(),
            $this->expiredAccountProvider(),
            $this->toRemindAccountProvider()
      );
   }

   /**
    * @return string
    */
   public function testInitGetServiceSessionToken() {
      $user = new User();
      $user->getFromDBbyName(PluginStorkmdmConfig::SERVICE_ACCOUNT_NAME);
      $this->assertFalse($user->isNewItem());
      $userToken = $user->getField('personal_token');

      $headers = ['authorization' => "user_token $userToken"];
      $this->emulateRestRequest('get', 'initSession', $headers);

      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      return $this->restResponse['session_token'];
   }

   protected function getAccountValidation($userId) {
      $accountValidation = new PluginStorkmdmAccountvalidation();
      if (!$accountValidation->getFromDBByQuery("WHERE `users_id` = '$userId'")) {
         $accountValidation = null;
      }

      return $accountValidation;
   }

   /**
    * @depends testInitGetServiceSessionToken
    */
   public function testCreateDemoUser($sessionToken) {
      $headers = ['Session-Token' => $sessionToken];
      $body = json_encode([
            'input'     => [
                  'name'      => self::$registeredUser,
                  'password'  => self::$registeredPass,
                  'password2' => self::$registeredPass,
                  'firstname' => 'John',
                  'realname'  => 'Doe',
            ],
      ]);
      $this->emulateRestRequest('post', 'PluginStorkmdmUser', $headers, $body);

      // Check user creation
      $this->assertEquals(201, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertArrayHasKey('id', $this->restResponse, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Check the user has only inactive registered user profile
      $config = Config::getConfigurationValues('storkmdm', ['inactive_registered_profiles_id', 'registered_profiles_id']);
      $profiles = Profile_User::getUserProfiles($this->restResponse['id']);
      $this->assertCount(1, $profiles);
      $this->assertArrayHasKey($config['inactive_registered_profiles_id'], $profiles);

      // check the account validation item
      $accountValidation = $this->getAccountValidation($this->restResponse['id']);
      $this->assertNotNull($accountValidation);
      $this->assertEquals($config['registered_profiles_id'], $accountValidation->getField('profiles_id'));

      return $this->restResponse['id'];
   }

   /**
    * @dataProvider allAccountsProvider
    * @depends testInitGetServiceSessionToken
    */
   public function testCreateOtherDemoUsers($name, $password, $firstname, $realname, $sessionToken) {
      $headers = ['Session-Token' => $sessionToken];
      $body = json_encode([
            'input'     => [
                  'name'      => $name,
                  'password'  => $password,
                  'password2' => $password,
                  'firstname' => $firstname,
                  'realname'  => $realname,
            ],
      ]);
      $this->emulateRestRequest('post', 'PluginStorkmdmUser', $headers, $body);

      // Check user creation
      $this->assertEquals(201, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));
   }

   /**
    * @dataProvider inactiveAccountProvider
    * @depends testInitGetServiceSessionToken
    * @depends testCreateOtherDemoUsers
    */
     public  function testInitExpireNotActivatedAccount($name, $password, $firstname, $realname, $sessionToken) {
      global $DB;

      $user = new User();
      $this->assertTrue($user->getFromDBbyName($name));
      $userId = $user->getID();

      $accountValidation_table = PluginStorkmdmAccountvalidation::getTable();
      $this->assertNotFalse($DB->query("UPDATE `$accountValidation_table`
                                        SET `date_creation` = '2016-01-01 00:00:00'
                                        WHERE `users_id` = '$userId'"));

   }

   /**
    * @dataProvider activeAndExpiredAccountProvider
    * @depends testInitGetServiceSessionToken
    * @depends testCreateOtherDemoUsers
    */
    public function testInitActivateOtherAccount($name, $password, $firstname, $realname, $sessionToken) {
       global $DB;

      $user = new User();
      $this->assertTrue($user->getFromDBbyName($name));
      $userId = $user->getID();
      $this->testActivateDemoAccount($sessionToken, $userId);

   }

   /**
    * @dataProvider expiredAccountProvider
    * @depends testInitGetServiceSessionToken
    * @depends testCreateOtherDemoUsers
    */
   public function testInitExpireOtherTrialAccounts($name, $password, $firstname, $realname, $sessionToken) {
      global $DB;

      $user = new User();
      $this->assertTrue($user->getFromDBbyName($name));
      $userId = $user->getID();

      $accountValidation_table = PluginStorkmdmAccountvalidation::getTable();
      $this->assertNotFalse($DB->query("UPDATE `$accountValidation_table`
                                        SET `date_end_trial` = '2016-01-01 00:00:00'
                                        WHERE `users_id` = '$userId'"));
   }

   /**
    * @dataProvider toRemindAccountProvider
    * @depends testInitGetServiceSessionToken
    * @depends testCreateOtherDemoUsers
    */
   public function testInitReachRemindDateForTrialAccounts($name, $password, $firstname, $realname, $sessionToken) {
      global $DB;

      $user = new User();
      $this->assertTrue($user->getFromDBbyName($name));
      $userId = $user->getID();

      // Divide by 2 the reminder delay before expiration
      $endOfTrialDatetime = new DateTime();
      $remindDateTime = new DateTime();
      $endOfTrialDatetime->add(new DateInterval(PluginStorkmdmAccountvalidation::TRIAL_LIFETIME));
      $remindDateTime->add(new DateInterval(PluginStorkmdmAccountvalidation::TRIAL_REMIND));
      $half = $endOfTrialDatetime->getTimestamp() - $remindDateTime->getTimestamp();
      $half = (int) ($half / 2);
      $expirationDateTime = new DateTime();
      $expirationDateTime->add(new DateInterval('PT' . $half . 'S'));
      $expirationDateTime = $expirationDateTime->format('Y-m-d H:i:s');

      $accountValidation_table = PluginStorkmdmAccountvalidation::getTable();
      $this->assertNotFalse($DB->query("UPDATE `$accountValidation_table`
             SET `date_end_trial` = '$expirationDateTime'
             WHERE `users_id` = '$userId'"));
   }

   /**
    * @depends testInitGetServiceSessionToken
    * @depends testCreateDemoUser
    */
   public function testActivateDemoAccountWithBadPass($sessionToken, $userId) {
      $accountValidation = $this->getAccountValidation($userId);
      $headers = ['Session-Token' => $sessionToken];
      $body = json_encode([
           'input'     => [
                 'id'            => $accountValidation->getID(),
                  '_validate'    => $accountValidation->getField('validation_pass') . "ab",
           ],
      ]);
      $this->emulateRestRequest('put', 'PluginStorkmdmAccountValidation', $headers, $body);

      // Request should faile due to bad validation pass
      $this->assertGreaterThanOrEqual(400, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Check the user has only inactive registered user profile
      $config = Config::getConfigurationValues('storkmdm', ['inactive_registered_profiles_id', 'registered_profiles_id']);
      $profiles = Profile_User::getUserProfiles($userId);
      $this->assertCount(1, $profiles);
      $this->assertArrayHasKey($config['inactive_registered_profiles_id'], $profiles);
   }


   /**
    * @depends testInitGetServiceSessionToken
    * @depends testCreateDemoUser
    */
   public function testActivateDemoAccountWithEmptyPass($sessionToken, $userId) {
      $accountValidation = $this->getAccountValidation($userId);
      $headers = ['Session-Token' => $sessionToken];
      $body = json_encode([
            'input'     => [
                  'id'           => $accountValidation->getID(),
                  '_validate'    => '',
            ],
      ]);
      $this->emulateRestRequest('put', 'PluginStorkmdmAccountValidation', $headers, $body);

      // Request should faile due to bad validation pass
      $this->assertGreaterThanOrEqual(400, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Check the user has only inactive registered user profile
      $config = Config::getConfigurationValues('storkmdm', ['inactive_registered_profiles_id', 'registered_profiles_id']);
      $profiles = Profile_User::getUserProfiles($userId);
      $this->assertCount(1, $profiles);
      $this->assertArrayHasKey($config['inactive_registered_profiles_id'], $profiles);
   }

   /**
    * @depends testInitGetServiceSessionToken
    * @depends testCreateDemoUser
    */
   public function testActivateDemoAccountWithExpiredPass($sessionToken, $userId) {
      global $DB;

      $accountValidation = $this->getAccountValidation($userId);

      // Force expiration of the validation pass
      $accountValidation_table = PluginStorkmdmAccountvalidation::getTable();
      $success = $DB->query("UPDATE `$accountValidation_table`
                             SET `date_creation` = '1970-01-01 00:00:00'
                             WHERE `users_id` = '$userId'");

      // Check the creation date is actually updated
      $this->assertTrue($success);

      // Try to validate the account
      $headers = ['Session-Token' => $sessionToken];
      $body = json_encode([
            'input'     => [
                  'id'           => $accountValidation->getID(),
                  '_validate'    => $accountValidation->getField('validation_pass'),
            ],
      ]);
      $this->emulateRestRequest('put', 'PluginStorkmdmAccountValidation', $headers, $body);

      // Request should faile due to bad validation pass
      $this->assertGreaterThanOrEqual(400, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Check the user has only inactive registered user profile
      $config = Config::getConfigurationValues('storkmdm', ['inactive_registered_profiles_id', 'registered_profiles_id']);
      $profiles = Profile_User::getUserProfiles($userId);
      $this->assertCount(1, $profiles);
      $this->assertArrayHasKey($config['inactive_registered_profiles_id'], $profiles);
   }

   /**
    * @depends testInitGetServiceSessionToken
    * @depends testCreateDemoUser
    */
   public function testActivateDemoAccount($sessionToken, $userId) {
      global $DB;

      $accountValidation = $this->getAccountValidation($userId);

      // Force activation pass to be useable
      $date = new DateTime();
      $accountValidation_table = PluginStorkmdmAccountvalidation::getTable();
      $success = $DB->query("UPDATE `$accountValidation_table`
            SET `date_creation` = '" . $date->format('Y-m-d H:i:s') ."'
            WHERE `users_id` = '$userId'");

      // Check the creation date is actually updated
      $this->assertTrue($success);

      // Try to validate the account
      $headers = ['Session-Token' => $sessionToken];
      $body = json_encode([
            'input'     => [
                  'id'           => $accountValidation->getID(),
                  '_validate'    => $accountValidation->getField('validation_pass'),
            ],
      ]);
      $this->emulateRestRequest('put', 'PluginStorkmdmAccountValidation', $headers, $body);

      // Request should succeed
      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Check the accountValidation is actually updated
      $this->assertArrayHasKey($accountValidation->getID(), $this->restResponse[0], json_encode($this->restResponse, JSON_PRETTY_PRINT));
      $this->assertTrue($this->restResponse[0][$accountValidation->getID()], json_encode($this->restResponse, JSON_PRETTY_PRINT));

      // Refresh the accountValidation from DB
      $accountValidation = $this->getAccountValidation($userId);

      // Check the validation pass is deleted
      $this->assertEmpty($accountValidation->getField('validation_pass'));

      // Check the user has only (active) registered user profile
      $config = Config::getConfigurationValues('storkmdm', ['inactive_registered_profiles_id', 'registered_profiles_id']);
      $profiles = Profile_User::getUserProfiles($userId);
      $this->assertCount(1, $profiles);
      $this->assertArrayHasKey($config['registered_profiles_id'], $profiles);
   }

   /**
    * @depends testActivateDemoAccount
    */
   public function testGetDemoAccountSession() {
      $credentials = base64_encode(self::$registeredUser . ':' . self::$registeredPass);
      $headers = ['authorization' => "Basic $credentials"];

      $this->emulateRestRequest('get', 'initSession', $headers);

      $this->assertEquals(200, $this->restHttpCode, json_encode($this->restResponse, JSON_PRETTY_PRINT));

      return $this->restResponse['session_token'];
   }

   /**
    * @dataProvider inactiveAccountProvider
    * @depends testInitExpireNotActivatedAccount
    */
   public function testCleanupNotActivatedAccounts($name, $password, $firstname, $realname) {
      $user = new User();
      $user->getFromDBbyName($name);
      $this->assertFalse($user->isNewItem());

      CronTask::launch(-1, 1, 'CleanupAccountActivation');

      $user2 = new User();
      $user2->getFromDB($user->getID());
      $this->assertTrue($user2->isNewItem());
   }

   /**
    * @dataProvider expiredAccountProvider
    * @depends testInitExpireOtherTrialAccounts
    */
   public function testDisableAccountsWithTrialOver($name, $password, $firstname, $realname) {
      $user = new User();
      $user->getFromDBbyName($name);
      $this->assertFalse($user->isNewItem());

      CronTask::launch(-1, 1, 'DisableExpiredTrial');

      $config = Config::getConfigurationValues('storkmdm', ['inactive_registered_profiles_id', 'registered_profiles_id']);
      $profiles = Profile_User::getUserProfiles($user->getID());
      $this->assertCount(1, $profiles);
      $this->assertArrayHasKey($config['inactive_registered_profiles_id'], $profiles);
   }

   /**
    * @dataProvider toRemindAccountProvider
    * @depends testInitReachRemindDateForTrialAccounts
    */
   public function testRemindActiveAccount($name, $password, $firstname, $realname) {
      $user = new User();
      $user->getFromDBbyName($name);
      $this->assertFalse($user->isNewItem());

      CronTask::launch(-1, 1, 'RemindTrialExpiration');

      $accountValidation = $this->getAccountValidation($user->getID());
      $this->assertEquals('1', $accountValidation->getField('is_reminder_sent'));
   }
}