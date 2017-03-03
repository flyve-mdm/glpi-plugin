<?php
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