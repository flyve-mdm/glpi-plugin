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
class PluginFlyvemdmProfile extends Profile {

   const RIGHT_FLYVEMDM_USE = 128;

   /**
    * @var string $rightname name of the right in DB
    */
   static $rightname = 'flyvemdm:flyvemdm';

   public static function purgeProfiles(Profile $prof) {
      $plugprof = new self();
      $plugprof->deleteByCriteria(array('profiles_id' => $prof->getField("id")));
   }

   /**
    * {@inheritDoc}
    * @see Profile::showForm()
    */
   public function showForm($ID, $options = array()) {
      global $LANG;

      if (!Profile::canView()) {
         return false;
      }
      $canedit = Profile::canUpdate();
      $profile    = new Profile();
      if ($ID) {
         //$this->getFromDBByProfile($ID);
         $profile->getFromDB($ID);
      }
      if ($canedit) {
         echo "<form action='".$profile->getFormURL()."' method='post'>";
      }

      $rights = $this->getGeneralRights();
      $profile->displayRightsChoiceMatrix($rights, array('canedit'       => $canedit,
                                                         'default_class' => 'tab_bg_2',
                                                         'title' => __('General')
      ));

      $rights = $this->getAssetsRights();
      $profile->displayRightsChoiceMatrix($rights, array('canedit'       => $canedit,
                                                         'default_class' => 'tab_bg_2',
                                                         'title' => __('Assets')
       ));

      if ($canedit) {
         echo "<div class='center'>";
         echo "<input type='hidden' name='id' value=".$ID.">";
         echo "<input type='submit' name='update' value=\""._sx('button', 'Save')."\" class='submit'>";
         echo "</div>";
      }
      Html::closeForm();
      $this->showLegend();
   }

   /**
    * {@inheritDoc}
    * @see Profile::getTabNameForItem()
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      if ($item->getType() == 'Profile') {
         return __('Flyve MDM', 'flyvemdm');
      }
      return '';
   }

   /**
    * @deprecated
    * @param CommonGLPI $item
    * @param number $tabnum
    * @param number $withtemplate
    * @return boolean
    */
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == 'Profile') {
         $profile = new self();
         $profile->showForm($item->getField('id'));
      }
      return true;
   }

   /**
    * Get rights matrix for plugin
    * @return array:array:string rights matrix
    */
   public function getGeneralRights() {
      $rights = array(
         array('itemtype'  => 'PluginFlyvemdmProfile',
             'label'       => parent::getTypeName(2),
             'field'       => self::$rightname,
             'rights'      => array(self::RIGHT_FLYVEMDM_USE => __('Use Flyve MDM'))
         ),
         array('itemtype'  => 'PluginFlyvemdmEntityconfig',
             'label'       => PluginFlyvemdmEntityconfig::getTypeName(2),
             'field'       => PluginFlyvemdmEntityconfig::$rightname,
             'rights'      => array(
                   READ                                                             => __('Read'),
                   PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_DEVICE_COUNT_LIMIT    => __('Write device limit'),
                   PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_APP_DOWNLOAD_URL      => __('Set agent download URL'),
                   PluginFlyvemdmEntityconfig::RIGHT_FLYVEMDM_INVITATION_TOKEN_LIFE => __('Set invitation tiken lifetime'),
             )
         ),
         array('itemtype'  => 'PluginFlyvemdmInvitationLog',
               'label'       => PluginFlyvemdmInvitationLog::getTypeName(2),
               'field'       => PluginFlyvemdmInvitationLog::$rightname,
               'rights'      => array(
                     READ                                                          => __('Read'),
               )
         )

      );
      return $rights;
   }

   /**
    * Get rights matrix for plugin's assets
    * @return array:array:string rights matrix
    */
   public function getAssetsRights() {
      $itemtypes = array(
         //'PluginFlyvemdmEntityconfig',
         'PluginFlyvemdmAgent',
         'PluginFlyvemdmInvitation',
         'PluginFlyvemdmFleet',
         'PluginFlyvemdmPackage',
         'PluginFlyvemdmFile',
         'PluginFlyvemdmGeolocation',
         'PluginFlyvemdmPolicy',
         'PluginFlyvemdmPolicyCategory',
         'PluginFlyvemdmWellknownpath',
      );

      $rights = array();
      foreach ($itemtypes as $itemtype) {
         $rights[] = [
            'itemtype'  => $itemtype,
            'label'     => $itemtype::getTypeName(2),
            'field'     => $itemtype::$rightname
         ];
      }

      return $rights;
   }

   /**
    * Callback when a user logins or switch profile
    */
   public static function changeProfile() {
      $rights = ProfileRight::getProfileRights($_SESSION['glpiactiveprofile']['id'], array(
            self::$rightname
      ));

      $config = Config::getConfigurationValues('flyvemdm', array('guest_profiles_id'));
      if (isset($config['guest_profiles_id'])) {
         $_SESSION['plugin_flyvemdm_guest_profiles_id'] = $config['guest_profiles_id'];
      } else {
         $_SESSION['plugin_flyvemdm_guest_profiles_id'] = '';
      }
   }
}
