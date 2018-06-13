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
namespace Flyvemdm\Tests;

use Glpi\Tests\CommonTestCase as GlpiCommonTestCase;

class CommonTestCase extends GlpiCommonTestCase {

   /**
    * Try to enroll an device by creating an agent. If the enrollment fails
    * the agent returned will not contain an ID. To ensore the enrollment succeeded
    * use isNewItem() method on the returned object.
    *
    * @param \User $user
    * @param array $input enrollment data for agent creation
    * @return \PluginFlyvemdmAgent
    */
   protected function enrollFromInvitation(\User $user, array $input) {
      // Close current session
      $this->terminateSession();
      $this->restartSession();
      $this->setupGLPIFramework();

      // login as invited user
      $_REQUEST['user_token'] = \User::getToken($user->getID(), 'api_token');
      $this->boolean($this->login('', '', false))->isTrue();
      $this->setupGLPIFramework();
      unset($_REQUEST['user_token']);

      // Try to enroll
      $agent = new \PluginFlyvemdmAgent();
      $agent->add($input);

      return $agent;
   }

   /**
    * Create a new invitation
    *
    * @param string $guestEmail
    * @return \PluginFlyvemdmInvitation
    */
   protected function createInvitation($guestEmail) {
      $invitation = new \PluginFlyvemdmInvitation();
      $invitation->add([
         'entities_id' => $_SESSION['glpiactive_entity'],
         '_useremails' => $guestEmail,
      ]);
      $this->boolean($invitation->isNewItem())->isFalse();

      return $invitation;
   }

   /**
    * @param string $userIdField
    * @return array
    */
   protected function createUserInvitation($userIdField) {
      // Create an invitation
      $serial = $this->getUniqueString();
      $guestEmail = $this->getUniqueEmail();
      $invitation = $this->createInvitation($guestEmail);
      $user = new \User();
      $user->getFromDB($invitation->getField($userIdField));

      return [$user, $serial, $guestEmail, $invitation];
   }

   /**
    * @param \User $user object
    * @param string $guestEmail
    * @param string|null $serial if null the value is not used
    * @param string $invitationToken
    * @param string $mdmType
    * @param string|null $version if null the value is not used
    * @param string $inventory xml
    * @return \PluginFlyvemdmAgent
    */
   protected function agentFromInvitation(
   $user,
   $guestEmail,
   $serial,
   $invitationToken,
   $mdmType = 'android',
   $version = '',
   $inventory = null
   ) {
      //Version change
      $finalVersion = \PluginFlyvemdmAgent::MINIMUM_ANDROID_VERSION . '.0';
      if ($version) {
         $finalVersion = $version;
      }
      if (null === $version) {
         $finalVersion = null;
      }

      $finalInventory = (null !== $inventory) ? $inventory : self::AgentXmlInventory($serial);

      $input = [
         'entities_id'       => $_SESSION['glpiactive_entity'],
         '_email'            => $guestEmail,
         '_invitation_token' => $invitationToken,
         'csr'               => '',
         'firstname'         => 'John',
         'lastname'          => 'Doe',
         'type'              => $mdmType,
         'inventory'         => $finalInventory,
      ];

      if ($serial) {
         $input['_serial'] = $serial;
      }
      if ($finalVersion) {
         $input['version'] = $finalVersion;
      }

      return $this->enrollFromInvitation($user, $input);
   }

   /**
    * @return object PluginFlyvemdmFleet mocked
    *
    * @param array $input input data
    */
   protected function createFleet($input) {
      $fleet = $this->newMockInstance(\PluginFlyvemdmFleet::class, '\MyMock');
      $fleet->getMockController()->post_addItem = function () {};
      $fleetId = $fleet->add($input);
      $this->boolean($fleet->isNewItem())->isFalse();

      $fleet = new \PluginFlyvemdmFleet();
      $fleet->getFromDB($fleetId);

      return $fleet;
   }

