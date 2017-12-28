<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2017 by the FusionInventory Development Team.
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
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace Flyvemdm\Tests\Src;


class TestingCommonTools {

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
              </CONTENT>
            </REQUEST>";
      return $xml;
   }
}