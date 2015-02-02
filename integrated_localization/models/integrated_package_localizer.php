<?php

class IntegratedPackageLocalizer
{
    /**
     * @var string
     */
    private $baseDirectory = '';
    /**
     * @var bool
     */
    private $baseDirectoryIsTemporary = false;
    /**
     * @var string
     */
    private $controllerDirectory = '';
    /**
     * @var string
     */
    private $originalZipPath = '';
    /**
     * @var string
     */
    private $packageHandle = '';
    /**
     * @var string
     */
    private $packageVersion = '';
    /**
     * @var \Gettext\Translations|null
     */
    private $translatables = null;
    /**
     * @param string $path
     * @param string $pkgHandle
     * @param string $pkgVersion
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
     */
    private function cleanup()
    {
        if ($this->baseDirectoryIsTemporary && is_dir($this->baseDirectory)) {
            Loader::helper('file_extended', 'integrated_localization')->deleteFromFileSystem($this->baseDirectory);
        }
    }
    /**
     */
    public function __destruct()
    {
        $this->cleanup();
    }
    /**
     * @return string
     */
    public function getPackageHandle()
    {
        return $this->packageHandle;
    }
    /**
     * @return string
     */
    public function getPackageVersion()
    {
        return $this->packageVersion;
    }
    /**
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
     * @param IntegratedLocale|string $locale
     * @param bool $readFromDB
     * @param bool $readFromPackageFiles
     * @param bool $useTranslatables
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
                        MERGE_ADD | MERGE_COMMENTS | MERGE_PLURAL
                    );
                }
                $moFile = "$gettextDir/messages.mo";
                if (is_file($poFile)) {
                    $translations->mergeWith(
                        \Gettext\Extractors\Mo::fromFile($moFile),
                        MERGE_ADD | MERGE_COMMENTS | MERGE_PLURAL
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
     * @param IntegratedLocale|string $locale
     * @param \Gettext\Translations $translations
     * @param bool $writeMO
     * @param bool $writePO
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
     * @param string $name
     * @param array $tokens
     * @return null|string|number
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
     * @param string $serverDirectory
     * @param string $zipBaseDirectory
     * @return string
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