   public function createAgent($input) {
      $guestEmail = $this->getUniqueEmail();
      $invitation = $this->createInvitation($guestEmail);
      $this->variable($invitation)->isNotNull();
      $user = new \User();
      $user->getFromDB($invitation->getField(\User::getForeignKeyField()));
      $serial = $this->getUniqueString();
      $input = [
         '_email'            => $guestEmail,
         '_invitation_token' => $invitation->getField('invitation_token'),
         '_serial'           => $serial,
         'csr'               => '',
         'firstname'         => 'John',
         'lastname'          => 'Doe',
         'version'           => \PluginFlyvemdmAgent::MINIMUM_ANDROID_VERSION . '.0',
         'type'              => 'android',
         'inventory'         => CommonTestCase::AgentXmlInventory($serial),
      ] + $input;
      $agent = $this->enrollFromInvitation($user, $input);
      $this->boolean($agent->isNewItem())
         ->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      return $agent;
   }

   /**
    * Create a file directly on DB and return the object and the filename generated
    *
    * @param $entityId
    * @param null $userFilename
    * @param int $version
    * @return \PluginFlyvemdmFile
    */
   function createDummyFile($entityId, $userFilename = null, $version = 1) {
      global $DB;

      // Create an file (directly in DB)
      $fileName = ((null !== $userFilename) ? $userFilename : $this->getUniqueString());
      $destination = $entityId . '/dummy_file_' . $fileName;
      if (!is_dir($directory = FLYVEMDM_FILE_PATH . "/" . $entityId)) {
         @mkdir($directory);
      }
      $fileSize = file_put_contents(FLYVEMDM_FILE_PATH . '/' . $destination, 'dummy');
      $this->integer($fileSize)->isGreaterThan(0);
      $fileTable = \PluginFlyvemdmFile::getTable();
      $query = "INSERT INTO $fileTable (
         `name`,
         `source`,
         `entities_id`,
         `version`
      ) VALUES (
         '$fileName',
         '$destination',
         '$entityId',
         '$version'
      )";

