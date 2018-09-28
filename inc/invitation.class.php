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
 * @license   http://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmInvitation extends CommonDBTM {

   const DEFAULT_TOKEN_LIFETIME = "P7D";

   /**
    * @var string $rightname name of the right in DB
    */
   public static $rightname = 'flyvemdm:invitation';

   /**
    * @var User The invited user
    */
   protected $user;

   /**
    * Gets the possibles statuses that an invitation can have
    * @return array the possibles statuses of the invitation
    */
   public function getEnumInvitationStatus() {
      return [
         'pending' => __('Pending', 'flyvemdm'),
         'done'    => __('Done', 'flyvemdm'),
      ];
   }

   /**
    * Localized name of the type
    * @param integer $nb number of item in the type (default 0)
    * @return string
    */
   public static function getTypeName($nb = 0) {
      return _n('Invitation', 'Invitations', $nb, 'flyvemdm');
   }

   /**
    * Returns the URI to the picture file relative to the front/folder of the plugin
    * @return string URI to the picture file
    */
   public static function getMenuPicture() {
      return 'fa-paper-plane';
   }

   /**
    * @since version 0.1.0
    * @see commonDBTM::getRights()
    */
   public function getRights($interface = 'central') {
      $rights = parent::getRights();
      /// For additional righrs if needed
      //$rights[self::RIGHTS] = self::getTypeName();

      return $rights;
   }

   /**
    * Prepares input to follow the most used description convention
    * @param array $input the data to use when creating a new row in the DB
    * @return array|false the modified input data
    */
   public function prepareInputForAdd($input) {
      // Find guest profile's id
      $config = Config::getConfigurationValues("flyvemdm", ['guest_profiles_id']);
      $guestProfileId = $config['guest_profiles_id'];

      if (!isset($input['entities_id']) || !isset($input['users_id'])) {
         Session::addMessageAfterRedirect(__("Invalid data", 'flyvemdm'));
         return false;
      }
      $entityId = $input['entities_id'];

      // Find the user
      $userId = (int) $input[User::getForeignKeyField()];
      $user = new User();
      $user->getFromDB($userId);
      // Do not handle deleted users
      if ($user->isDeleted()) {
         Session::addMessageAfterRedirect(__("The user was deleted. You must restore him first.",
            'flyvemdm'), false, INFO, true);
         return false;
      }

      // The user already exists, add him in the entity
      $userId = $user->getID();
      $profile_User = new Profile_User();
      $entities = $profile_User->getEntitiesForProfileByUser($userId, $guestProfileId);
      if (!isset($entities[$_SESSION['glpiactive_entity']])) {
         $profile_User->add([
            'users_id'     => $userId,
            'profiles_id'  => $guestProfileId,
            'entities_id'  => $entityId,
            'is_recursive' => 0,
         ]);
      }

      // Ensure the user has a token
      $personalToken = User::getToken($user->getID(), 'api_token');
      if ($personalToken === false) {
         return false;
      }

      // Generate a invitation token
      $input['invitation_token'] = $this->setInvitationToken();

      // Get the default expiration delay
      $entityConfig = new PluginFlyvemdmEntityConfig();
      $tokenExpire = self::DEFAULT_TOKEN_LIFETIME;
      if ($entityConfig->getFromDB($_SESSION['glpiactive_entity'])) {
         $tokenExpire = $entityConfig->getField('agent_token_life');
      }

      // Compute the expiration date of the invitation
      $expirationDate = new DateTime("now");
      try {
         $expirationDate->add(new DateInterval($tokenExpire));
      } catch (Exception $e) {
         Session::addMessageAfterRedirect(__("Invalid token expiration date interval",
            'flyvemdm'), false, ERROR, true);
         return false;
      }
      $input['expiration_date'] = $expirationDate->format('Y-m-d H:i:s');

      // Set a name for the invitation
      $input['name'] = self::getTypeName(1);
      return $input;
   }

   /**
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      // Registered users need right to send again an invitation
      // but shall not be able to edit anything
      $config = Config::getConfigurationValues('flyvemdm', ['registered_profiles_id']);
      $registeredProfileId = $config['registered_profiles_id'];
      if ($_SESSION['glpiactiveprofile']['id'] == $registeredProfileId) {
         $forbidden = array_diff_key(
            $input,
            [
               'id'          => '',
               '_notify'     => '',
               '_no_history' => '',
            ]
         );
         if (count($forbidden)) {
            // An attempt to edit the item by a registered use
            return false;
         }
      }

      if (isset($input['_notify'])) {
         $this->sendInvitation();
      }

      return $input;
   }

   /**
    * Finds the invitation that matches the token given in argument
    * @param string $token
    * @return boolean true if the invitation token exist
    */
   public function getFromDBByToken($token) {
      return $this->getFromDBByCrit(['invitation_token' => $token]);
   }

   /**
    * Generates the Invitation Token
    * @return string the generated token
    */
   protected function setInvitationToken() {
      $invitation = new static();
      do {
         $token = bin2hex(openssl_random_pseudo_bytes(32));
      } while ($invitation->getFromDBByToken($token));
      return $token;
   }

   public function pre_deleteItem() {
      $invitationLog = new PluginFlyvemdmInvitationlog();
      return $invitationLog->deleteByCriteria(['plugin_flyvemdm_invitations_id' => $this->getID()]);
   }

   public function post_addItem() {
      // Create / update import to entity rule
      $fi = new PluginFlyvemdmFusionInventory();
      $fi->addInvitationRule($this);

      // Generate QR code
      $this->createQRCodeDocument();

      // Sent invitation email
      $this->sendInvitation();
   }

   public function post_updateItem($history = 1) {
      // Update the dynamic import entity rules
      if (in_array('status', $this->updates)) {
         $fi = new PluginFlyvemdmFusionInventory();
         switch ($this->fields['status']) {
            case  'done':
               $fi->deleteInvitationRuleCriteria($this);
               break;
            case 'pending':
               $fi->addInvitationRule($this);
               break;
         }
      }
   }

   /**
    * @see CommonDBTM::pre_deleteItem()
    * @param CommonDBTM $item
    * @return bool
    */
   public function hook_pre_invitation_purge(CommonDBTM $item) {
      $fi = new PluginFlyvemdmFusionInventory();
      $fi->deleteInvitationRuleCriteria($item);
      $document = new Document();
      return $document->delete([
         'id' => $item->getField('documents_id'),
      ], 1);
   }

   /**
    * Actions done when a document is being purged
    * @param CommonDBTM $item Document
    */
   public static function hook_pre_document_purge(CommonDBTM $item) {
      $invitation = new self();
      $documentId = $item->getID();
      $rows = $invitation->find("`documents_id`='$documentId'", '', '1');
      if (count($rows) > 0) {
         Session::addMessageAfterRedirect(__('Cannot delete the document. Delete the attached invitation first',
            'flyvemdm'));
         $item->input = false;
      }
   }

   /**
    * Returns the invited user
    * @return User
    */
   public function getUser() {
      if ($this->isNewItem()) {
         return null;
      }
      $this->user = new User();
      if (!$this->user->getFromDB($this->fields['users_id'])) {
         $this->user = null;
      }
      return $this->user;
   }

   /**
    * get the enrollment URL of the agent
    * @return string URL to enroll a mobile Device
    */
   protected function createQRCodeDocument() {

      $user = new User();
      $user->getFromDB($this->fields['users_id']);
      $entityId = $this->fields['entities_id'];
      $encodedRequest = $this->getEnrollmentUrl();

      // Generate a QRCode
      $barcodeobj = new TCPDF2DBarcode($encodedRequest, 'QRCODE,L');
      $qrCode = $barcodeobj->getBarcodePngData(7, 7, [0, 0, 0]);

      // Add border to the QR
      // TCPDF forgets the quiet zone
      $borderSize = 30;
      $image = imagecreatefromstring($qrCode);
      $width = imagesx($image);
      $height = imagesy($image);

      // Build new bigger image
      $compliantQRcode = imagecreatetruecolor($width + 2 * $borderSize, $height + 2 * $borderSize);
      $white = imagecolorallocate($compliantQRcode, 255, 255, 255);
      // Fill it with white
      imagefilledrectangle($compliantQRcode, 0, 0, $width + 2 * $borderSize,
         $height + 2 * $borderSize, $white);

      // Copy and center the qr code in the big image
      imagecopy($compliantQRcode, $image, $borderSize, $borderSize, 0, 0, $width, $height);

      // Save the image in a temporary file
      $tmpFile = uniqid() . ".png";
      imagepng($compliantQRcode, GLPI_TMP_DIR . "/" . $tmpFile, 9);

      // Generate a document with the QR code
      $input = [];
      $document = new Document();
      $input['entities_id'] = $entityId;
      $input['is_recursive'] = '0';
      $input['name'] = addslashes(__('Enrollment QR code', 'flyvemdm'));
      $input['_filename'] = [$tmpFile];
      $input['_only_if_upload_succeed'] = true;
      $documentId = $document->add($input);

      $documentItem = new Document_Item();
      $documentItem->add([
         'entities_id'  => $entityId,
         'is_recursive' => 0,
         'itemtype'     => PluginFlyvemdmInvitation::class,
         'items_id'     => $this->fields['id'],
         'documents_id' => $documentId,
      ]);

      return $documentId;
   }

   /**
    * Generates the enrollment URL for the invitation
    * @return string
    */
   public function getEnrollmentUrl() {
      global $CFG_GLPI;

      // Get the general config of Flyve MDM
      $config = Config::getConfigurationValues('flyvemdm', ['invitation_deeplink']);

      $user = new User();
      $user->getFromDB($this->fields['users_id']);
      $invitationToken = $this->fields['invitation_token'];
      $entities_id = $this->fields['entities_id'];

      // Get the entitiy configuration data
      $entityConfig = new PluginFlyvemdmEntityConfig();
      $entityConfig->getFromDBByCrit([
         'entities_id' => $entities_id
      ]);

      // Build the data of the deeplink
      $personalToken = User::getToken($user->getID(), 'api_token');
      $enrollmentData = [
         'url'                => rtrim($CFG_GLPI['url_base_api'], '/'),
         'user_token'         => $personalToken,
         'invitation_token'   => $invitationToken,
         'support_name'       => $entityConfig->getField('support_name'),
         'support_phone'      => $entityConfig->getField('support_phone'),
         'support_website'    => $entityConfig->getField('support_website'),
         'support_email'      => $entityConfig->getField('support_email'),
         //'support_address'    => $entityConfig->getField('support_address'),
      ];

      return $config['invitation_deeplink'] . PluginFlyvemdmNotificationTargetInvitation::DEEPLINK
                        . base64_encode(addcslashes(implode(';', $enrollmentData), '\;'));
   }

   /**
    * Sends an invitation
    */
   public function sendInvitation() {
      NotificationEvent::raiseEvent(
         PluginFlyvemdmNotificationTargetInvitation::EVENT_GUEST_INVITATION,
         $this
      );
   }

   /**
    * @see CommonDBTM::getSearchOptionsNew()
    * @return array
    */
   public function getSearchOptionsNew() {
      return $this->rawSearchOptions();
   }

   public function rawSearchOptions() {
      if (method_exists('CommonDBTM', 'rawSearchOptions')) {
         $tab = parent::rawSearchOptions();
      } else {
         $tab = parent::getSearchOptionsNew();
      }

      $tab[] = [
         'id'            => '2',
         'table'         => $this->getTable(),
         'field'         => 'id',
         'name'          => __('ID'),
         'massiveaction' => false,
         'datatype'      => 'number'
      ];

      $tab[] = [
         'id'            => '3',
         'table'         => 'glpi_users',
         'field'         => 'name',
         'name'          => __('User'),
         'massiveaction' => false,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '4',
         'table'         => $this->getTable(),
         'field'         => 'status',
         'name'          => __('Status'),
         'massiveaction' => false,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'            => '5',
         'table'         => $this->getTable(),
         'field'         => 'expiration_date',
         'name'          => __('Expiration date'),
         'massiveaction' => false,
         'datatype'      => 'string',
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }

   /**
    * Deletes the invitation related to the entity being purged
    * @param CommonDBTM $item
    */
   public function hook_entity_purge(CommonDBTM $item) {
      $invitation = new static();
      $invitation->deleteByCriteria(['entities_id' => $item->getField('id')], 1);
   }

   /**
    * Show form for edition
    * @param integer $ID
    * @param array $options
    */
   public function showForm($ID, array $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      $canUpdate = (!$this->isNewID($ID)) && ($this->canView() > 0) || $this->isNewID($ID);

      $fields = $this->fields;
      $fields['user'] = User::dropdown([
         'name'      => 'users_id',
         'value'     => $this->fields['users_id'],
         'entity'    => $this->fields['entities_id'],
         'right'     => 'all',
         'rand'      => mt_rand(),
         'display'   => false,
      ]);
      $data = [
         'withTemplate' => (isset($options['withtemplate']) && $options['withtemplate'] ? "*" : ""),
         'canUpdate'    => $canUpdate,
         'isNewID'      => $this->isNewID($ID),
         'invitation'   => $fields,
         'resendButton' => Html::submit(_x('button', 'Re-send'), ['name' => 'resend']),
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('invitation.html.twig', $data);

      if (!$this->isNewID($ID)) {
         $options['canedit'] = true;
      }
      $this->showFormButtons($options);
   }

   /**
    * Displays the massive actions related to the invitation of the user
    * @return string a HTML with the masssive actions
    */
   protected function showMassiveActionInviteUser() {
      $twig = plugin_flyvemdm_getTemplateEngine();
      $data = [
         'inviteButton' => Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']),
      ];
      echo $twig->render('mass_invitation.html.twig', $data);
   }

   /**
    *
    * @param MassiveAction $ma
    * @return bool
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      switch ($ma->getAction()) {
         case 'InviteUser':
            $invitation = new static();
            $invitation->showMassiveActionInviteUser();
            return true;

      }
   }

   /**
    * Executes the code to process the massive actions
    *
    * @param MassiveAction $ma
    * @param CommonDBTM $item
    * @param array $ids
    */
   public static function processMassiveActionsForOneItemtype(
      MassiveAction $ma,
      CommonDBTM $item,
      array $ids
   ) {
      switch ($ma->getAction()) {
         case 'InviteUser':
            if (!$item->getType() == User::class) {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
            foreach ($ids as $id) {
               $item->getFromDB($id);

               // Do not invite users without a default email address
               $useremail = new UserEmail();
               $emailAddress = $useremail->getDefaultForUser($id);
               if (empty($emailAddress)) {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  continue;
               }

               $invitation = new PluginFlyvemdmInvitation();
               $invitation->add([
                  User::getForeignKeyField() => $id,
                  'entities_id' => $_SESSION['glpiactive_entity'],
               ]);
               if ($invitation->isNewItem()) {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  continue;
               }

               $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
            }
      }

      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }
}
