<?php

/**
 * Manage packages translations for packages
 */
class IntegratedPackageLocalizer
{
    /**
     * The root directory containing the package files
     * @var string
     */
    private $baseDirectory = '';
    /**
     * This will be true if $baseDirectory is a temporary directory that should be removed on instance destruction
     * @var bool
     */
    private $baseDirectoryIsTemporary = false;
    /**
     * This is the real package root directory (the one containing the controller.php file)
     * @var string
     */
    private $controllerDirectory = '';
    /**
     * If the originating path was a zip file, this will contain the full path to that zip folder
     * @var string
     */
    private $originalZipPath = '';
    /**
     * The package handle
     * @var string
     */
    private $packageHandle = '';
    /**
     * The package version
     * @var string
     */
    private $packageVersion = '';
    /**
     * The translatable strings read from the package directory
     * @var \Gettext\Translations|null
     */
    private $translatables = null;
    /**
     * Initialize a new instance
     * @param string $path A path to the package .zip archive, or to the directory of the package (or to a directory that contains only the directory package)
     * @param string $pkgHandle The package handle. If not specified we'll determine it from the package controller.php file
     * @param string $pkgVersion The package version. If not specified we'll determine it from the package controller.php file
     * @throw Exception
     */
    public function __construct($path, $packageHandle = '', $packageVersion = '')
    {
        try {
            $realPath = @realpath($path);
            if ($realPath === false) {
                throw new Exception(t('Unable to find the file/directory %s', $path));
            }
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $realPath);
            if (is_dir($path)) {
                $this->baseDirectory = $path;
            } else {
                $feh = Loader::helper('file_extended', 'integrated_localization');
                /* @var $feh FileExtendedHelper */
                $this->baseDirectory = $feh->getTempSandboxDirectory(true);
                $this->baseDirectoryIsTemporary = true;
                if (!class_exists('ZipArchive')) {
                    throw new Exception(t('Missing PHP extension: %s', 'ZIP'));
                }
                $zip = new ZipArchive();
                self::checkZipOpenResult(@$zip->open($realPath));
                if (@$zip->extractTo($this->baseDirectory) !== true) {
                    $error = $zip->getStatusString();
                    if ((!is_string($error)) || ($error === '')) {
                        $error = t('Unknown error');
                    }
                    throw new Exception(t('Error extracting files from zip: %s', $error));
                }
                $zip->close();
                unset($zip);
                $this->originalZipPath = $realPath;
            }
            if (@is_file($this->baseDirectory.'/controller.php')) {
                $this->controllerDirectory = $this->baseDirectory;
            } else {
                $contents = @scandir($this->baseDirectory);
                if ($contents === false) {
                    throw new Exception(t('Unable to retrieve the contents of the directory %s', $this->baseDirectory));
                }
                $contents = array_values(array_filter($contents, function ($item) {
                    return $item[0] !== '.';
                }));
                if ((count($contents) === 1) && is_file($this->baseDirectory.'/'.$contents[0].'/controller.php')) {
                    $this->controllerDirectory = $this->baseDirectory.'/'.$contents[0];
                } else {
                    throw new Exception(t("Unable to find the package file '%s'", 'controller.php'));
                }
            }
            if (!is_string($packageHandle)) {
                $packageHandle = '';
            }
            if (is_int($packageVersion) || is_float($packageVersion)) {
                $packageVersion = (string) $packageVersion;
            } elseif (!is_string($packageVersion)) {
                $packageVersion = '';
            }
            if (($packageHandle === '') || ($packageVersion === '')) {
                $packageInfo = self::extractPackageInfo($this->controllerDirectory.'/controller.php');
                if ($packageHandle === '') {
                    $packageHandle = $packageInfo['handle'];
                }
                if ($packageVersion === '') {
                    $packageVersion = $packageInfo['version'];
                }
            }
            $this->packageHandle = $packageHandle;
            $this->packageVersion = $packageVersion;
        } catch (Exception $x) {
            if (isset($zip)) {
                @$zip->close();
                unset($zip);
            }
            $this->cleanup();
        }
    }
    /**
     * Clean up the instance temporary stuff
     */
    private function cleanup()
    {
        if ($this->baseDirectoryIsTemporary && is_dir($this->baseDirectory)) {
            Loader::helper('file_extended', 'integrated_localization')->deleteFromFileSystem($this->baseDirectory);
        }
    }
    /**
     * Automatically called on instance release
     */
    public function __destruct()
    {
        $this->cleanup();
    }
    /**
     * Return the package handle (extracted from the package controller.php if not given when the instane was initializated)
     * @return string
     */
    public function getPackageHandle()
    {
        return $this->packageHandle;
    }
    /**
     * Return the package version (extracted from the package controller.php if not given when the instane was initializated)
     * @return string
     */
    public function getPackageVersion()
    {
        return $this->packageVersion;
    }
    /**
     * Extract the translatable strings from the package files
     * @return \Gettext\Translations
     */
    public function getTranslatables()
    {
        if (!isset($this->translatables)) {
            $translatables = new \Gettext\Translations();
            \C5TL\Parser::clearCache();
            foreach (\C5TL\Parser::getAllParsers() as $parser) {
                if ($parser->canParseDirectory()) {
                    $parser->parseDirectory($this->controllerDirectory, 'packages/'.$this->getPackageHandle(), $translatables);
                }
            }
            \C5TL\Parser::clearCache();
            $this->translatables = $translatables;
        }

        return $this->translatables;
    }
    /**
     * Read the translated strings already in the package and/or in the database
     * @param IntegratedLocale|string $locale The locale for which you want the translations
     * @param bool $readFromDB Do you want to read translations from the database?
     * @param bool $readFromPackageFiles Do you want to read translations from the package .mo/.po files?
     * @param bool $useTranslatables Leave to true to use as strings dictionary the strings read from the package files too, false to use only the package .mo/.po files
     * @throws Exception
     * @return \Gettext\Translations
     */
    public function getTranslations($locale, $readFromPackageFiles = true, $readFromDB = true, $useTranslatables = true)
    {
        if (!is_object($locale)) {
            Loader::model('integrated_locale', 'integrated_localization');
            $l = IntegratedLocale::getByID($locale);
            if (!$l) {
                throw new Exception(t('Invalid locale identifier: %s', $locale));
            }
            $locale = $l;
        }
        if ($useTranslatables) {
            $translations = clone $this->getTranslatables();
        } else {
            $translations = new \Gettext\Translations();
        }
        $pluralCount = $locale->getPluralCount();
        $translations->setLanguage($locale->getID());
        $translations->setPluralForms($pluralCount, $locale->getPluralRule());
        if ($readFromPackageFiles) {
            $gettextDir = $this->controllerDirectory.'/languages/'.$locale->getID().'/LC_MESSAGES';
            if (is_dir($gettextDir)) {
                $poFile = "$gettextDir/messages.po";
                if (is_file($poFile)) {
                    $translations->mergeWith(
                        \Gettext\Extractors\Po::fromFile($poFile),
                        \Gettext\Translations::MERGE_ADD | \Gettext\Translations::MERGE_COMMENTS | \Gettext\Translations::MERGE_PLURAL
                    );
                }
                $moFile = "$gettextDir/messages.mo";
                if (is_file($moFile)) {
                    $translations->mergeWith(
                        \Gettext\Extractors\Mo::fromFile($moFile),
                        \Gettext\Translations::MERGE_ADD | \Gettext\Translations::MERGE_COMMENTS | \Gettext\Translations::MERGE_PLURAL
                    );
                }
            }
        }
        if ($readFromDB) {
            $tsh = Loader::helper('translations_source', 'integrated_localization');
            /* @var $tsh TranslationsSourceHelper */
            $tsh->fillInTranslations($locale, $translations);
        }

        return $translations;
    }
    /**
     * If this instance was created from a .zip archive, update the archive with the new files of the currently extracted temporary directory
     * @throws Exception
     */
    public function repack()
    {
        if ($this->originalZipPath === '') {
            throw new Exception(t('The class was not initialized starting from a zip archive.'));
        }
        try {
            $tempZipFilename = self::createZip($this->baseDirectory);
            if (@rename($tempZipFilename, $this->originalZipPath) !== true) {
                throw new Exception(t('Error moving zip file to its final location'));
            }
            unset($tempZipFilename);
        } catch (Exception $x) {
            if (isset($tempZipFilename) && is_file($tempZipFilename)) {
                @unlink($tempZipFilename);
                unset($tempZipFilename);
            }
            throw $x;
        }
    }
    /**
     * Create a .zip archive with the content of the current languages directory and ends the execution of the script
     * @throws Exception
     */
    public function downloadLanguagesFolder()
    {
        try {
            $tempZipFilename = self::createZip($this->controllerDirectory.'/languages', 'languages');
            $length = @filesize($tempZipFilename);
            if ($length === false) {
                throw new Exception(t('Unable to determine the size of the zip file'));
            }
            header('Content-type: application/zip');
            header('Content-Disposition: attachment; filename='.$this->packageHandle.'-'.$this->packageVersion.'.zip');
            header('Content-length: '.$length);
            header('Pragma: no-cache');
            header('Expires: 0');
            @readfile($tempZipFilename);
            @unlink($tempZipFilename);
            unset($tempZipFilename);
            die();
        } catch (Exception $x) {
            if (isset($tempZipFilename) && is_file($tempZipFilename)) {
                @unlink($tempZipFilename);
                unset($tempZipFilename);
            }
            throw $x;
        }
    }
    /**
     * Write the gettext .pot dictionary with the translatable strings extracted from the package files
     * @throws Exception
     */
    public function writePotFile()
    {
        $dir = $this->controllerDirectory.'/languages';
        if (!is_dir($dir)) {
            @mkdir($dir, DIRECTORY_PERMISSIONS_MODE, true);
            if (!is_dir($dir)) {
                throw new Exception(t('Unable to create the directory %s', $dir));
            }
        }
        $file = $dir.'/messages.pot';
        if ($this->getTranslatables()->toPoFile($file) !== true) {
            throw new Exception(t('Unable to write the file %s', $file));
        }
    }
    /**
     * Write the gettext .po/.mo files for a specific locale
     * @param IntegratedLocale|string $locale The language of the translations
     * @param \Gettext\Translations $translations The translations to weite
     * @param bool $writeMO Set to false to don't write the binary .mo file
     * @param bool $writePO Set to false to don't write the textual .po file
     * @throws Exception
     */
    public function writeTranslationsFile($locale, \Gettext\Translations $translations, $writeMO = true, $writePO = true)
    {
        if (!is_object($locale)) {
            Loader::model('integrated_locale', 'integrated_localization');
            $l = IntegratedLocale::getByID($locale);
            if (!$l) {
                throw new Exception(t('Invalid locale identifier: %s', $locale));
            }
            $locale = $l;
        }
        if ($writeMO || $writePO) {
            $gettextDir = $this->controllerDirectory.'/languages/'.$locale->getID().'/LC_MESSAGES';
            if (!is_dir($gettextDir)) {
                @mkdir($gettextDir, DIRECTORY_PERMISSIONS_MODE, true);
                if (!is_dir($gettextDir)) {
                    throw new Exception(t('Unable to create the directory %s', $gettextDir));
                }
            }
            if ($writePO) {
                $poFile = "$gettextDir/messages.po";
                if ($translations->toPoFile($poFile) !== true) {
                    throw new Exception(t('Unable to write the file %s', $poFile));
                }
            }
            if ($writeMO) {
                $moFile = "$gettextDir/messages.mo";
                if ($translations->toMoFile($moFile) !== true) {
                    throw new Exception(t('Unable to write the file %s', $moFile));
                }
            }
        }
    }
    /**
     * Check the result of a ZipArchive->open method call
     * @param true|int $rc
     * @throws Exception
     */
    private static function checkZipOpenResult($rc)
    {
        if ($rc !== true) {
            switch ($rc) {
                case ZipArchive::ER_EXISTS:
                    throw new Exception(t('Error opening zip: %s', t("File already exists.")));
                case ZipArchive::ER_INCONS:
                    throw new Exception(t('Error opening zip: %s', t("Zip archive inconsistent.")));
                case ZipArchive::ER_INVAL:
                    throw new Exception(t('Error opening zip: %s', t("Invalid argument.")));
                case ZipArchive::ER_MEMORY:
                    throw new Exception(t('Error opening zip: %s', t("Malloc failure.")));
                case ZipArchive::ER_NOENT:
                    throw new Exception(t('Error opening zip: %s', t("No such file.")));
                case ZipArchive::ER_NOZIP:
                    throw new Exception(t('Error opening zip: %s', t("Not a zip archive.")));
                case ZipArchive::ER_OPEN:
                    throw new Exception(t('Error opening zip: %s', t("Can't open file.")));
                case ZipArchive::ER_READ:
                    throw new Exception(t('Error opening zip: %s', t("Read error.")));
                case ZipArchive::ER_SEEK:
                    throw new Exception(t('Error opening zip: %s', t("Seek error.")));
                default:
                    throw new Exception(t('Error opening zip: %s', t("Unknown error (%s).", $rc)));
            }
        }
    }
    /**
     * Inspect a package to retrieve its controller path, its handle and its version
     * @param string $controllerFile The path to the package controller.php file
     * @throws Exception Throws an Exception if we were not able to retrieve all the data.
     * @return array Array keys are: handle, version
     */
    public static function extractPackageInfo($controllerFile)
    {
        if (!is_file($controllerFile)) {
            throw new Exception(t('Unable to find the file %s', $controllerFile));
        }
        if (!is_readable($controllerFile)) {
            throw new Exception(t("The file '%s' is not readable", $controllerFile));
        }
        $controllerContents = @file_get_contents($controllerFile);
        if ($controllerContents == false) {
            throw new Exception(t('Error reading the contents of the file %s', $controllerFile));
        }
        $readTokens = @token_get_all($controllerContents);
        if (!is_array($readTokens)) {
            throw new Exception(t('Error analyzing the PHP contents of the file %s', $controllerFile));
        }
        // Normalize tokens representation, remove comments and multiple spaces
        $tokens = array();
        foreach ($readTokens as $token) {
            if (is_array($token)) {
                $token = array(
                    'id' => $token[0],
                    '_' => token_name($token[0]),
                    'text' => $token[1],
                    'line' => $token[2],
                );
            } else {
                $token = array(
                    'id' => null,
                    'text' => $token,
                    'line' => null,
                );
            }
            switch ($token['id']) {
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                case T_WHITESPACE:
                    $n = count($tokens);
                    if (($n === 0) || ($tokens[$n - 1]['id'] !== T_WHITESPACE)) {
                        $tokens[] = $token;
                    }
                    break;
                default:
                    $tokens[] = $token;
            }
        }
        $pkgHandle = self::extractPackageInfo_lookForVar('$pkgHandle', $tokens);
        $pkgVersion = self::extractPackageInfo_lookForVar('$pkgVersion', $tokens);
        if (!(isset($pkgHandle)  && isset($pkgVersion))) {
            throw new Exception(t('Unable to detect the package handle and/or version'));
        }
        if (is_string($pkgHandle)) {
            $pkgHandle = trim($pkgHandle);
        }
        if ((!is_string($pkgHandle)) || ($pkgHandle === '')) {
            throw new Exception(t('The package handle is not valid'));
        }
        if (is_int($pkgVersion) || is_float($pkgVersion)) {
            $pkgVersion = (string) $pkgVersion;
        } else {
            if (is_string($pkgVersion)) {
                $pkgVersion = trim($pkgVersion);
            }
            if ((!is_string($pkgVersion)) || ($pkgVersion === '')) {
                throw new Exception(t('The package version is not valid'));
            }
        }

        return array(
            'handle' => $pkgHandle,
            'version' => $pkgVersion,
        );
    }
    /**
     * Helper method used by IntegratedPackageLocalizer::extractPackageInfo
     * @param string $name The name of the variable
     * @param array $tokens The php tokens
     * @return null|string|number The value of the variable (null if not found)
     */
    private static function extractPackageInfo_lookForVar($name, $tokens)
    {
        $n = count($tokens);
        foreach ($tokens as $varIndex => $varToken) {
            if (($varToken['id'] === T_VARIABLE) && ($varToken['text'] === $name)) {
                if (($varIndex > 1) && ($tokens[$varIndex - 1]['id'] === T_WHITESPACE)) {
                    switch ($tokens[$varIndex - 2]['id']) {
                        case T_PROTECTED:
                        case T_PUBLIC:
                        case T_VAR:
                            $iNext = $varIndex + 1;
                            if (($iNext < $n - 1) && ($tokens[$iNext]['id'] === T_WHITESPACE)) {
                                $iNext++;
                            }
                            if (($iNext < $n - 1) && ($tokens[$iNext]['text'] === '=')) {
                                $iNext++;
                                if (($iNext < $n - 1) && ($tokens[$iNext]['id'] === T_WHITESPACE)) {
                                    $iNext++;
                                }
                                if ($iNext < $n) {
                                    switch ($tokens[$iNext]['id']) {
                                        case T_CONSTANT_ENCAPSED_STRING:
                                            if (preg_match('/^["\'](.*)["\']$/', $tokens[$iNext]['text'], $matches)) {
                                                return stripslashes((string) $matches[1]);
                                            }
                                            break;
                                        case T_DNUMBER:
                                            return (float) $tokens[$iNext]['text'];
                                        case T_LNUMBER:
                                            return (int) $tokens[$iNext]['text'];
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        }

        return;
    }
    /**
     * Create a temporary zip archive
     * @param string $serverDirectory The directory to compress
     * @param string $zipBaseDirectory THe name of the compressed directory as stored in the zip file
     * @return string Returns the path of the newly created .zip file (you should delete it after you use it)
     */
    private static function createZip($serverDirectory, $zipBaseDirectory = '')
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception(t('Missing PHP extension: %s', 'ZIP'));
        }
        $tmp = @realpath($serverDirectory);
        if (($tmp === false) || (!is_dir($tmp))) {
            throw new Exception(t('Directory not found: %s', $serverDirectory));
        }
        $serverDirectory = str_replace(DIRECTORY_SEPARATOR, '/', $tmp);
        $zipBaseDirectory = is_string($zipBaseDirectory) ? trim(str_replace(DIRECTORY_SEPARATOR, '/', trim($zipBaseDirectory)), '/') : '';
        try {
            $feh = Loader::helper('file_extended', 'integrated_localization');
            /* @var $feh FileExtendedHelper */
            $tmp = @tempnam($feh->getTempSandboxDirectory(false), 'zip');
            if (!$tmp) {
                throw new Exception(t('Unable to create a temporary file'));
            }
            $tempZipFilename = $tmp;
            $zip = new ZipArchive();
            self::checkZipOpenResult(@$zip->open($tempZipFilename, ZIPARCHIVE::CREATE | ZipArchive::OVERWRITE));
            $prefix = '';
            if ($zipBaseDirectory !== '') {
                foreach (explode('/', $zipBaseDirectory) as $chunk) {
                    $prefix .= (($prefix === '') ? '' : '/').$chunk;
                    $rc = $zip->addEmptyDir($prefix);
                }
                if ($rc !== true) {
                    $error = $zip->getStatusString();
                    if ((!is_string($error)) || ($error === '')) {
                        $error = t('Unknown error');
                    }
                    throw new Exception(t('Error adding files to zip: %s', $error));
                }
                $prefix .= '/';
            }
            $startPath = strlen($serverDirectory) + 1;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($serverDirectory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $splFileInfo) {
                /* @var $splFileInfo SplFileInfo */
                $fileAbs = str_replace(DIRECTORY_SEPARATOR, '/', $splFileInfo->getRealPath());
                if ($fileAbs !== $serverDirectory) {
                    $fileRel = $prefix.substr($fileAbs, $startPath);
                    if (is_dir($fileAbs)) {
                        $rc = $zip->addEmptyDir($fileRel);
                    } elseif (is_file($fileAbs)) {
                        $rc = $zip->addFile($fileAbs, $fileRel);
                    }
                    if ($rc !== true) {
                        $error = $zip->getStatusString();
                        if ((!is_string($error)) || ($error === '')) {
                            $error = t('Unknown error');
                        }
                        throw new Exception(t('Error adding files to zip: %s', $error));
                    }
                }
            }
            @$zip->close();
            unset($zip);

            return $tempZipFilename;
        } catch (Exception $x) {
            if (isset($zip)) {
                @$zip->close();
                unset($zip);
            }
            if (isset($tempZipFilename) && is_file($tempZipFilename)) {
                @unlink($tempZipFilename);
                unset($tempZipFilename);
            }
            throw $x;
        }
    }
}
