<?php defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Handles translatable strings from core/packages
 */
class TranslationsSourceHelper
{
    /**
     * Extract translatable strings from core/packages
     * @param string $directory The root directory to parse
     * @param string $relPath How the root directory will be seen by translators
     * @param string $packageHandle The package handle ('-' for the core)
     * @param string $packageVersion The package version ('dev-...' for the core development versions)
     * @throws Exception
     * @return array Keys are:
     * - int total: number of total entries found
     * - int updated: numnber of new entries updated
     * - int added: number of new entries added
     */
    public function parseDirectory($directory, $relPath, $packageHandle, $packageVersion)
    {
        $translations = new \Gettext\Translations();
        \C5TL\Parser::clearCache();
        foreach (\C5TL\Parser::getAllParsers() as $parser) {
            if ($parser->canParseDirectory()) {
                $parser->parseDirectory($directory, $relPath, $translations);
            }
        }
        \C5TL\Parser::clearCache();
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $result = array(
            'total' => 0,
            'updated' => 0,
            'added' => 0,
        );
        $db->Execute('START TRANSACTION');
        try {
            $db->Execute('DELETE FROM IntegratedTranslatablePlaces WHERE (itpPackage = ?) AND (itpVersion = ?)', array($packageHandle, $packageVersion));
            $placesQuery = null;
            $placesQueryParams = null;
            $placesQueryCount = 0;
            foreach ($translations as $translation) {
                $result['total']++;
                /* @var $translation \Gettext\Translation */
                $hash = md5($translation->getId());
                $row = $db->GetRow('SELECT itID, itPlural FROM IntegratedTranslatables WHERE itHash = ? LIMIT 1', array($hash));
                if ($row) {
                    $itID = (int) $row['itID'];
                    if ($translation->hasPlural() && (((string) $row['itPlural']) === '')) {
                        $db->Execute('UPDATE IntegratedTranslatables SET itPlural = ? WHERE itID = ? LIMIT 1', array($translation->getPlural(), $itID));
                        $result['updated']++;
                    }
                } else {
                    $sql = 'INSERT INTO IntegratedTranslatables SET itHash = ?, itText = ?';
                    $q = array($hash, $translation->getOriginal());
                    if ($translation->hasContext() !== '') {
                        $sql .= ', itContext = ?';
                        $q[] = $translation->getContext();
                    }
                    if ($translation->hasPlural()) {
                        $sql .= ', itPlural = ?';
                        $q[] = $translation->getPlural();
                    }
                    $db->Execute($sql, $q);
                    $itID = (int) $db->Insert_ID();
                    $result['added']++;
                }
                if ($placesQueryCount === 0) {
                    $placesQuery = 'INSERT INTO IntegratedTranslatablePlaces (itpTranslatable, itpPackage, itpVersion, itpLocations, itpComments) VALUES ';
                    $placesQueryParams = array();
                } else {
                    $placesQuery .= ', ';
                }
                $placesQuery .= '('.$itID.', ?';
                $placesQueryParams[] = $packageHandle;
                $placesQuery .= ', ?';
                $placesQueryParams[] = $packageVersion;
                $refs = array();
                foreach ($translation->getReferences() as $tr) {
                    $refs[] = implode(':', $tr);
                }
                if (empty($refs)) {
                    $placesQuery .= ', NULL';
                } else {
                    $placesQuery .= ', ?';
                    $placesQueryParams[] = implode("\x04", $refs);
                }
                if ($translation->hasComments()) {
                    $placesQuery .= ', ?';
                    $placesQueryParams[] = implode("\x04", $translation->getComments());
                } else {
                    $placesQuery .= ', NULL';
                }
                $placesQuery .= ')';
                $placesQueryCount++;
                if ($placesQueryCount >= 50) {
                    $db->Execute($placesQuery, $placesQueryParams);
                    $placesQueryCount = 0;
                }
            }
            if ($placesQueryCount !== 0) {
                $db->Execute($placesQuery, $placesQueryParams);
            }
            $db->Execute('COMMIT');

            return $result;
        } catch (Exception $x) {
            try {
                $db->Execute('ROLLBACK');
            } catch (Exception $foo) {
            }
            throw $x;
        }
    }

