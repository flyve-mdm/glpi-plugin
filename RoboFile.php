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
 * @author    other author
 * @author    third author
 * @copyright Copyright © 2018 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

require_once 'vendor/autoload.php';

class RoboFile extends Glpi\Tools\RoboFile {

   protected static $banned = [
      'dist/',
      'vendor/',
      '.atoum.php',
      '.git',
      '.gitignore',
      '.gitattributes',
      '.github',
      '.tx',
      '.settings/',
      '.project',
      '.buildpath/',
      'github_deploy_key.enc',
      'tools/dev/',
      'tests/',
      'screenshot*.png',
      'RoboFile*.php',
      'plugin.xml',
      'phpunit.xml.*',
      '.travis.yml',
      'save.sql',
   ];

   protected static $toArchive = [
      '*.md',
      '*.js',
      '*.php',
      'composer.*',
      'package.json',
      'tools',
      'scripts',
      'inc',
      'pics',
      'css',
      'tpl',
      'locales',
      'images',
      'install',
      'front',
      'lib',
      'ajax',
   ];

   protected static $tagPrefix = 'v';

   protected static $pluginXmlFile = 'plugin.xml';

   protected $headerTemplate = '';

   /**
    * @return string
    */
   protected function getPluginPath() {
      return __DIR__;
   }

   /**
    * @return string
    */
   protected function getPluginName() {
      return basename($this->getPluginPath());
   }

   /**
    * @return mixed
    * @throws Exception
    */
   protected function getVersion() {
      $currentRev = $this->getCurrentCommitHash();
      $setupContent = $this->getFileFromGit('setup.php', $currentRev);
      $constantName = "PLUGIN_" . strtoupper($this->getPluginName()) . "_VERSION";
      $pattern = "#^define\('$constantName', '([^']*)'\);$#m";
      preg_match($pattern, $setupContent, $matches);
      if (isset($matches[1])) {
         return $matches[1];
      }

      throw new Exception("Could not determine version of the plugin");
   }

   protected function getIsRelease() {
      $currentRev = $this->getCurrentCommitHash();
      $setupContent = $this->getFileFromGit('setup.php', $currentRev);
      $constantName = "PLUGIN_" . strtoupper($this->getPluginName()) . "_IS_OFFICIAL_RELEASE";
      $pattern = "#^define\('$constantName', ([^\)]*)\);$#m";
      preg_match($pattern, $setupContent, $matches);
      if (isset($matches[1])) {
         return $matches[1];
      }
      throw new Exception("Could not determine release status of the plugin");
   }

   /**
    * find last version tag before current version
    */
   protected function getPreviousVersion() {
      $version = $this->getVersion();
      $tags = $this->getAllTags();
      //TODO: find the latest version in tags
   }

   /**
    * @return mixed
    * @throws Exception
    */
   protected function getGLPIMinVersion() {
      $currentRev = $this->getCurrentCommitHash();
      $setupContent = $this->getFileFromGit('setup.php', $currentRev);
      $pluginName = $this->getPluginName();
      $constantName = "PLUGIN_" . strtoupper($this->getPluginName()) . "_GLPI_MIN_VERSION";
      $pattern = "#^define\('$constantName', '([^']*)'\);$#m";
      preg_match($pattern, $setupContent, $matches);
      if (isset($matches[1])) {
         return $matches[1];
      }

      throw new Exception("Could not determine version of the plugin");
   }

   /**
    * Override to change the banned list
    * @return array
    */
   protected function getBannedFiles() {
      return static::$banned;
   }

   /**
    * @param string $filename
    * @param string $version
    * @return bool
    * @throws Exception
    */
   protected function checkJsonFile($filename, $version) {
      $currentRev = $this->getCurrentCommitHash();
      $fileContent = $this->getFileFromGit($filename, $currentRev);
      $jsonContent = json_decode($fileContent, true);
      if (!isset($jsonContent['version'])) {
         throw new Exception("version not defined in $filename");
      }
      if ($jsonContent['version'] != $version) {
         return false;
      }
      return true;
   }

