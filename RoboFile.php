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

   public function test() {
      $this->testUnit();
      $this->testCS();
   }

   public function testUnit() {
      $this->_exec(__DIR__ . '/vendor/bin/phpunit --verbose');
   }

   public function testCS() {
      $this->_exec(__DIR__ . '/vendor/bin/phpcs -p --standard=vendor/glpi-project/coding-standard/GlpiStandard/ *.php install/ inc/ front/ ajax/ tests/');
   }

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
}

