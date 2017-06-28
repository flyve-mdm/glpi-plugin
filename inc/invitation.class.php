<?php
/*
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
 @link      https://github.com/flyve-mdm/flyve-mdm-glpi
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmInvitation extends CommonDBTM {

   const DEFAULT_TOKEN_LIFETIME  = "P7D";

   /**
    * @var string $rightname name of the right in DB
    */
   public static $rightname            = 'flyvemdm:invitation';

   /**
    * @var User The invited user
    */
   protected $user;

   public function getEnumInvitationStatus() {
      return array(
            'pending'         => __('Pending', 'flyvemdm'),
            'done'            => __('Done', 'flyvemdm'),
      );
   }

   /**
    * Localized name of the type
    * @param $nb  integer  number of item in the type (default 0)
    */
   public static function getTypeName($nb=0) {
      return _n('Invitation', 'Invitations', $nb, "flyvemdm");
   }

   public static function getMenuPicture() {
      return '../pics/picto-invitation.png';
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

   public function prepareInputForAdd($input) {
      // integrity checks
      if (!isset($input['_useremails'])) {
         Session::addMessageAfterRedirect(__("Email address is invalid", 'flyvemdm'));
         return false;
      }

      $input['_useremails'] = filter_var($input['_useremails'], FILTER_VALIDATE_EMAIL);
      if (!$input['_useremails']) {
         Session::addMessageAfterRedirect(__("Email address is invalid", 'flyvemdm'));
         return false;
      }

      // Find guest profile's id
      $config = Config::getConfigurationValues("flyvemdm", ['guest_profiles_id']);
      $guestProfileId = $config['guest_profiles_id'];

      $entityId = $input['entities_id'];

      // Find or create the user
      $userIsNew = false;
      $user = new User();
      if (!$user->getFromDBbyName($input['_useremails'], '')) {
         // The user does not exists yet, create him
         $userId = $user->add([
            '_useremails'     => array($input['_useremails']),
            'name'            => $input['_useremails'],
            '_profiles_id'    => $guestProfileId,
            '_entities_id'    => $entityId,
            '_is_recursive'   => 0,
            'authtype'        => Auth::DB_GLPI
         ]);
         $userIsNew = true;
         if ($user->isNewItem()) {
            Session::addMessageAfterRedirect(__("Cannot create the user", 'flyvemdm'), false, INFO, true);
            return false;
         }

      } else {
         // Do not handle deleted users
         if ($user->isDeleted()) {
            Session::addMessageAfterRedirect(__("The user already exists and has been deleted. You must restore or purge him first.", 'flyvemdm'), false, INFO, true);
            return false;
         }

         // The user already exists, add him in the entity
         $userId = $user->getID();
         $profile_User = new Profile_User();
         $entities = $profile_User->getEntitiesForProfileByUser($userId, $guestProfileId);
         if (!isset($entities[$_SESSION['glpiactive_entity']])) {
            $profile_User->add([
                  'users_id'       => $userId,
                  'profiles_id'    => $guestProfileId,
                  'entities_id'    => $_SESSION['glpiactive_entity'],
                  'is_recursive'   => 0,
            ]);
         }
      }
      $input['users_id'] = $userId;

      // Ensure the user has a token
      $personalToken = User::getToken($user->getID(), 'api_token');
      if ($personalToken === false) {
         return false;
      }

      // Generate a invitation token
      $input['invitation_token'] = $this->setInvitationToken();

      // Get the default expiration delay
      $entityConfig = new PluginFlyvemdmEntityconfig();
      if ($entityConfig->getFromDB($_SESSION['glpiactive_entity'])) {
         $tokenExpire = $entityConfig->getField('agent_token_life');
      } else {
         $tokenExpire = self::DEFAULT_TOKEN_LIFETIME;
      }

      // Compute the expiration date of the invitation
      $expirationDate = new DateTime("now", new DateTimeZone("UTC"));
      $expirationDate->add(new DateInterval($tokenExpire));
      $input['expiration_date'] = $expirationDate->format('Y-m-d H:i:s');

      // Generate the QR code
      $documentId = $this->createQRCodeDocument($user, $input['invitation_token']);
      if ($documentId === false) {
         Session::addMessageAfterRedirect(__("Could not create enrollment QR code", 'flyvemdm'), false, INFO, true);
         return false;
      }

      $input['documents_id'] = $documentId;
      return $input;
   }

   /**
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      global $DB;

      // Registered users need right to send again an invitation
      // but shall not be able to edit anything
      $config = Config::getConfigurationValues('flyvemdm', array('registered_profiles_id'));
      $registeredProfileId = $config['registered_profiles_id'];
      if ($_SESSION['glpiactiveprofile']['id'] == $registeredProfileId) {
         $forbidden = array_diff_key(
               $input,
               [
                     'id'           => '',
                     '_notify'      => '',
                     '_no_history'  => '',
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

   public function getFromDBByToken($token) {
      $entityRestriction = getEntitiesRestrictRequest('AND', self::getTable(), "entities_id", '', false, true);
      return $this->getFromDBByQuery("WHERE `invitation_token`='$token' $entityRestriction");
   }

   protected function setInvitationToken() {
      return bin2hex(openssl_random_pseudo_bytes(32));
   }

   /**
    *
    * @see CommonDBTM::pre_deleteItem()
    */
   public function pre_deleteItem() {
      $invitationLog = new PluginFlyvemdmInvitationlog();
      return $invitationLog->deleteByCriteria(array('plugin_flyvemdm_invitations_id' => $this->getID()));
   }

   /**
    * @see CommonDBTM::post_addItem()
    */
   public function post_addItem() {
      $this->sendInvitation();
   }

   /**
    * @see CommonDBTM::pre_deleteItem()
    */
   public static function hook_pre_self_purge(CommonDBTM $item) {
      $document = new Document();
      $document->getFromDB($item->getField('documents_id'));
      return $document->delete([
            'id' => $item->getField('documents_id')
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
         Session::addMessageAfterRedirect(__('Cannot delete the document. Delete the attached invitation first', 'flyvemdm'));
         $item->input = false;
      }
   }

   /**
    * @return the invited user
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
    * @param User $user Recipient of the QR code
    * @param string $învitationToken Invitation token
    * @return string URL to enroll a mobile Device
    */
   protected function createQRCodeDocument(User $user, $învitationToken) {
      global $CFG_GLPI;

      $personalToken = User::getToken($user->getID(), 'api_token');
      $enrollRequest = [
         'url'                => rtrim($CFG_GLPI["url_base_api"], '/'),
         'user_token'         => $personalToken,
         'invitation_token'   => $învitationToken
      ];

      $encodedRequest = PluginFlyvemdmNotificationTargetInvitation::DEEPLINK
                        . base64_encode(json_encode($enrollRequest, JSON_UNESCAPED_SLASHES));

      // Generate a QRCode
      $barcodeobj = new TCPDF2DBarcode($encodedRequest, 'QRCODE,L');
      $qrCode = $barcodeobj->getBarcodePngData(4, 4, array(0, 0, 0));

      // Add border to the QR
      // TCPDF forgets the quiet zone
      $borderSize  = 30;
      $image = imagecreatefromstring($qrCode);
      $width = imagesx($image);
      $height = imagesy($image);

      // Build new bigger image
      $compliantQRcode = imagecreatetruecolor($width + 2 * $borderSize, $height + 2 * $borderSize);
      $white = imagecolorallocate($compliantQRcode, 255, 255, 255);
      // Fill it with white
      imagefilledrectangle($compliantQRcode, 0, 0, $width + 2 * $borderSize, $height + 2 * $borderSize, $white);

      // Copy and center the qr code in the big image
      imagecopy($compliantQRcode, $image, $borderSize, $borderSize, 0, 0, $width, $height);

      // Save the image in a temporary file
      $tmpFile = uniqid() . ".png";
      imagepng($compliantQRcode, GLPI_TMP_DIR . "/" . $tmpFile, 9);

      // Generate a document with the QR code
      $input = array();
      $document = new Document();
      $input['entities_id']               = $this->input['entities_id'];
      $input['is_recursive']              = '0';
      $input['name']                      = addslashes(__('Enrollment QR code', 'flyvemdm'));
      $input['_filename']                 = array($tmpFile);
      $input['_only_if_upload_succeed']   = true;
      $documentId = $document->add($input);

      // Build relation between the invitation and the document
      //$document_Item = new Document_Item();
      //$document_Item->add([
      //      'documents_id' => $documentId,
      //      'itemtype'     => 'PluginFlyvemdmInvitation',
      //      'items_id'     => $this->getID(),
      //      'entities_id'  => $this->fields['entities_id'],
      //      'is_recursive' => '0',
      //]);
      return $documentId;
   }

   public function sendInvitation() {
      NotificationEvent::raiseEvent(
            PluginFlyvemdmNotificationTargetInvitation::EVENT_GUEST_INVITATION,
            $this
      );
   }

   /**
    * @see CommonDBTM::getSearchOptions()
    */
   public function getSearchOptions() {
      global $CFG_GLPI;

      $tab = array();
      $tab['common']                 = __s('Invitation', "flyvemdm");

      $i = 1;
      $tab[$i]['table']               = User::getTable();
      $tab[$i]['field']               = 'name';
      $tab[$i]['name']                = __('Name');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'id';
      $tab[$i]['name']                = __('ID');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'number';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'status';
      $tab[$i]['name']                = __('Status');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      $i++;
      $tab[$i]['table']               = self::getTable();
      $tab[$i]['field']               = 'expiration_date';
      $tab[$i]['name']                = __('Expiration date', 'flyvemdm');
      $tab[$i]['massiveaction']       = false;
      $tab[$i]['datatype']            = 'string';

      return $tab;
   }

   public function hook_entity_purge(CommonDBTM $item) {
      $invitation = new static();
      $invitation->deleteByCriteria(array('entities_id' => $item->getField('id')), 1);
   }

   /**
    * Show form for edition
    */
   public function showForm($ID, $options = array()) {
      global $CFG_GLPI, $DB;

      $this->initForm($ID, $options);
      $this->showFormHeader();
      $twig = plugin_flyvemdm_getTemplateEngine();
      $fields                 = $this->fields;
      $user = new User();
      $user->getFromDB($fields['users_id']);
      $fields['_useremails']  = $user->getDefaultEmail();
      $data = [
            'withTemplate' => (isset($options['withtemplate']) && $options['withtemplate'] ? "*" : ""),
            'canUpdate'    => (!$this->isNewID($ID)) && ($this->canUpdate() > 0) || $this->isNewID($ID),
            'isNewID'      => $this->isNewID($ID),
            'invitation'   => $fields,
            'resendButton' => Html::submit(_x('button', 'Re-send'), array('name' => 'resend')),
      ];
      echo $twig->render('invitation.html', $data);

      if (!$this->isNewID($ID)) {
         $options['canedit'] = false;
      }
      $this->showFormButtons($options);
   }

   protected function showMassiveActionInviteUser() {
      $twig = plugin_flyvemdm_getTemplateEngine();
      $data = [
            'inviteButton' => Html::submit(_x('button', 'Post'), array('name' => 'massiveaction'))
      ];
      echo $twig->render('mass_invitation.html', $data);
   }

   /**
    *
    * @param MassiveAction $ma
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
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    */
   public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids) {
      switch ($ma->getAction()) {
         case 'InviteUser':
            if ($item->getType() == User::class) {
               // find the profile ID of the service account (demo plugin)
               $config = Config::getConfigurationValues('flyvemdmdemo', ['service_profiles_id']);
               if (isset($config['service_profiles_id'])) {
                  $profile = new Profile();
                  $profile->getFromDB($config['service_profiles_id']);
                  $profile_user = new Profile_User();
               }
               foreach ($ids as $id) {
                  $item->getFromDB($id);
                  $reject = false;

                  // Do not invite service account users (demo mode)
                  if (isset($config['service_profiles_id'])) {
                     if ($profile_user->getFromDBForItems($item, $profile) !== false) {

                        $reject = true;
                     }
                  }

                  // Do not invite users without a default email address
                  $useremail = new UserEmail();
                  $emailAddress = $useremail->getDefaultForUser($id);
                  if (empty($emailAddress)) {
                     $reject = true;
                  }

                  if ($reject) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  } else {
                     $invitation = new PluginFlyvemdmInvitation();
                     $success = $invitation->add([
                        '_useremails'  => $emailAddress,
                        'entities_id'  => $_SESSION['glpiactive_entity'],
                     ]);
                     if (!$success) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     }
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
            }
      }

      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }
}
