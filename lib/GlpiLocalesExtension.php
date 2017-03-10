<?php
/*
 LICENSE

This file is part of the flyvemdm plugin.

Order plugin is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

Order plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI; along with flyvemdm. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
@package   flyvemdm
@author    the flyvemdm plugin team
@copyright Copyright (c) 2015 flyvemdm plugin team
@license   GPLv2+ http://www.gnu.org/licenses/gpl.txt
@link      https://github.com/teclib/flyvemdm
@link      http://www.glpi-project.org/
@since     0.1.0
----------------------------------------------------------------------
*/

class GlpiLocalesExtension extends \Twig_Extension
{
   /**
    * Sets aliases for functions
    *
    * @see Twig_Extension::getFunctions()
    * @return array
    */
   public function getFunctions() {
      return array(
            new \Twig_SimpleFunction('__', '__'),
            new \Twig_SimpleFunction('__s', '__s'),
            new \Twig_SimpleFunction('_e', '_e'),
            new \Twig_SimpleFunction('_ex', '_ex'),
            new \Twig_SimpleFunction('_n', '_n'),
            new \Twig_SimpleFunction('_nx', '_nx'),
            new \Twig_SimpleFunction('_sn', '_sn'),
            new \Twig_SimpleFunction('_sx', '_sx'),
            new \Twig_SimpleFunction('_x', '_x'),
      );
   }

   /**
    * Returns the name of the extension.
    *
    * @return string The extension name
    *
    * @see Twig_ExtensionInterface::getName()
    */
   public function getName() {
      return 'glpi_locales_extension';
   }
}