   /**
    *
    * @param string $filename
    * @param string $version
    */
   protected function updateJsonFile($filename, $version) {
      // get Package JSON
      $filename = __DIR__ . "/$filename";
      $jsonContent = file_get_contents($filename);
      $jsonContent = json_decode($jsonContent, true);

      // update version
      if (empty($version)) {
         echo "Version not found in setup.php\n";
         return;
      }
      $jsonContent['version'] = $version;
      file_put_contents($filename,
         json_encode($jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
   }

   /**
    * @param $version
    */
   protected function sourceUpdatePackageJson($version) {
      $this->updateJsonFile('package.json', $version);
   }

   /**
    * Build an redistribuable archive
    *
    * @param string $release 'release' if the archive is a release
    */
   public function archiveBuild($release = 'release') {
      $release = strtolower($release);

      // get Version fron source code
      $version = $this->getVersion();

      // Check the version is semver compliant
      if (!$this->isSemVer($version)) {
         throw new Exception("$version is not semver compliant. See http://semver.org/");
      }

      if (!$this->checkUpgrade('HEAD')) {
         throw new Exception("Bad upgrade code");
      }

      if ($release != 'release') {
         // check the release constant
         if ($this->getIsRelease() === 'true') {
            throw new Exception('The Official release constant must be false');
         }
      } else {
         // check the release constant
         if ($this->getIsRelease() !== 'true') {
            throw new Exception('The Official release constant must be true');
         }

         // check the version is declared in plugin.xml
         $versionTag = $this->getVersionTagFromXML($version);
         if (!is_array($versionTag)) {
            throw new Exception("The version does not exists in the XML file");
         }
      }

      // Update locales
      $this->localesGenerate();
      $pluginName = $this->getPluginName();

      if ($release == 'release') {
         // commit locales update
         $this->taskGitStack()
            ->stopOnFail()
            ->add('locales/*.po')
            ->add('locales/*.mo')
            ->add("locales/$pluginName.pot")
            ->commit('docs(locales): update translations')
            ->run();

         // bump version in package.json
         $this->sourceUpdatePackageJson($version);

         // updte changelog
         $this->updateChangelog();
         $diff = $this->gitDiff(['package.json']);
         $diff = implode("\n", $diff);
         if ($diff != '') {
            $this->taskGitStack()
               ->stopOnFail()
               ->add('package.json')
               ->commit('docs: bump version package.json')
               ->run();
         }
         $this->taskGitStack()
            ->stopOnFail()
            ->add('CHANGELOG.md')
            ->commit('docs(changelog): update changelog')
            ->run();
      }

      $rev = 'HEAD';
      if ($release == 'release') {
         // check a tag exists for the version
         $tag = self::$tagPrefix . $version;
         $this->taskGitStack()
            ->stopOnFail()
            ->tag($tag)
            ->run();

         if (!$this->tagExists($tag)) {
            throw new Exception("The tag $tag does not exists");
         }

         // check the current head matches the tag
         if (!$this->isTagMatchesCurrentCommit(self::$tagPrefix . $version)) {
            throw new Exception("HEAD is not pointing to the tag of the version to build");
         }

         $rev = $tag;
      }

      // Build archive
      $pluginPath = $this->getPluginPath();
      $archiveWorkdir = "$pluginPath/output/dist/archive_workdir";
      $archiveFile = "$pluginPath/output/dist/glpi-$pluginName-$version.tar.bz2";
      $this->taskDeleteDir($archiveWorkdir)->run();
      mkdir($archiveWorkdir, 0777, true);
      $filesToArchive = implode(' ', static::$toArchive);
      $this->_exec("git archive --prefix=$pluginName/ $rev $filesToArchive | tar x -C '$archiveWorkdir' ");
      $this->_exec("composer install --no-dev --working-dir='$archiveWorkdir/$pluginName'");
      $this->taskPack($archiveFile)
         ->addDir("/$pluginName", "$archiveWorkdir/$pluginName")
         ->run();
   }

   /**
    * Generate API documentation in markdown format for Github
    * @throws Exception
    */
   public function documentationBuild() {
      $command1 = 'vendor/bin/phpdoc';
      $command2 = 'vendor/bin/phpdocmd';
      if (!is_readable($command1) || !is_file($command1)) {
         throw new Exception('phpdoc not available. Run composer install');
      }

      if (!is_readable($command2) || !is_file($command2)) {
         throw new Exception('phpdocmd not available. Run composer install');
      }

      $banned = implode(',', self::$banned);
      $dir = __DIR__;
      echo $dir;
      $this->_exec("$command1 --directory '$dir' --cache-folder /tmp/ --target=docs/ --template=xml -i $banned --progressbar --no-interaction");
      $this->_exec("$command2 docs/structure.xml docs/ --lt '%c'");
   }

   /**
    * Extract translatable strings
    *
    * @return void
    */
   public function localesExtract() {
      // extract locales from twig templates
      $tplDir = __DIR__ . "/tpl";
      $tmpDir = sys_get_temp_dir().'/twig-cache/';

      $loader = new Twig_Loader_Filesystem($tplDir);

      // force auto-reload to always have the latest version of the template
      $twig = new Twig_Environment($loader, [
         'cache'       => $tmpDir,
         'auto_reload' => true,
      ]);
      include __DIR__ . '/lib/GlpiLocalesExtension.php';
      $twig->addExtension(new GlpiLocalesExtension());
      // configure Twig the way you want

      // iterate over all templates
      foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tplDir), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
         // force compilation
         if ($file->isFile()) {
            $twig->loadTemplate($file->getFilename());
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
      $phpSources = "*.php ajax/*.php front/*.php inc/*.php install/*.php install/policies/*.php";
      // extract locales from source code
      $command = "xgettext $phpSources $compiledTemplates -o locales/$potfile -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po";
      $command .= " --keyword=_n:1,2,4t --keyword=__s:1,2t --keyword=__:1,2t --keyword=_e:1,2t --keyword=_x:1c,2,3t --keyword=_ex:1c,2,3t";
      $command .= " --keyword=_sx:1c,2,3t --keyword=_nx:1c,2,3,5t";
      $this->_exec($command);
      return $this;
   }

   /**
    * Build MO files
    */
   public function localesMo() {
      $localesPath = $this->getProjectPath() . '/locales';
      if ($handle = opendir($localesPath)) {
         while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
               $poFile = "$localesPath/$file";
               if (pathinfo($poFile, PATHINFO_EXTENSION) == 'po') {
                  $moFile = str_replace('.po', '.mo', $poFile);
                  $command = "msgfmt $poFile -o $moFile";
                  $this->_exec($command);
               }
            }
         }
         closedir($handle);
      }
      return $this;
   }

