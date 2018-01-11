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
 * @author    Thierry Bugier
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlyvemdmGraph extends CommonDBTM
{

   /**
    * Displays the graphic of the invitations
    * @return string HTML snippet for Pie graph
    */
   public function showInvitationsGraph() {
      $out = '';

      $dbUtils = new DbUtils();

      $pendingCount = $dbUtils->countElementsInTableForMyEntities(
         PluginFlyvemdmInvitation::getTable(),
         "`status` = 'pending'"
      );

      $doneCount = $dbUtils->countElementsInTableForMyEntities(
         PluginFlyvemdmInvitation::getTable(),
         "`status` = 'done'"
      );

      if (($pendingCount + $doneCount) == 0) {
         return '';
      }

      $stat = new Stat();
      $out = $stat->displayPieGraph(
            __('Invitations', 'flyvemdm'),
            [
               __('Done', 'flyvemdm'),
               __('Pending', 'flyvemdm')
            ],
            [
               [
                  'name'   => __('Done', 'flyvemdm'),
                  'data'   => $doneCount,
               ],
               [
                  'name'   => __('Pending', 'flyvemdm'),
                  'data'   => $pendingCount,
               ],
            ],
            false
      );

      return $out;
   }

   /**
    * Displays the devices per operating system version
    * @return string a HTML with the devices according their operating system version
    */
   public function showDevicesPerOSVersion() {
      global $DB;

      $out = '';

      $config = Config::getConfigurationValues('flyvemdm', ['computertypes_id']);
      $computerTypeId = $config['computertypes_id'];
      $computerTable = Computer::getTable();
      $itemOperatingSystemTable = Item_OperatingSystem::getTable();
      $operatingSystemTable = OperatingSystem::getTable();
      $operatingSystemVersionTable = OperatingSystemVersion::getTable();
      $entityRestrict = getEntitiesRestrictRequest(" AND ", $computerTable);
      $query = "SELECT
                  `os`.`name` AS `operatingsystem`,
                  `osv`.`name` AS `version`,
                  COUNT(*) AS `cpt`
                FROM `$computerTable`
                LEFT JOIN `$itemOperatingSystemTable` AS `i_os`
                  ON (`i_os`.itemtype = 'Computer' AND `i_os`.`items_id` = `$computerTable`.`id`)
                LEFT JOIN `$operatingSystemTable` AS `os`
                  ON (`os`.`id` = `i_os`.`operatingsystems_id`)
                LEFT JOIN `$operatingSystemVersionTable` AS `osv`
                  ON (`osv`.`id` = `i_os`.`operatingsystemversions_id`)
                WHERE `$computerTable`.`computertypes_id` = '$computerTypeId' $entityRestrict
                GROUP BY `operatingsystem`, `version`";
      $result = $DB->query($query);
      if ($result && $DB->numrows($result) > 0) {
         $osNames = [];
         $versionNames = [];
         $versionPerOS = [];
         while ($row = $DB->fetch_assoc($result)) {
            $osNames[$row['operatingsystem']] = $row['operatingsystem'];
            $versionNames[$row['version']] = $row['version'];
            $versionPerOS[$row['version']][$row['operatingsystem']] = $row['cpt'];
         }
         foreach ($osNames as $osName) {
            foreach ($versionNames as $versionName) {
               if (!isset($versionPerOS[$versionName][$osName])) {
                  $versionPerOS[$versionName][$osName] = '0';
               }
            }
         }
         ksort($versionPerOS);
         $series = [];
         foreach ($versionPerOS as $osName => $serie) {
            ksort($serie);
            $series[] = [
               'name'   => $osName,
               'data'   => array_values($serie),
            ];
         }
         $out = $this->displayStackedBarGraph(
            __('Devices per operating system version', 'flyvemdm'),
            array_values($osNames),
            $series,
            [
               'width'     => '100%',
            ],
            false
         );
      }

      return $out;
   }

   /**
    * Display stacked bar graph
    *
    * @param string   $title  Graph title
    * @param string[] $labels Labels to display
    * @param array    $series Series data. An array of the form:
    *                 [
    *                    ['name' => 'a name', 'data' => []],
    *                    ['name' => 'another name', 'data' => []]
    *                 ]
    * @param string[] $options  array of options
    * @param boolean  $display  Whether to display directly; defauts to true
    *
    * @return void
    */
   public function displayStackedBarGraph($title, $labels, $series, $options = null, $display = true) {
      $param = [
         'width'   => 900,
         'height'  => 300,
         'tooltip' => true,
         'legend'  => true,
         'animate' => true
      ];

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $param[$key] = $val;
         }
      }

      $slug = str_replace('-', '_', Toolbox::slugify($title));
      $this->checkEmptyLabels($labels);
      $out = "<h2 class='center'>$title</h2>";
      $out .= "<div id='$slug' class='chart'></div>";
      $out .= "<script type='text/javascript'>
                  $(function() {
                     var chart_$slug = new Chartist.Bar('#$slug', {
                        labels: ['" . implode('\', \'', Toolbox::addslashes_deep($labels))  . "'],
                        series: [";

      $first = true;
      foreach ($series as $serie) {
         if ($first === true) {
            $first = false;
         } else {
            $out .= ",\n";
         }
         if (isset($serie['name'])) {
            $out .= "{'name': '{$serie['name']}', 'data': [" . implode(', ', $serie['data']) . "]}";
         } else {
            $out .= "[" . implode(', ', $serie['data']) . "]";
         }
      }

      $out .= "
                        ]
                     }, {
                        low: 0,
                        showArea: true,
                        width: '{$param['width']}',
                        height: '{$param['height']}',
                        fullWidth: true,
                        lineSmooth: Chartist.Interpolation.simple({
                           divisor: 10,
                           fillHoles: false
                        }),
                        stackBars: true,
                        axisX: {
                           labelOffset: {
                              x: -" . mb_strlen($labels[0]) * 7  . "
                           }
                        }";

      if ($param['legend'] === true || $param['tooltip'] === true) {
         $out .= ", plugins: [";
         if ($param['legend'] === true) {
            $out .= "Chartist.plugins.legend()";
         }
         if ($param['tooltip'] === true) {
            $out .= ($param['legend'] === true ? ',' : '') . "Chartist.plugins.tooltip()";
         }
         $out .= "]";
      }

      $out .= "});";

      if ($param['animate'] === true) {
         $out .= "
                     chart_$slug.on('draw', function(data) {
                        if(data.type === 'bar') {
                           data.element.animate({
                              y2: {
                                 begin: 300 * data.index,
                                 dur: 500,
                                 from: data.y1,
                                 to: data.y2,
                                 easing: Chartist.Svg.Easing.easeOutQuint
                              }
                           });
                        }
                     });
                  });";
      }
      $out .= "</script>";

      if ($display) {
         echo $out;
         return;
      }
      return $out;
   }

   /**
    * Check and replace empty labels (picked from GLPI 9.2)
    *
    * @param array $labels Labels
    *
    * @return void
    */
   private function checkEmptyLabels(&$labels) {
      foreach ($labels as &$label) {
         if (empty($label)) {
            $label = '-';
         }
      }
   }
}