      try {
         $DB->query($query);
         $mysqlError = $DB->error();
         $flyvemdmFile = new \PluginFlyvemdmFile();
         $flyvemdmFile->getFromDBByCrit(['name' => $fileName]);
         $this->boolean($flyvemdmFile->isNewItem())->isFalse($mysqlError);
         return $flyvemdmFile;
      } catch (\Exception $e) {
         echo $e->getMessage();
         $this->stop();
      }
   }

   /**
    * Fake XML data for inventory
    * @param $serial
    * @param string $macAddress
    * @param string $deviceId
    * @param string $uuid
    * @return string
    */
   public static function AgentXmlInventory($serial, $macAddress = '', $deviceId = '', $uuid = '') {
      $uuid = ($uuid) ? $uuid : '1d24931052f35d92';
      $macAddress = ($macAddress) ? $macAddress : '02:00:00:00:00:00';
      $deviceId = ($deviceId) ? $deviceId : $serial . "_" . $macAddress;
      $xml = "<?xml version='1.0' encoding='utf-8' standalone='yes'?>
            <REQUEST>
              <QUERY>INVENTORY</QUERY>
              <VERSIONCLIENT>FlyveMDM-Agent_v1.0</VERSIONCLIENT>
              <DEVICEID>" . $deviceId . "</DEVICEID>
              <CONTENT>
                <ACCESSLOG>
                  <LOGDATE>" . date("Y-m-d H:i:s") . "</LOGDATE>
                  <USERID>N/A</USERID>
                </ACCESSLOG>
                <ACCOUNTINFO>
                  <KEYNAME>TAG</KEYNAME>
                  <KEYVALUE/>
                </ACCOUNTINFO>
                <HARDWARE>
                  <DATELASTLOGGEDUSER>09/11/17</DATELASTLOGGEDUSER>
                  <LASTLOGGEDUSER>jenkins</LASTLOGGEDUSER>
                  <NAME>" . $serial . "</NAME>
                  <OSNAME>Android</OSNAME>
                  <OSVERSION>6.0</OSVERSION>
                  <ARCHNAME>aarch64</ARCHNAME>
                  <UUID>" . $uuid . "</UUID>
                  <MEMORY>1961</MEMORY>
                </HARDWARE>
                <BIOS>
                  <BDATE>09/11/17</BDATE>
                  <BMANUFACTURER>bq</BMANUFACTURER>
                  <MMANUFACTURER>bq</MMANUFACTURER>
                  <SMODEL>Aquaris M10 FHD</SMODEL>
                  <SSN>" . $serial . "</SSN>
                </BIOS>
                <MEMORIES>
                  <DESCRIPTION>Memory</DESCRIPTION>
                  <CAPACITY>1961</CAPACITY>
                </MEMORIES>
                <INPUTS>
                  <CAPTION>Touch Screen</CAPTION>
                  <DESCRIPTION>Touch Screen</DESCRIPTION>
                  <TYPE>FINGER</TYPE>
                </INPUTS>
                <SENSORS>
                  <NAME>ACCELEROMETER</NAME>
                  <NAME>MTK</NAME>
                  <TYPE>ACCELEROMETER</TYPE>
                  <POWER>0.13</POWER>
                  <VERSION>3</VERSION>
                </SENSORS>
                <SENSORS>
                  <NAME>LIGHT</NAME>
                  <NAME>MTK</NAME>
                  <TYPE>Unknow</TYPE>
                  <POWER>0.13</POWER>
                  <VERSION>1</VERSION>
                </SENSORS>
                <SENSORS>
                  <NAME>ORIENTATION</NAME>
                  <NAME>MTK</NAME>
                  <TYPE>Unknow</TYPE>
                  <POWER>0.25</POWER>
                  <VERSION>3</VERSION>
                </SENSORS>
                <SENSORS>
                  <NAME>MAGNETOMETER</NAME>
                  <NAME>MTK</NAME>
                  <TYPE>MAGNETIC FIELD</TYPE>
                  <POWER>0.25</POWER>
                  <VERSION>3</VERSION>
                </SENSORS>
                <DRIVES>
                  <VOLUMN>/system</VOLUMN>
                  <TOTAL>1487</TOTAL>
                  <FREE>72</FREE>
                </DRIVES>
                <DRIVES>
                  <VOLUMN>/storage/emulated/0</VOLUMN>
                  <TOTAL>12529</TOTAL>
                  <FREE>8322</FREE>
                </DRIVES>
                <DRIVES>
                  <VOLUMN>/data</VOLUMN>
                  <TOTAL>12529</TOTAL>
                  <FREE>8322</FREE>
                </DRIVES>
                <DRIVES>
                  <VOLUMN>/cache</VOLUMN>
                  <TOTAL>410</TOTAL>
                  <FREE>410</FREE>
                </DRIVES>
                <CPUS>
                  <NAME>AArch64 Processor rev 3 (aarch64)</NAME>
                  <SPEED>1500</SPEED>
                </CPUS>
                <SIMCARDS>
                  <STATE>SIM_STATE_UNKNOWN</STATE>
                </SIMCARDS>
                <VIDEOS>
                  <RESOLUTION>1920x1128</RESOLUTION>
                </VIDEOS>
                <CAMERAS>
                  <RESOLUTIONS>3264x2448</RESOLUTIONS>
                </CAMERAS>
                <CAMERAS>
                  <RESOLUTIONS>2880x1728</RESOLUTIONS>
                </CAMERAS>
                <NETWORKS>
                  <TYPE>WIFI</TYPE>
                  <MACADDR>" . $macAddress . "</MACADDR>
                  <SPEED>65</SPEED>
                  <BSSID>aa:5b:78:78:52:7e</BSSID>
                  <SSID>aa:5b:78:78:52:7e</SSID>
                  <IPGATEWAY>172.20.10.1</IPGATEWAY>
                  <IPADDRESS>172.20.10.3</IPADDRESS>
                  <IPMASK>0.0.0.0</IPMASK>
                  <IPDHCP>172.20.10.1</IPDHCP>
                </NETWORKS>
                <ENVS>
                  <KEY>SYSTEMSERVERCLASSPATH</KEY>
                  <VAL>/system/framework/services.jar:/system/framework/ethernet-service.jar:/system/framework/wifi-service.jar</VAL>
                </ENVS>
                <ENVS>
                  <KEY>ANDROID_SOCKET_zygote</KEY>
                  <VAL>11</VAL>
                </ENVS>
                <ENVS>
                  <KEY>ANDROID_DATA</KEY>
                  <VAL>/data</VAL>
                </ENVS>
                <ENVS>
                  <KEY>PATH</KEY>
                  <VAL>/sbin:/vendor/bin:/system/sbin:/system/bin:/system/xbin</VAL>
                </ENVS>
                <ENVS>
                  <KEY>ANDROID_ASSETS</KEY>
                  <VAL>/system/app</VAL>
                </ENVS>
                <ENVS>
                  <KEY>ANDROID_ROOT</KEY>
                  <VAL>/system</VAL>
                </ENVS>
                <ENVS>
                  <KEY>ASEC_MOUNTPOINT</KEY>
                  <VAL>/mnt/asec</VAL>
                </ENVS>
                <ENVS>
                  <KEY>LD_PRELOAD</KEY>
                  <VAL>libdirect-coredump.so</VAL>
                </ENVS>
                <ENVS>
                  <KEY>ANDROID_BOOTLOGO</KEY>
                  <VAL>1</VAL>
                </ENVS>
                <ENVS>
                  <KEY>BOOTCLASSPATH</KEY>
                  <VAL>/system/framework/core-libart.jar:/system/framework/conscrypt.jar:/system/framework/okhttp.jar:/system/framework/core-junit.jar:/system/framework/bouncycastle.jar:/system/framework/ext.jar:/system/framework/framework.jar:/system/framework/telephony-common.jar:/system/framework/voip-common.jar:/system/framework/ims-common.jar:/system/framework/apache-xml.jar:/system/framework/org.apache.http.legacy.boot.jar:/system/framework/mediatek-common.jar:/system/framework/mediatek-framework.jar:/system/framework/mediatek-telephony-common.jar:/system/framework/dolby_ds2.jar:/system/framework/dolby_ds1.jar</VAL>
                </ENVS>
                <ENVS>
                  <KEY>ANDROID_PROPERTY_WORKSPACE</KEY>
                  <VAL>9,0</VAL>
                </ENVS>
                <ENVS>
                  <KEY>EXTERNAL_STORAGE</KEY>
                  <VAL>/sdcard</VAL>
                </ENVS>
                <ENVS>
                  <KEY>ANDROID_STORAGE</KEY>
                  <VAL>/storage</VAL>
                </ENVS>
                <JVMS>
                  <NAME>Dalvik</NAME>
                  <LANGUAGE>en_GB</LANGUAGE>
                  <VENDOR>The Android Project</VENDOR>
                  <RUNTIME>0.9</RUNTIME>
                  <HOME>/system</HOME>
                  <VERSION>2.1.0</VERSION>
                  <CLASSPATH>.</CLASSPATH>
                </JVMS>
                <BATTERIES>
                  <CHEMISTRY>Li-ion</CHEMISTRY>
                  <TEMPERATURE>23.0c</TEMPERATURE>
                  <VOLTAGE>3.745V</VOLTAGE>
                  <LEVEL>60%</LEVEL>
                  <HEALTH>Good</HEALTH>
                  <STATUS>Not charging</STATUS>
                </BATTERIES>
                <SOFTWARES>
                  <NAME><![CDATA[Bluetooth Pairing Utility]]></NAME>
                  <COMMENTS>com.symbol.btapp</COMMENTS>
                  <VERSION>3.10</VERSION>
                  <FILESIZE>0</FILESIZE>
                  <FROM>Android</FROM>
                  <INSTALLDATE>1519977807000</INSTALLDATE>
                </SOFTWARES>
                <SOFTWARES>
                  <NAME><![CDATA[com.android.cts.priv.ctsshim]]></NAME>
                  <COMMENTS>com.android.cts.priv.ctsshim</COMMENTS>
                  <VERSION>7.0-2996264</VERSION>
                  <FILESIZE>0</FILESIZE>
                  <FROM>Android</FROM>
                  <INSTALLDATE>1519977807000</INSTALLDATE>
                </SOFTWARES>
                <SOFTWARES>
                  <NAME><![CDATA[YouTube]]></NAME>
                  <COMMENTS>com.google.android.youtube</COMMENTS>
                  <VERSION>12.43.52</VERSION>
                  <FILESIZE>0</FILESIZE>
                  <FROM>Android</FROM>
                  <INSTALLDATE>1519977807000</INSTALLDATE>
                </SOFTWARES>
                <SOFTWARES>
                  <NAME><![CDATA[SampleExtAuthService]]></NAME>
                  <COMMENTS>com.qualcomm.qti.auth.sampleextauthservice</COMMENTS>
                  <VERSION>1.0</VERSION>
                  <FILESIZE>0</FILESIZE>
                  <FROM>Android</FROM>
                  <INSTALLDATE>1519977807000</INSTALLDATE>
                </SOFTWARES>
                <SOFTWARES>
                  <NAME><![CDATA[Kingdoms &amp; Lords]]></NAME>
                  <COMMENTS>com.gameloft.android.GloftKLMF</COMMENTS>
                  <VERSION>1.0.0</VERSION>
                  <FILESIZE>0</FILESIZE>
                  <FROM>Android</FROM>
                  <INSTALLDATE>1519977807000</INSTALLDATE>
                </SOFTWARES>
              </CONTENT>
            </REQUEST>";
      return base64_encode($xml);
   }

   /**
    * Create an application (directly in DB) because we are not uploading any file
    * @return \PluginFlyvemdmPackage
    */
   protected function createDummyPackage($entityId, $filename = null, $version = '1.0.5') {
      global $DB;

      // Create an file (directly in DB)
      $uniqueString = ((null !== $filename) ? $filename : $this->getUniqueString());
      $dumbPackageName = 'com.domain.' . $uniqueString . '.application';
      $destination = 'flyvemdm/package/' .$entityId . '/123456789_application_' . $uniqueString . '.apk';
      if (!is_dir($directory = FLYVEMDM_PACKAGE_PATH . "/" . $entityId)) {
         @mkdir($directory);
      }
      $fileSize = file_put_contents(GLPI_PLUGIN_DOC_DIR . '/' . $destination, 'dummy');
      $this->integer($fileSize)->isGreaterThan(0);
      $packageTable = \PluginFlyvemdmPackage::getTable();
      $query = "INSERT INTO $packageTable (
         `package_name`,
         `alias`,
         `version`,
         `filename`,
         `entities_id`,
         `dl_filename`,
         `icon`
      ) VALUES (
         '$dumbPackageName',
         'application',
         '$version',
         '$destination',
         '$entityId',
         'application_" . $uniqueString . ".apk',
         ''
      )";

      try {
         $DB->query($query);
         $mysqlError = $DB->error();
         $flyvemdmPackage = new \PluginFlyvemdmPackage();
         $flyvemdmPackage->getFromDBByCrit(['package_name' => $dumbPackageName]);
         $this->boolean($flyvemdmPackage->isNewItem())->isFalse($mysqlError);
         return $flyvemdmPackage;
      } catch (\Exception $e) {
         echo $e->getMessage();
         $this->stop();
      }
   }

   /**
    * @return array
    */
   public static function commandList() {
      return [
         'Command/Subscribe',
         'Command/Ping',
         'Command/Geolocate',
         'Command/Inventory',
         'Command/Lock',
         'Command/Wipe',
         'Command/Unenroll',
      ];
   }

   /**
    * @return array
    */
   public static function policyList() {
      return [
         'Policy/passwordEnabled',
         'Policy/passwordMinLength',
         'Policy/passwordQuality',
         'Policy/passwordMinLetters',
         'Policy/passwordMinLowerCase',
         'Policy/passwordMinNonLetter',
         'Policy/passwordMinNumeric',
         'Policy/passwordMinSymbols',
         'Policy/passwordMinUpperCase',
         'Policy/maximumFailedPasswordsForWipe',
         'Policy/maximumTimeToLock',
         'Policy/storageEncryption',
         'Policy/disableCamera',
         'Policy/deployApp',
         'Policy/removeApp',
         'Policy/deployFile',
         'Policy/removeFile',
         'Policy/disableWifi',
         'Policy/disableBluetooth',
         'Policy/disableRoaming',
         'Policy/disableGPS',
         'Policy/disableUsbMtp',
         'Policy/disableUsbPtp',
         'Policy/disableUsbAdb',
         'Policy/disableMobileLine',
         'Policy/disableNfc',
         'Policy/disableHostpotTethering',
         'Policy/disableAirplaneMode',
         'Policy/disableStatusBar',
         'Policy/disableScreenCapture',
         'Policy/disableSpeakerphone',
         'Policy/disableUnknownAppSources',
         'Policy/disableStreamMusic',
         'Policy/disableStreamRing',
         'Policy/disableStreamAlarm',
         'Policy/disableStreamNotification',
         'Policy/disableStreamAccessibility',
         'Policy/disableStreamDTMF',
         'Policy/disableStreamSystem',
         'Policy/defaultStreamType',
      ];
   }
}