   /**
    * Returns all files tracked in the repository
    *
    * @param string $version
    * @throws Exception
    * @return array
    */
   protected function getTrackedFiles($version = null) {
      $output = [];
      if ($version === null) {
         $version = 'HEAD';
      }
      exec("git ls-tree -r '$version' --name-only", $output, $retCode);
      if ($retCode != '0') {
         throw new Exception("Unable to get tracked files for $version");
      }
      return $output;
   }

   /**
    * Returns all tags of the GIT repository
    *
    * @throws Exception
    * @return unknown
    */
   protected function getAllTags() {
      $prefix = self::$tagPrefix;
      exec("git tag --list '$prefix*' --sort=-refname", $output, $retCode);
      if ($retCode != '0') {
         // An error occured
         throw new Exception("Unable to get tags from the repository");
      }
      return $output;
   }

   /**
    * Check a tag exists in the GIT repository
    *
    * @param string $tag
    * @return boolean
    */
   protected function tagExists($tag) {
      $tags = $this->getAllTags();
      return in_array($tag, $tags);
   }

   /**
    * Check the version follows semver http://semver.org/
    *
    * Returns true if the version is well formed, false otherwise
    *
    * @param string $version
    * @return boolean
    */
   protected function isSemVer($version) {
      if (preg_match($this->getSemverRegex(), $version) == 1) {
         return true;
      }

      return false;
   }

