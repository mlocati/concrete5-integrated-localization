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
            $db->Execute('DELETE FROM TranslatablePlaces WHERE (tpPackage = ?) AND (tpVersion = ?)', array($packageHandle, $packageVersion));
            $placesQuery = null;
            $placesQueryParams = null;
            $placesQueryCount = 0;
            foreach ($translations as $translation) {
                $result['total']++;
                /* @var $translation \Gettext\Translation */
                $hash = md5($translation->getId());
                $row = $db->GetRow('SELECT tID, tPlural FROM Translatables WHERE tHash = ? LIMIT 1', array($hash));
                if ($row) {
                    $tID = (int) $row['tID'];
                    if ($translation->hasPlural() && (((string) $row['tPlural']) === '')) {
                        $db->Execute('UPDATE Translatables SET tPlural = ? WHERE tID = ? LIMIT 1', array($translation->getPlural(), $tID));
                        $result['updated']++;
                    }
                } else {
                    $sql = 'INSERT INTO Translatables SET tHash = ?, tText = ?';
                    $q = array($hash, $translation->getOriginal());
                    if ($translation->getContext() !== '') {
                        $sql .= ', tContext = ?';
                        $q[] = $translation->getContext();
                    }
                    if ($translation->hasPlural()) {
                        $sql .= ', tPlural = ?';
                        $q[] = $translation->getPlural();
                    }
                    $db->Execute($sql, $q);
                    $tID = (int) $db->Insert_ID();
                    $result['added']++;
                }
                if ($placesQueryCount === 0) {
                    $placesQuery = 'INSERT INTO TranslatablePlaces (tpTranslatable, tpPackage, tpVersion, tpLocations, tpComments) VALUES ';
                    $placesQueryParams = array();
                } else {
                    $placesQuery .= ', ';
                }
                $placesQuery .= '('.$tID.', ?';
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

    public function processPackageZip($packageHandle, $packageVersion, $packageZip, $exportTranslations = true, $importTranslations = true, $importTranslationsAsApproved = false)
    {
        if(!is_file($packageZip)) {
            throw new Exception(t("Package archive not found: %s", $packageZip));
        }
        $fh = Loader::helper('file');
        /* @var $fh FileHelper */
        for($i = 0; ; $i++) {
            $unzippedFolder = str_replace(DIRECTORY_SEPARATOR, '/', $fh->getTemporaryDirectory()).'/localization/package-'.strtolower(trim(preg_replace('/[^\w\.]+/', '-', $packageHandle), '-'));
            if($i > 0) {
                $unzippedFolder .= '-'.$i;
            }
            if(file_exists($unzippedFolder)) {
                continue;
            }
            @mkdir($unzippedFolder, DIRECTORY_PERMISSIONS_MODE, true);
            if (!is_dir($unzippedFolder)) {
                throw new Exception(t('Unable to create the directory %s', $unzippedFolder));
            }
            break;
        }
        try {
            if(!class_exists('ZipArchive')) {
                throw new Exception(t('Missing PHP extension: %s', 'ZIP'));
            }
            $zip = new ZipArchive();
            $rc = @$zip->open($packageZip);
            self::checkZipOpenResult($rc);
            if(@$zip->extractTo($unzippedFolder) !== true) {
                $error = $zip->getStatusString();
                if((!is_string($error)) || ($error === '')) {
                    $error = t('Unknown error');
                }
                throw new Exception(t('Error extracting files from zip: %s', $error));
            }
            @$zip->close();
            unset($zip);
            $packageFilesChanged = $this->processPackageDirectory($packageHandle, $packageVersion, $unzippedFolder, $exportTranslations, $importTranslations, $importTranslationsAsApproved);
            if($packageFilesChanged) {
                $tempZipFilename = tempnam($fh->getTemporaryDirectory().'/localization', 'zip');
                if(!$tempZipFilename) {
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
                    if($fileAbs !== $unzippedFolder) {
                        $fileRel = substr($fileAbs, $startPath);
                        if (is_dir($fileAbs)) {
                            $rc = $zip->addEmptyDir($fileRel);
                        } elseif (is_file($fileAbs)) {
                            $rc = $zip->addFile($fileAbs, $fileRel);
                       }
                        if($rc !== true) {
                            $error = $zip->getStatusString();
                            if((!is_string($error)) || ($error === '')) {
                                $error = t('Unknown error');
                            }
                            throw new Exception(t('Error adding files to zip: %s', $error));
                        }
                    }
                }
                @$zip->close();
                unset($zip);
                if(@rename($tempZipFilename, $packageZip) !== true) {
                    throw new Exception(t('Error moving zip file to its final location'));
                }
                unset($tempZipFilename);
            }
            Loader::helper('file_extended', 'integrated_localization')->deleteFromFileSystem($unzippedFolder);
        }
        catch(Exception $x) {
            if(isset($zip)) {
                try {
                    @$zip->close();
                }
                catch(Exception $foo) {
                }
            }
            if(isset($tempZipFilename) && $tempZipFilename) {
                @unlink($tempZipFilename);
            }
            Loader::helper('file_extended', 'integrated_localization')->deleteFromFileSystem($unzippedFolder);
            throw $x;
        }
    }

    public function processPackageDirectory($packageHandle, $packageVersion, $dir, $exportTranslations = true, $importTranslations = true, $importTranslationsAsApproved = false)
    {
        if(is_file("$dir/controller.php")) {
            $packageRootDir = $dir;
        }
        elseif(is_file("$dir/$packageHandle/controller.php")) {
            $packageRootDir = "$dir/$packageHandle";
        }
        else {
            throw new Exception(t("Unable to find the package file '%s'", 'controller.php'));
        }
        $packageFilesChanged = false;
        $this->parseDirectory($packageRootDir, "packages/$packageHandle", $packageHandle, $packageVersion);
        if($importTranslations || $exportTranslations) {
            $translationsDir = "$packageRootDir/languages";
            foreach($this->getAvailableLocales() as $localeInfo) {
                $gettextDir = "$translationsDir/{$localeInfo['id']}/LC_MESSAGES";
                $poFile = "$gettextDir/messages.po";
                $moFile = "$gettextDir/messages.mo";
                $importedTranslations = null;
                if(is_dir($gettextDir)) {
                    $importedTranslations = new \Gettext\Translations();
                    if(is_file($poFile)) {
                        try {
                            \Gettext\Extractors\Po::fromFile($poFile, $importedTranslations);
                        }
                        catch(Exception $foo) {
                        }
                    }
                    if(is_file($moFile)) {
                        try {
                            \Gettext\Extractors\Mo::fromFile($moFile, $importedTranslations);
                        }
                        catch(Exception $foo) {
                        }
                    }
                    if($importedTranslations->count() > 0) {
                        if($importTranslations) {
                           $this->importTranslations($localeInfo['id'], $importedTranslations, $importTranslationsAsApproved);
                        }
                    }
                    else {
                        $importedTranslations = null;
                    }
                }
                if($exportTranslations) {
                    $translationsToExport = $this->loadPackageTranslations($localeInfo['id'], $packageHandle, $packageVersion);
                    if($importedTranslations) {
                        $translationsToExport->mergeWith($importedTranslations);
                    }
                    if($translationsToExport->count() > 0) {
                        $someTranslated = false;
                        foreach($translationsToExport as $translation) {
                            if($translation->hasTranslation()) {
                                $someTranslated = true;
                                break;
                            }
                        }
                        if($someTranslated) {
                            if(!is_dir($gettextDir)) {
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

    private static function checkZipOpenResult($rc) {
        if($rc !== true) {
            switch($rc) {
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

    public function getAvailableTranslations($packageHandle, $packageVersion, $minTranslationsPerc = null)
    {
        
    }

    private function getLocales($where = '', $q = array())
    {
        $result = array();
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $sql = 'SELECT * FROM Locales';
        if(is_string($where) && ($where !== '')) {
            $sql .= ' WHERE '.$where;
        }
        $sql .= ' ORDER BY lName';
        $rs = $db->Query($sql, $q);
        /* @var $rs ADORecordSet_mysql */
        while($row = $rs->FetchRow()) {
            $result[] = array(
                'id' => $row['lID'],
                'name' => $row['lName'],
                'isSource' => empty($row['lIsSource']) ? false : true,
                'pluralCount' => (int) $row['lPluralCount'],
                'pluralRule' => (int) $row['lPluralRule'],
            );
        }
        $rs->Close();
        
        return $result;
    }
    public function getAvailableLocales($excludeSourceLocale = true)
    {
        return $this->getLocales($excludeSourceLocale ? 'lIsSource = 0' : '');
    }
    public function getLocaleByID($localeID) {
        $list = $this->getLocales('lID = ?', array($localeID));
        return empty($list) ? null : $list[0];
    }
    

    public function importTranslations($localeID, \Gettext\Translations $translations, $markAsApproved)
    {
        $localeInfo = $this->getLocaleByID($localeID);
        if(!isset($localeInfo)) {
            throw new Exception(t('Invalid locale identifier: %s', $localeID));
        }
        $translations = clone $translations;
        $translations->setLanguage($localeInfo['id']);
        $translations->setPluralForms($localeInfo['pluralCount'], $localeInfo['pluralRule']);
        $markAsApproved = $markAsApproved ? 1 : 0;
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $adds = array();
        $maxPluralCount = 1;
        foreach($translations as $translation) {
            /* @var $translation \Gettext\Translation */
            if($translation->hasTranslation()) {
                $translated = true;
                $plural = $translation->hasPlural();
                if($plural) {
                    for($i = 0; $i < $localeInfo['pluralCount']; $i++) {
                        if($translation->getPluralTranslation($i) === '') {
                            $translated = false;
                            break;
                        }
                    }
                }
                if($translated) {
                    $markAsApprovedThis = $markAsApproved;
                    if($markAsApprovedThis === 1) {
                        foreach($translation->getFlags() as $flag) {
                            if($flag === 'fuzzy') {
                                $markAsApprovedThis = 0;
                                break;
                            }
                        }
                    }
                    $hash = md5($translation->getId());
                    $row = $db->GetRow(
                        '
                            SELECT
                                tID,
                                tPlural,
                                tApproved,
                                tText0
                            FROM
                                    Translatables
                                LEFT JOIN
                                    Translations
                                ON
                                    (? = Translations.tLocale)
                                    AND
                                    (Translatables.tID = Translations.tTranslatable)
                            WHERE
                               (tHash = ?)
                            LIMIT 1
                        ',
                        array($localeInfo['id'], $hash)
                    );
                    if($row) {
                        $translatableID = (int) $row['tID'];
                        $savedPlural = (isset($row['tPlural']) && ($row['tPlural'] !== '')) ? true : false;
                        if($savedPlural === $plural) {
                            if(is_null($row['tText0'])) {
                                $addThis = array(
                                    'tLocale' => $localeInfo['id'],
                                    'tTranslatable' => $translatableID,
                                    'tApproved' => $markAsApprovedThis,
                                    'tText0' => $translation->getTranslation(),
                                );
                                if($plural) {
                                    for($i = 0; $i < $localeInfo['pluralCount']; $i++) {
                                        $addThis['tText'.($i + 1)] = $translation->getPluralTranslation($i);
                                    }
                                    if($maxPluralCount < $localeInfo['pluralCount']) {
                                        $maxPluralCount = $localeInfo['pluralCount'];
                                    }
                                }
                                $adds[] = $addThis;
                            }
                            else {
                                $savedApproved = empty($row['tApproved']) ? 0 : 1;
                                if($markAsApprovedThis || ($savedApproved === 0)) {
                                    $sql = 'UPDATE Translations SET';
                                    $q = array();
                                    $sql .= ' tApproved = '.$markAsApprovedThis;
                                    $sql .= ', tText0 = ?';
                                    $q[] = $translation->getTranslation();
                                    if($plural) {
                                        for($i = 0; $i < $localeInfo['pluralCount']; $i++) {
                                            $sql .= ', tText'.($i + 1).' = ?';
                                            $q[] = $translation->getPluralTranslation($i);
                                        }
                                    }
                                    $sql .= ' WHERE (tLocale = ?) AND (tTranslatable = ?) LIMIT 1';
                                    $q[] = $localeInfo['id'];
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
        if($numAdd > 0) {
            $sqlStart = 'INSERT INTO Translations (tLocale, tTranslatable, tApproved';
            for($i = 0; $i < $maxPluralCount; $i++) {
                $sqlStart .= ', tText'.$i;
            }
            $sqlStart .= ') VALUES ';
            $sql = '';
            for($a = 0; $a < $numAdd; $a++) {
                if($sql === '') {
                    $thisQueryCount = 0;
                    $sql = $sqlStart;
                    $q = array();
                }
                else {
                    $thisQueryCount++;
                    $sql .= ', ';
                }
                $sql .= '(?, ?, ?';
                $q[] = $adds[$a]['tLocale'];
                $q[] = $adds[$a]['tTranslatable'];
                $q[] = $adds[$a]['tApproved'];
                for($i = 0; $i < $maxPluralCount; $i++) {
                    if(isset($adds[$a]['tText'.$i])) {
                        $sql .= ', ?';
                        $q[] = $adds[$a]['tText'.$i];
                    }
                    else {
                        $sql .= ', NULL';
                    }
                }
                $sql .= ')';
                if(($thisQueryCount === 20) || ($a === ($numAdd - 1))) {
                    $db->Execute($sql, $q);
                    $sql = '';
                }
            }
        }
    }
    
    /**
     * @param string $localeID
     * @param string $packageHandle
     * @param string $packageVersion
     * @param bool $onlyTranslated
     * @return \Gettext\Translations
     */
    public function loadPackageTranslations($localeID, $packageHandle, $packageVersion, $onlyTranslated = false, $unapprovedAsFuzzy = false)
    {
        $localeInfo = $this->getLocaleByID($localeID);
        if(!isset($localeInfo)) {
            throw new Exception(t('Invalid locale identifier: %s', $localeID));
        }
        $translations = new \Gettext\Translations();
        $translations->setHeader('Project-Id-Version', "$packageHandle v$packageVersion");
        $translations->setLanguage($localeInfo['id']);
        $translations->setPluralForms($localeInfo['pluralCount'], $localeInfo['pluralRule']);
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        if($onlyTranslated) {
            $from = '
                    TranslatablePlaces
                INNER JOIN
                    Translations
                ON
                    (TranslatablePlaces.tpTranslatable = Translations.tTranslatable)
                INNER JOIN
                    Translatables
                ON
                    (Translatables.tID = TranslatablePlaces.tpTranslatable)
            ';
            $where = '
                (TranslatablePlaces.tpPackage = ?)
                AND
                (TranslatablePlaces.tpVersion = ?)
                AND
                (Translations.tLocale = ?)
            ';
             $q = array(
                $packageHandle,
                $packageVersion,
                $localeInfo['id'],
            );
        }
        else {
            $from = '
                    TranslatablePlaces
                INNER JOIN
                    Translatables
                ON
                    (Translatables.tID = TranslatablePlaces.tpTranslatable)
                LEFT JOIN
                    Translations
                ON
                    (? = Translations.tLocale)
                    AND
                    (TranslatablePlaces.tpTranslatable = Translations.tTranslatable)
            ';
            $where = '
                (TranslatablePlaces.tpPackage = ?)
                AND
                (TranslatablePlaces.tpVersion = ?)
            ';
            $q = array(
                $localeInfo['id'],
                $packageHandle,
                $packageVersion,
            );
        }
        $select = "
             TranslatablePlaces.tpLocations,
             TranslatablePlaces.tpComments,
             Translatables.tContext,
             Translatables.tText,
             Translatables.tPlural,
             Translations.tApproved
        ";
        for($i = 0; $i < $localeInfo['pluralCount']; $i++) {
            $select .= ', Translations.tText'.$i;
        }
        $rs = $db->Query("SELECT $select FROM $from WHERE $where", $q);
        /* @var $rs ADORecordSet_mysql */
        while($row = $rs->FetchRow()) {
            $transation = $translations->insert($row['tContext'], $row['tText'], $row['tPlural']);
            if(isset($row['tText0'])) {
                $transation->setTranslation($row['tText0']);
                if($transation->hasPlural()) {
                    for($i = 1; $i < $localeInfo['pluralCount']; $i++) {
                        $transation->setPluralTranslation($row['tText'.$i], $i - 1);
                    }
                }
                if($unapprovedAsFuzzy && empty($row['tApproved'])) {
                    $transation->addFlag('fuzzy');
                }
            }
            if(isset($row['tpLocations']) && ($row['tpLocations'] !== '')) {
                foreach(explode("\x04", $row['tpLocations']) as $location) {
                    if($location !== '') {
                        $line = null;
                        if(preg_match('/^(.+):(\d+)$/', $location, $m)) {
                            $location = $m[1];
                            $line = (int) $m[2];
                        }
                        $transation->addReference($location, $line);
                    }
                }
            }
            if(isset($row['tpComments']) && ($row['tpComments'] !== '')) {
                foreach(explode("\x04", $row['tpComments']) as $comment) {
                    if($comment !== '') {
                        $transation->addExtractedComment($comment);
                    }
                }
            }
        }
        $rs->Close();

        return $translations;
    }
}
