<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

require_once 'vendor/autoload.php';

class RoboFile extends Glpi\Tools\RoboFile
{
   protected static $banned = [
         'dist',
         'vendor',
         '.git',
         '.gitignore',
         '.gitattributes',
         '.github',
         '.tx',
         '.settings',
         '.project',
         '.buildpath',
         'tools',
         'tests',
         'screenshot*.png',
         'RoboFile*.php',
         'plugin.xml',
         'phpunit.xml.*',
         '.travis.yml',
         'save.sql',
   ];

   public function __construct() {
      $this->csignore = array_merge($this->csignore, ['/lib/']);
   }

   protected function getPluginPath() {
      return __DIR__;
   }

   protected function getPluginName() {
      return basename($this->getPluginPath());
   }

   protected function getVersion() {
      $setupFile = __DIR__ . "/setup.php";
      $setupContent = file_get_contents($setupFile);
      $pluginName = $this->getPluginName();
      $constantName = "PLUGIN_" . strtoupper($this->getPluginName()) . "_VERSION";
      $pattern = "#^define\('$constantName', '([^']*)'\);$#m";
      preg_match($pattern, $setupContent, $matches);
      if (isset($matches[1])) {
         return $matches[1];
      }
      return null;
   }

   protected function getBannedFiles() {
      return static::$banned;
   }

   protected function updateJsonFile($filename) {
      // get Package JSON
      $filename = __DIR__ . "/$filename";
      $jsonContent = file_get_contents($filename);
      $jsonContent = json_decode($jsonContent, true);

      // update version
      $version = $this->getVersion();
      if (empty($version)) {
         echo "Version not found in setup.php\n";
         return;
      }
      $jsonContent['version'] = $version;
      file_put_contents($filename, json_encode($jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
   }

   protected function sourceUpdatePackageJson() {
       $this->updateJsonFile('package.json');
   }

   protected function sourceUpdateComposerJson() {
      $this->updateJsonFile('composer.json');
   }

   /**
    * Run all tests over the project
    *
    * @return void
    */
   public function test() {
      $this->testUnit();
      $this->testCS();
   }

   /**
    * Run phpunit over the project
    *
    * @return void
    */
   public function testUnit() {
      $this->_exec(__DIR__ . '/vendor/bin/phpunit --verbose');
   }

   /**
    * run phpcs over the project
    *
    * @return void
    */
   public function testCS() {
      $this->_exec(__DIR__ . '/vendor/bin/phpcs -p --standard=vendor/glpi-project/coding-standard/GlpiStandard/ *.php install/ inc/ front/ ajax/ tests/');
   }

   /**
    *
    * Build an resistribuable archive
    *
    * @return void
    */
   public function archiveBuild() {
       // update version in package.json
       $this->sourceUpdatePackageJson();
       $this->sourceUpdateComposerJson();

       // Build archive
       $version = $this->getVersion();
       $pluginName = $this->getPluginName();
       $pluginPath = $this->getPluginPath();
       $targetFile = __DIR__ . "/dist/glpi-" . $this->getPluginName() . "tar.bz2";
       $targetFile = $this->getPluginPath() . "/dist/glpi-" . $this->getPluginName() . "-" . $version;
       $exclude = "--exclude '" . implode("' --exclude '", $this->getBannedFiles()) ."'";
       // each entry of banned path must match. If a path fails to match something, the archive will not build
       @mkdir($this->getPluginPath() . "/dist");
       $this->_exec("tar -cjf $targetFile.tar.bz2 --directory '$pluginPath' --transform='s/^\./$pluginName/' $exclude .");
   }

   /**
    * Extract translatable strings
    *
    * @return void
    */
   public function localesExtract() {
      // extract locales from twig templates
      $tplDir = __DIR__ . "/tpl";
      $tmpDir = '/tmp/twig-cache/';

      $loader = new Twig_Loader_Filesystem($tplDir);

      // force auto-reload to always have the latest version of the template
      $twig = new Twig_Environment($loader, array(
            'cache' => $tmpDir,
            'auto_reload' => true
      ));
      include __DIR__ . '/lib/GlpiLocalesExtension.php';
      $twig->addExtension(new GlpiLocalesExtension());
      // configure Twig the way you want

      // iterate over all templates
      foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tplDir), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
         // force compilation
         if ($file->isFile()) {
            $twig->loadTemplate(str_replace($tplDir.'/', '', $file));
         }
      }

      // find compiled templates
      foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
         if ($file->isFile()) {
            $compiledTemplates[] = $file;
         }
      }
      $compiledTemplates = implode(' ', $compiledTemplates);

      $potfile = strtolower($this->getPluginName()) . ".pot";
      $phpSources = "*.php ajax/*.php front/*.php inc/*.php install/*.php";
      // extract locales from source code
      $command = "xgettext $phpSources $compiledTemplates -o locales/$potfile -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po";
      $command.= " --keyword=_n:1,2,4t --keyword=__s:1,2t --keyword=__:1,2t --keyword=_e:1,2t --keyword=_x:1c,2,3t --keyword=_ex:1c,2,3t";
      $command.= " --keyword=_sx:1c,2,3t --keyword=_nx:1c,2,3,5t";
      $this->_exec($command);
      return $this;
   }
}