   /**
    * Returns structure of the plugin XML description file
    *
    * @return array
    */
   protected function getPluginXMLDescription() {
      if (!is_file(self::$pluginXmlFile) || !is_readable(self::$pluginXmlFile)) {
         throw Exception("plugin.xml file not found");
      }

      $xml = simplexml_load_string(file_get_contents(self::$pluginXmlFile));
      $json = json_encode($xml);
      return json_decode($json, true);
   }

   /**
    * Search for a version in the plugin description XML file
    *
    * @param string $versionToSearch
    * @return mixed|NULL
    */
   protected function getVersionTagFromXML($versionToSearch) {
      $xml = $this->getPluginXMLDescription();
      if (!isset($xml['versions']['version'][0])) {
         $xml['versions']['version'] = [
            0 => $xml['versions']['version'],
         ];
      }
      // several versions available, in an array with numeric keys
      foreach ($xml['versions']['version'] as $version) {
         if ($version['num'] == $versionToSearch) {
            // version found
            return $version;
         }
      }

      return null;
   }

   /**
    * Return the hash of the current commit
    *
    * @throws Exception
    * @return string
    */
   protected function getCurrentCommitHash() {
      exec('git rev-parse HEAD', $output, $retCode);
      if ($retCode != '0') {
         throw new Exception("failed to get current commit hash");
      }
      return $output[0];
   }

   /**
    * Checks a  tagged commit matches the current commit
    * @param string $tag
    * @return boolean
    */
   protected function isTagMatchesCurrentCommit($tag) {
      $commitHash = $this->getCurrentCommitHash();
      exec("git tag -l --contains $commitHash", $output, $retCode);
      if (isset($output[0]) && $output[0] == $tag) {
         return true;
      }

      return false;
   }

   /**
    * Get a file from git tree
    * @param string $path
    * @param string $rev a commit hash, a tag or a branch
    * @throws Exception
    * @return string content of the file
    */
   protected function getFileFromGit($path, $rev) {
      $output = shell_exec("git show $rev:$path");
      if ($output === null) {
         throw new Exception ("coult not get file from git: $rev:$path");
      }
      return $output;
   }

   /**
    * Update headers in source files
    */
   public function codeHeadersUpdate() {
      $toUpdate = $this->getTrackedFiles();
      foreach ($toUpdate as $file) {
         $this->replaceSourceHeader($file);
      }
   }

   /**
    * Read the header template from a file
    * @throws Exception
    * @return string
    */
   protected function getHeaderTemplate() {
      if (empty($this->headerTemplate)) {
         $this->headerTemplate = file_get_contents(__DIR__ . '/tools/dev/HEADER');
         if (empty($this->headerTemplate)) {
            throw new Exception('Header template file not found');
         }
      }

      $copyrightRegex = "#Copyright (\(c\)|©) (\d{4}-)?(\d{4}) #iUm";
      $year = date("Y");
      $replacement = 'Copyright © ${2}' . $year . ' ';
      $this->headerTemplate = preg_replace($copyrightRegex, $replacement, $this->headerTemplate);

      return $this->headerTemplate;
   }

   /**
    * Format header template for a file type based on extension
    *
    * @param string $extension
    * @param string $template
    * @return string
    */
   protected function getFormatedHeaderTemplate($extension, $template) {
      switch ($extension) {
         case 'php':
            $lines = explode("\n", $template);
            foreach ($lines as &$line) {
               $line = rtrim(" * $line");
            }
            return implode("\n", $lines);
            break;

         default:
            return $template;
      }
   }

