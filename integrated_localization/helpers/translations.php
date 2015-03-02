<?php defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Handles translatable strings from core/packages.
 */
class TranslationsHelper
{
    /**
     * Import translatable strings from core/packages into the database.
     *
     * @param \Gettext\Translations $translations The translatable strings to import
     * @param string $packageHandle The package handle ('_' for the core)
     * @param string $packageVersion The package version ('dev-...' for the core development versions)
     *
     * @throws Exception
     *
     * @return array Keys are:
     * - int total: number of total entries found
     * - int updated: number of new entries updated
     * - int added: number of new entries added
     * - bool somethingChanged: will be set to true if something changed
     */
    public function saveTranslatables(\Gettext\Translations $translations, $packageHandle, $packageVersion)
    {
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $preHash = $db->GetOne('SELECT MD5(GROUP_CONCAT(itpTranslatable)) FROM IntegratedTranslatablePlaces WHERE (itpPackage = ?) AND (itpVersion = ?) ORDER BY itpTranslatable', array($packageHandle, $packageVersion));
        if (!isset($preHash)) {
            $preHash = '';
        }
        $result = array(
            'total' => 0,
            'added' => 0,
            'updated' => 0,
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
                    $refs[] = isset($tr[1]) ? implode(':', $tr) : $tr[0];
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
            $postHash = $db->GetOne('SELECT MD5(GROUP_CONCAT(itpTranslatable)) FROM IntegratedTranslatablePlaces WHERE (itpPackage = ?) AND (itpVersion = ?) ORDER BY itpTranslatable', array($packageHandle, $packageVersion));
            if (!isset($postHash)) {
                $postHash = '';
            }
            $result['somethingChanged'] = ($preHash === $postHash) ? false : true;
            Cache::delete('integrated_localization-po', $packageHandle.'@'.$packageVersion);
            Cache::delete('integrated_localization-po-dev', $packageHandle.'@'.$packageVersion);
            Cache::delete('integrated_localization-mo', $packageHandle.'@'.$packageVersion);
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
    /**
     * @param IntegratedLocale|string $locale
     * @param \Gettext\Translations $translations
     * @param bool $markAsApproved
     */
    public function saveTranslations($locale, \Gettext\Translations $translations, $markAsApproved)
    {
        if (!is_object($locale)) {
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
        $translations->setPluralForms($pluralCount, $locale->getPluralFormula());
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
     * @param string $only 'translated' to load only translated strings, 'untranslated' to load only untranslated strings. Anything else: load all the strings
     *
     * @return \Gettext\Translations
     */
    public function loadTranslationsByPackage($locale, $packageHandle, $packageVersion, $only = '', $unapprovedAsFuzzy = false)
    {
        if (!is_object($locale)) {
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
        $translations->setPluralForms($pluralCount, $locale->getPluralFormula());
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        switch ($only) {
            case 'translated':
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
                break;
            case 'untranslated':
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
                    AND
                    (IntegratedTranslations.itTranslatable IS NULL)
                ';
                $q = array(
                    $locale->getID(),
                    $packageHandle,
                    $packageVersion,
                );
                break;
            default:
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
                break;
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
                foreach (explode("\x04", $row['itpLocations']) as $location) {
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
    /**
     * @param IntegratedLocale|string $locale
     * @param \Gettext\Translations $translations
     * @param bool $unapprovedAsFuzzy
     */
    public function fillInTranslations($locale, $translations, $unapprovedAsFuzzy = false)
    {
        if (!is_object($locale)) {
            Loader::model('integrated_locale', 'integrated_localization');
            $l = IntegratedLocale::getByID($locale);
            if (!$l) {
                throw new Exception(t('Invalid locale identifier: %s', $locale));
            }
            $locale = $l;
        }
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $pluralCount = $locale->getPluralCount();
        $translations->setLanguage($locale->getID());
        $translations->setPluralForms($pluralCount, $locale->getPluralFormula());
        $total = count($translations);
        $current = 0;
        $searchGroup = array();
        $searchGroupCount = 0;
        foreach ($translations as $translation) {
            if ($translation->hasTranslation() === false) {
                $searchGroup[md5($translation->getId())] = $translation;
                $searchGroupCount++;
            }
            $current++;
            if (($current === $total) || ($searchGroupCount >= 25)) {
                if ($searchGroupCount > 0) {
                    $q = array_keys($searchGroup);
                    $q[] = $locale->getID();
                    $rs = $db->Query(
                        '
                            SELECT
                                IntegratedTranslatables.itHash,
                                IntegratedTranslations.*
                            FROM
                                    IntegratedTranslatables
                                INNER JOIN
                                    IntegratedTranslations
                                ON
                                    IntegratedTranslatables.itID = IntegratedTranslations.itTranslatable
                            WHERE
                                ('.implode(' OR ', array_fill(0, $searchGroupCount, '(IntegratedTranslatables.itHash = ?)')).')
                                AND
                                (IntegratedTranslations.itLocale = ?)
                        ',
                        $q
                    );
                    /* @var $rs ADORecordSet_mysql */
                    while ($row = $rs->FetchRow()) {
                        $translation = $searchGroup[$row['itHash']];
                        if ($translation->hasPlural()) {
                            $useCount = $pluralCount;
                            for ($i = 1; $i < $pluralCount; $i++) {
                                if ((!isset($row['itText'.$i])) || ($row['itText'.$i] === '')) {
                                    $useCount = 0;
                                    break;
                                }
                            }
                        } else {
                            $useCount = 1;
                        }
                        if ($useCount > 0) {
                            if ($unapprovedAsFuzzy && empty($row['itApproved'])) {
                                $translation->addFlag('fuzzy');
                            }
                            $translation->setTranslation($row['itText0']);
                            for ($i = 1; $i < $useCount; $i++) {
                                $translation->setPluralTranslation($row['itText'.$i], $i - 1);
                            }
                        }
                    }
                    $rs->Close();
                    $searchGroup = array();
                    $searchGroupCount = 0;
                }
            }
        }
    }
}
