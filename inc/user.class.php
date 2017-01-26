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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmUser extends User {

   static $rightname = 'flyvemdm:user';

   /**
    * @var Entity
    */
   protected $entity = null;

   /**
    * @var PluginFlyvemdmFleet
    */
   protected $defaultFleet = null;

   /**
    * true if a user is being created with this class.
    * Needed to unlock entity creation using an user with the service profile
    * @var boolean $creation
    */
   protected static $creation = false;

   public static function getCreation() {
      return self::$creation;
   }

   /**
    * Return the table used to stor this object
    * @return string
    */
   public static function getTable() {
      if (empty($_SESSION['glpi_table_of'][get_called_class()])) {
         $_SESSION['glpi_table_of'][get_called_class()] = User::getTable();
      }

      return $_SESSION['glpi_table_of'][get_called_class()];
   }

   /**
    * Name of the type
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb=0) {
      return _n('FlyveMDM user', 'FlyveMDM users', $nb, 'flyvemdm');
   }

   /**
    * {@inheritDoc}
    * @see User::canCreateItem()
    */
   public function canCreateItem() {
      // Will be created from form, with selected entity/profile
      if (isset($this->input['_profiles_id']) && ($this->input['_profiles_id'] > 0)
          && isset($this->input['_entities_id'])
          && Session::haveAccessToEntity($this->input['_entities_id'])) {
         return true;
      }
      // Will be created with default value
      if (Session::haveAccessToEntity(0) // Access to root entity (required when no default profile)
          || (Profile::getDefault() > 0)) {
         return true;
      }

      return false;
   }

   /**
    * Have I the global right to "create" the Object
    * @return booleen
    */
   public static function canCreate() {
      $config = Config::getConfigurationValues('flyvemdm', array('registered_profiles_id'));
      $registeredProfileId = $config['registered_profiles_id'];
      if ($registeredProfileId === null) {
         return false;
      }

      if ($_SESSION['glpiactiveprofile']['id'] == $registeredProfileId) {
         return false;
      }

      return User::canCreate() && Entity::canCreate();
   }

   /**
    * Prepare input datas for adding the item
    * @param $input datas used to add the item
    * @return the modified $input array
    */
   public function prepareInputForAdd($input) {
      $input = $this->checkPassword($input);
      if (!is_array($input)) {
         return false;
      }

      $input['name'] = filter_var($input['name'], FILTER_VALIDATE_EMAIL);
      if (!$input['name']) {
         // User name is not an email
         Session::addMessageAfterRedirect(__('Email address is not valid', 'flyvemdm'));
         return false;
      }

      // Create the user, with authorization on his entity via the registered user profile
      $config = Config::getConfigurationValues('flyvemdm', array(
            'registered_profiles_id',
            'inactive_registered_profiles_id',
      ));

      $accountValidation = new PluginFlyvemdmAccountvalidation();
      $demoMode = $accountValidation->isDemoEnabled();

      if ($demoMode) {
         $registeredProfileId = $config['inactive_registered_profiles_id'];
         if (!isset($config['registered_profiles_id'])) {
            Session::addMessageAfterRedirect(__('No inactive profile available to setup user rights', 'flyvemdm'));
            return false;
         }
      } else {
         $registeredProfileId = $config['registered_profiles_id'];
         if (!isset($config['registered_profiles_id'])) {
            Session::addMessageAfterRedirect(__('No profile available to setup user rights', 'flyvemdm'));
            return false;
         }
      }

      // Create an entity for the user
      self::$creation = true;
      $this->entity = new Entity();
      $entityId = $this->entity->add(array(
            "entities_id" => 0,
            "name" => $input["name"]
      ));
      self::$creation = false;
      if ($entityId === false) {
         Session::addMessageAfterRedirect(__('An entity already exists for your email. You probably already have an account.', 'flyvemdm'));
         return false;
      }

      // Create the default fleet for the new entity
      $this->defaultFleet = new PluginFlyvemdmFleet();
      $fleetId = $this->defaultFleet->add(array(
         'is_default'  => '1',
         'name'        => __("not managed fleet", 'flyvemdm'),
         'entities_id' => $entityId
      ));
      if ($fleetId === false) {
         $this->defaultFleet = null;
         $this->entity->delete(['id' => $entityId]);
         $this->entity = null;
         Session::addMessageAfterRedirect(__('Failed to create default fleet', 'flyvemdm'));
         return false;
      }

      // Force $_SESSION to have rights to create a profile because the user is not loged in yet
      $input = parent::prepareInputForAdd($input);
      if ($input === false) {
         return false;
      }
      $input["_profiles_id"]  = $registeredProfileId;
      $input["profiles_id"]   = $registeredProfileId;      // Default profile when user logs in
      $input["_entities_id"]  = $this->entity->getID();
      $input["_is_recursive"] = 0;
      $input['_useremails']   = array ($input['name']);

      return $input;
   }

   /**
    * Check the password for a new account is valid
    */
   protected function checkPassword($input) {
      if ($input['password'] != $input['password2']) {
         // Password and password check are different
         Session::addMessageAfterRedirect(__('Passwords are different', 'flyvemdm'));
         return false;
      }

      if (strlen($input['password']) < 8) {
         Session::addMessageAfterRedirect(__('Password too short', 'flyvemdm'));
         return false;
      }

      return $input;
   }

   /**
    * {@inheritDoc}
    * @see User::post_addItem()
    */
   public function post_addItem() {
      // add emails (use _useremails set from UI, not _emails set from LDAP)
      if (isset($this->input['_useremails']) && count($this->input['_useremails'])) {
         $useremail = new UserEmail();
         foreach ($this->input['_useremails'] as $id => $email) {
            $email = trim($email);
            $email_input = array('email'    => $email,
                                 'users_id' => $this->getID());
            if (isset($this->input['_default_email'])
                && ($this->input['_default_email'] == $id)) {
               $email_input['is_default'] = 1;
            } else {
               $email_input['is_default'] = 0;
            }
            $useremail->add($email_input);
         }
      }

      $this->syncLdapGroups();
      $this->syncDynamicEmails();
      $rulesplayed = $this->applyRightRules();
      $picture     = $this->syncLdapPhoto();

      //add picture in user fields
      if (!empty($picture)) {
         $this->update(array('id'      => $this->fields['id'],
                             'picture' => $picture));
      }

      // Add default profile
      if (!$rulesplayed) {
         $affectation = array();
         if (isset($this->input['_profiles_id']) && $this->input['_profiles_id']) {
            $profile                   = $this->input['_profiles_id'];
            // Choosen in form, so not dynamic
            $affectation['is_dynamic'] = 0;
         } else {
            $profile                   = Profile::getDefault();
            // Default right as dynamic. If dynamic rights are set it will disappear.
            $affectation['is_dynamic'] = 1;
         }

         if ($profile) {
            if (isset($this->input["_entities_id"])) {
               // entities_id (user's pref) always set in prepareInputForAdd
               // use _entities_id for default right
               $affectation["entities_id"] = $this->input["_entities_id"];

            } else if (isset($_SESSION['glpiactive_entity'])) {
               $affectation["entities_id"] = $_SESSION['glpiactive_entity'];

            } else {
               $affectation["entities_id"] = 0;
            }
            if (isset($this->input["_is_recursive"])) {
               $affectation["is_recursive"] = $this->input["_is_recursive"];
            } else {
               $affectation["is_recursive"] = 0;
            }

            $affectation["profiles_id"]  = $profile;
            $affectation["users_id"]     = $this->fields["id"];
            $right                       = new Profile_User();
            $right->add($affectation);

            // If demo mode enabled, send an activation email
            $accountValidation = new PluginFlyvemdmAccountvalidation();
            $demoMode = $accountValidation->isDemoEnabled();

            if ($demoMode) {
               $config = Config::getConfigurationValues('flyvemdm', array(
                     'registered_profiles_id',
               ));
               $affectation['assigned_entities_id'] = $affectation['entities_id'];
               $affectation['profiles_id']          = $config['registered_profiles_id'];
               $accountValidation->add($affectation);

               NotificationEvent::raiseEvent(
                     PluginFlyvemdmNotificationTargetAccountvalidation::EVENT_SELF_REGISTRATION,
                     $accountValidation,
                     array('entities_id' => $right->getField('entities_id'))
               );
            }
         }
      }
   }

   /**
    * Creating users (not PluginFlyvemdmUser) with service account is forbidden
    * @param CommonDBTM $item
    */
   public static function hook_pre_user_add(CommonDBTM $item) {
      $config = Config::getConfigurationValues('flyvemdm', array('service_profiles_id'));
      $serviceProfileId = $config['service_profiles_id'];
      if ($serviceProfileId === null) {
         $item->input = null;
         return false;
      }

      if ($_SESSION['glpiactiveprofile']['id'] == $serviceProfileId) {
         $item->input = null;
         return false;
      }
   }

   /**
    *
    */
   public static function hook_pre_user_purge(CommonDBTM $item) {
      $computer = new Computer();
      $userId = $item->getID();
      $rows = $computer->find("`users_id`='$userId'", '', '1');
      if (count($rows) > 0) {
         $item->input = false;
      }
   }

}