   /**
    * Update source code header in a source file
    * @param string $filename
    */
   protected function replaceSourceHeader($filename) {
      $filename = __DIR__ . "/$filename";

      // define regex for the file type
      $ext = pathinfo($filename, PATHINFO_EXTENSION);
      switch ($ext) {
         case 'php':
            $prefix              = "\<\?php\\n/\*(\*)?\\n";
            $replacementPrefix   = "<?php\n/**\n";
            $suffix              = "\\n( )?\*/";
            $replacementSuffix   = "\n */";
            break;

         default:
            // Unhandled file format
            return;
      }

      // format header template for the file type
      $header = trim($this->getHeaderTemplate());
      $formatedHeader = $replacementPrefix . $this->getFormatedHeaderTemplate($ext, $header) . $replacementSuffix;

      // get the content of the file to update
      $source = file_get_contents($filename);

      // update authors in formated template
      $headerMatch = [];
      $originalAuthors = [];
      $authors = [];
      $authorsRegex = "#^.*(\@author .*)$#Um";
      preg_match('#^' . $prefix . '(.*)' . $suffix . '#Us', $source, $headerMatch);
      if (isset($headerMatch[0])) {
         $originalHeader = $headerMatch[0];
         preg_match_all($authorsRegex, $originalHeader, $originalAuthors);
         if (isset($originalAuthors[1])) {
            $originalAuthors = $this->getFormatedHeaderTemplate($ext, implode("\n", $originalAuthors[1]));
            $formatedHeader = preg_replace($authorsRegex, $originalAuthors, $formatedHeader, 1);
         }
      }

      // replace the header if it exists
      $source = preg_replace('#^' . $prefix . '(.*)' . $suffix . '#Us', $formatedHeader, $source, 1);
      if (empty($source)) {
         throw new Exception("An error occurred while processing $filename");
      }

      file_put_contents($filename, $source);
   }

   /**
    * Return a regular expression to test a string against semver version naming specification
    * @return string
    */
   protected function getSemverRegex() {
      $regex = '#^\bv?(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)\.(?:0|[1-9]\d*)' // 3 numbers separated by a dots
         . '(?:-[\da-z\-]+(?:\.[\da-z\-]+)*)?(?:\+[\da-z\-]+(?:\.[\da-z\-]+)*)?\b$#i'; // dash and a word

      return $regex;
   }

   /**
    * @param array $files files to commit
    * @param string $version1
    * @param string $version2
    */
   protected function gitDiff(array $files, $version1 = '', $version2 = '') {
      if (count($files) < 1) {
         $arg = '-u';
      } else {
         $arg = '"' . implode('" "', $files) . '"';
      }

      if ($version1 == '' && $version2 == '') {
         $fromTo = '';
      } else {
         $fromTo = "$version1..$version2";
      }

      exec("git diff $fromTo -- $arg", $output, $retCode);
      if ($retCode > 0) {
         throw new Exception("Failed to diff $fromTo");
      }

      return $output;
   }


   /**
    */
   protected function updateChangelog() {
      exec("node_modules/.bin/conventional-changelog -p angular -i CHANGELOG.md -s", $output, $retCode);
      if ($retCode > 0) {
         throw new Exception("Failed to update the changelog");
      }

      return true;
   }

   private function checkUpgrade($rev) {
      try {
         $fileContent = $this->getFileFromGit('install/upgrade_to_develop.php', $rev);
      } catch (Exception $e) {
         return true;
      }
      if ($fileContent != '') {
         throw new Exception ('upgrade_to_develop.php must be renamed !');
         return false;
      }
      return true;
   }

   protected function getProjectPath() {
      return __DIR__;
   }
}