    public function processPackageZip($packageZip, &$packageHandle = '', &$packageVersion = '', $exportTranslations = true, $importTranslations = true, $importTranslationsAsApproved = false)
    {
        if (!is_file($packageZip)) {
            throw new Exception(t("Package archive not found: %s", $packageZip));
        }
        $fh = Loader::helper('file');
        /* @var $fh FileHelper */
        for ($i = 0; ; $i++) {
            $unzippedFolder = str_replace(DIRECTORY_SEPARATOR, '/', $fh->getTemporaryDirectory()).'/localization/package-'.trim(preg_replace('/[^\w\.]+/', '-', basename(strtolower($packageZip), '.zip')), '-');
            if ($i > 0) {
                $unzippedFolder .= '-'.$i;
            }
            if (file_exists($unzippedFolder)) {
                continue;
            }
            @mkdir($unzippedFolder, DIRECTORY_PERMISSIONS_MODE, true);
            if (!is_dir($unzippedFolder)) {
                throw new Exception(t('Unable to create the directory %s', $unzippedFolder));
            }
            break;
        }
        try {
            if (!class_exists('ZipArchive')) {
                throw new Exception(t('Missing PHP extension: %s', 'ZIP'));
            }
            $zip = new ZipArchive();
            $rc = @$zip->open($packageZip);
            self::checkZipOpenResult($rc);
            if (@$zip->extractTo($unzippedFolder) !== true) {
                $error = $zip->getStatusString();
                if ((!is_string($error)) || ($error === '')) {
                    $error = t('Unknown error');
                }
                throw new Exception(t('Error extracting files from zip: %s', $error));
            }
            @$zip->close();
            unset($zip);
            $packageFilesChanged = $this->processPackageDirectory($unzippedFolder, $packageHandle, $packageVersion, $exportTranslations, $importTranslations, $importTranslationsAsApproved);
            if ($packageFilesChanged) {
                $tempZipFilename = tempnam($fh->getTemporaryDirectory().'/localization', 'zip');
                if (!$tempZipFilename) {
                    throw new Exception(t('Unable to create a temporary file'));
                }
                $zip = new ZipArchive();
                $rc = $zip->open($tempZipFilename, ZIPARCHIVE::CREATE | ZipArchive::OVERWRITE);
                self::checkZipOpenResult($rc);
                $startPath = strlen($unzippedFolder) + 1;
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($unzippedFolder, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
                foreach ($iterator as $splFileInfo) {
                    /* @var $splFileInfo SplFileInfo */
                    $fileAbs = str_replace(DIRECTORY_SEPARATOR, '/', $splFileInfo->getRealPath());
                    if ($fileAbs !== $unzippedFolder) {
                        $fileRel = substr($fileAbs, $startPath);
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
                if (@rename($tempZipFilename, $packageZip) !== true) {
                    throw new Exception(t('Error moving zip file to its final location'));
                }
                unset($tempZipFilename);
            }
            Loader::helper('file_extended', 'integrated_localization')->deleteFromFileSystem($unzippedFolder);
        } catch (Exception $x) {
            if (isset($zip)) {
                try {
                    @$zip->close();
                } catch (Exception $foo) {
                }
            }
            if (isset($tempZipFilename) && $tempZipFilename) {
                @unlink($tempZipFilename);
            }
            Loader::helper('file_extended', 'integrated_localization')->deleteFromFileSystem($unzippedFolder);
            throw $x;
        }
    }

    public function processPackageDirectory($dir, &$packageHandle = '', &$packageVersion = '', $exportTranslations = true, $importTranslations = true, $importTranslationsAsApproved = false)
    {
        $pih = Loader::helper('package_inspector', 'integrated_localization');
        /* @var $pih PackageInspectorHelper */
        $packageRootDir = $pih->getControllerDirectory($dir);
        if ($packageRootDir === '') {
            throw new Exception(t("Unable to find the package file '%s'", 'controller.php'));
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
            $packageInfo = $pih->getPackageInfo("$packageRootDir/controller.php");
            if ($packageHandle === '') {
                $packageHandle = $packageInfo['handle'];
            }
            if ($packageVersion === '') {
                $packageVersion = $packageInfo['version'];
            }
        }
        $packageFilesChanged = false;
        $this->parseDirectory($packageRootDir, "packages/$packageHandle", $packageHandle, $packageVersion);
        if ($importTranslations || $exportTranslations) {
            Loader::model('integrated_locale', 'integrated_localization');
            $translationsDir = "$packageRootDir/languages";
            foreach (IntegratedLocale::getList() as $locale) {
                $gettextDir = "$translationsDir/".$locale->getID()."/LC_MESSAGES";
                $poFile = "$gettextDir/messages.po";
                $moFile = "$gettextDir/messages.mo";
                $importedTranslations = null;
                if (is_dir($gettextDir)) {
                    $importedTranslations = new \Gettext\Translations();
                    if (is_file($poFile)) {
                        try {
                            \Gettext\Extractors\Po::fromFile($poFile, $importedTranslations);
                        } catch (Exception $foo) {
                        }
                    }
                    if (is_file($moFile)) {
                        try {
                            \Gettext\Extractors\Mo::fromFile($moFile, $importedTranslations);
                        } catch (Exception $foo) {
                        }
                    }
                    if ($importedTranslations->count() > 0) {
                        if ($importTranslations) {
                            $this->importTranslations($locale->getID(), $importedTranslations, $importTranslationsAsApproved);
                        }
                    } else {
                        $importedTranslations = null;
                    }
                }
                if ($exportTranslations) {
                    $translationsToExport = $this->loadPackageTranslations($locale->getID(), $packageHandle, $packageVersion);
                    if ($importedTranslations) {
                        $translationsToExport->mergeWith($importedTranslations);
                    }
                    if ($translationsToExport->count() > 0) {
                        $someTranslated = false;
                        foreach ($translationsToExport as $translation) {
                            if ($translation->hasTranslation()) {
                                $someTranslated = true;
                                break;
                            }
                        }
                        if ($someTranslated) {
                            if (!is_dir($gettextDir)) {
                                @mkdir($gettextDir, DIRECTORY_PERMISSIONS_MODE, true);
                                if (!is_dir($gettextDir)) {
                                    throw new Exception(t('Unable to create the directory %s', $gettextDir));
                                }
                            }
                            $translationsToExport->toPoFile($poFile);
                            $translationsToExport->toMoFile($moFile);
                            $packageFilesChanged = true;
                        }
                    }
                }
            }
        }

        return $packageFilesChanged;
    }

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
     * @param IntegratedLocale|string $locale
     * @param \Gettext\Translations $translations
     * @param bool $markAsApproved
     */
    public function importTranslations($locale, \Gettext\Translations $translations, $markAsApproved)
    {
        if(!is_object($locale)) {
            Loader::model('integrated_locale', 'integrated_localization');
            $l = IntegratedLocale::getByID($locale);
            if (!$l) {
                throw new Exception(t('Invalid locale identifier: %s', $locale));
            }
            $locale = $l;
        }
        $pluralCount = $locale->getPluralCount();
        $translations = clone $translations;
        $translations->setLanguage($locale->getID());
        $translations->setPluralForms($pluralCount, $locale->getPluralRule());
        $markAsApproved = $markAsApproved ? 1 : 0;
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $adds = array();
        foreach ($translations as $translation) {
            /* @var $translation \Gettext\Translation */
            if ($translation->hasTranslation()) {
                $translated = true;
                $plural = $translation->hasPlural();
                if ($plural) {
                    for ($i = 0; $i < $pluralCount; $i++) {
                        if ($translation->getPluralTranslation($i) === '') {
                            $translated = false;
                            break;
                        }
                    }
                }
                if ($translated) {
                    $markAsApprovedThis = $markAsApproved;
                    if ($markAsApprovedThis === 1) {
                        foreach ($translation->getFlags() as $flag) {
                            if ($flag === 'fuzzy') {
                                $markAsApprovedThis = 0;
                                break;
                            }
                        }
                    }
                    $hash = md5($translation->getId());
                    $row = $db->GetRow(
                        '
                            SELECT
                                itID,
                                itPlural,
                                itApproved,
                                itText0
                            FROM
                                    IntegratedTranslatables
                                LEFT JOIN
                                    IntegratedTranslations
                                ON
                                    (? = IntegratedTranslations.itLocale)
                                    AND
                                    (IntegratedTranslatables.itID = IntegratedTranslations.itTranslatable)
                            WHERE
                               (itHash = ?)
                            LIMIT 1
                        ',
                        array($locale->getID(), $hash)
                    );
                    if ($row) {
                        $translatableID = (int) $row['itID'];
                        $savedPlural = (isset($row['itPlural']) && ($row['itPlural'] !== '')) ? true : false;
                        if ($savedPlural === $plural) {
                            if (is_null($row['itText0'])) {
                                $addThis = array(
                                    'itLocale' => $locale->getID(),
                                    'itTranslatable' => $translatableID,
                                    'itApproved' => $markAsApprovedThis,
                                    'itText0' => $translation->getTranslation(),
                                );
                                if ($plural) {
                                    for ($i = 0; $i < $pluralCount; $i++) {
                                        $addThis['itText'.($i + 1)] = $translation->getPluralTranslation($i);
                                    }
                                }
                                $adds[] = $addThis;
                            } else {
                                $savedApproved = empty($row['itApproved']) ? 0 : 1;
                                if ($markAsApprovedThis || ($savedApproved === 0)) {
                                    $sql = 'UPDATE IntegratedTranslations SET';
                                    $q = array();
                                    $sql .= ' itApproved = '.$markAsApprovedThis;
                                    $sql .= ', itText0 = ?';
                                    $q[] = $translation->getTranslation();
                                    if ($plural) {
                                        for ($i = 0; $i < $pluralCount; $i++) {
                                            $sql .= ', itText'.($i + 1).' = ?';
                                            $q[] = $translation->getPluralTranslation($i);
                                        }
                                    }
                                    $sql .= ' WHERE (itLocale = ?) AND (itTranslatable = ?) LIMIT 1';
                                    $q[] = $locale->getID();
                                    $q[] = $translatableID;
                                    $db->Execute($sql, $q);
                                }
                            }
                        }
                    }
                }
            }
        }
        $numAdd = count($adds);
        if ($numAdd > 0) {
            $sqlStart = 'INSERT INTO IntegratedTranslations (itLocale, itTranslatable, itApproved';
            for ($i = 0; $i < $pluralCount; $i++) {
                $sqlStart .= ', itText'.$i;
            }
            $sqlStart .= ') VALUES ';
            $sql = '';
            for ($a = 0; $a < $numAdd; $a++) {
                if ($sql === '') {
                    $thisQueryCount = 0;
                    $sql = $sqlStart;
                    $q = array();
                } else {
                    $thisQueryCount++;
                    $sql .= ', ';
                }
                $sql .= '(?, ?, ?';
                $q[] = $adds[$a]['itLocale'];
                $q[] = $adds[$a]['itTranslatable'];
                $q[] = $adds[$a]['itApproved'];
                for ($i = 0; $i < $pluralCount; $i++) {
                    if (isset($adds[$a]['itText'.$i])) {
                        $sql .= ', ?';
                        $q[] = $adds[$a]['itText'.$i];
                    } else {
                        $sql .= ', NULL';
                    }
                }
                $sql .= ')';
                if (($thisQueryCount === 20) || ($a === ($numAdd - 1))) {
                    $db->Execute($sql, $q);
                    $sql = '';
                }
            }
        }
    }

    /**
     * @param IntegratedLocale|string $locale
     * @param string $packageHandle
     * @param string $packageVersion
     * @param bool $onlyTranslated
     * @return \Gettext\Translations
     */
    public function loadPackageTranslations($locale, $packageHandle, $packageVersion, $onlyTranslated = false, $unapprovedAsFuzzy = false)
    {
        if(!is_object($locale)) {
            Loader::model('integrated_locale', 'integrated_localization');
            $l = IntegratedLocale::getByID($locale);
            if (!$l) {
                throw new Exception(t('Invalid locale identifier: %s', $locale));
            }
            $locale = $l;
        }
        $pluralCount = $locale->getPluralCount();
        $translations = new \Gettext\Translations();
        $translations->setHeader('Project-Id-Version', "$packageHandle v$packageVersion");
        $translations->setLanguage($locale->getID());
        $translations->setPluralForms($pluralCount, $locale->getPluralRule());
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        if ($onlyTranslated) {
            $from = '
                    IntegratedTranslatablePlaces
                INNER JOIN
                    IntegratedTranslations
                ON
                    (IntegratedTranslatablePlaces.itpTranslatable = IntegratedTranslations.itTranslatable)
                INNER JOIN
                    IntegratedTranslatables
                ON
                    (IntegratedTranslatables.itID = IntegratedTranslatablePlaces.itpTranslatable)
            ';
            $where = '
                (IntegratedTranslatablePlaces.itpPackage = ?)
                AND
                (IntegratedTranslatablePlaces.itpVersion = ?)
                AND
                (IntegratedTranslations.itLocale = ?)
            ';
            $q = array(
                $packageHandle,
                $packageVersion,
                $locale->getID(),
            );
        } else {
            $from = '
                    IntegratedTranslatablePlaces
                INNER JOIN
                    IntegratedTranslatables
                ON
                    (IntegratedTranslatables.itID = IntegratedTranslatablePlaces.itpTranslatable)
                LEFT JOIN
                    IntegratedTranslations
                ON
                    (? = IntegratedTranslations.itLocale)
                    AND
                    (IntegratedTranslatablePlaces.itpTranslatable = IntegratedTranslations.itTranslatable)
            ';
            $where = '
                (IntegratedTranslatablePlaces.itpPackage = ?)
                AND
                (IntegratedTranslatablePlaces.itpVersion = ?)
            ';
            $q = array(
                $locale->getID(),
                $packageHandle,
                $packageVersion,
            );
        }
        $select = "
             IntegratedTranslatablePlaces.itpLocations,
             IntegratedTranslatablePlaces.itpComments,
             IntegratedTranslatables.itContext,
             IntegratedTranslatables.itText,
             IntegratedTranslatables.itPlural,
             IntegratedTranslations.itApproved
        ";
        for ($i = 0; $i < $pluralCount; $i++) {
            $select .= ', IntegratedTranslations.itText'.$i;
        }
        $rs = $db->Query("SELECT $select FROM $from WHERE $where", $q);
        /* @var $rs ADORecordSet_mysql */
        while ($row = $rs->FetchRow()) {
            $transation = $translations->insert($row['itContext'], $row['itText'], $row['itPlural']);
            if (isset($row['itText0'])) {
                $transation->setTranslation($row['itText0']);
                if ($transation->hasPlural()) {
                    for ($i = 1; $i < $pluralCount; $i++) {
                        $transation->setPluralTranslation($row['itText'.$i], $i - 1);
                    }
                }
                if ($unapprovedAsFuzzy && empty($row['itApproved'])) {
                    $transation->addFlag('fuzzy');
                }
            }
            if (isset($row['itpLocations']) && ($row['itpLocations'] !== '')) {
                foreach (explode("\x04", $row['iitpLocations']) as $location) {
                    if ($location !== '') {
                        $line = null;
                        if (preg_match('/^(.+):(\d+)$/', $location, $m)) {
                            $location = $m[1];
                            $line = (int) $m[2];
                        }
                        $transation->addReference($location, $line);
                    }
                }
            }
            if (isset($row['itpComments']) && ($row['itpComments'] !== '')) {
                foreach (explode("\x04", $row['itpComments']) as $comment) {
                    if ($comment !== '') {
                        $transation->addExtractedComment($comment);
                    }
                }
            }
        }
        $rs->Close();

        return $translations;
    }
}
