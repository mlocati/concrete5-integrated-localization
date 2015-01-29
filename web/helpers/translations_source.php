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
     * - int removed: number of old entries not found here
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
        $previousCount = $db->GetOne('SELECT COUNT(*) FROM TranslatablePlaces WHERE (tpPackage = ?) AND (tpVersion = ?)', array($packageHandle, $packageVersion));
        $previousCount = $previousCount ? ((int) $previousCount) : 0;
        $previousKept = 0;
        $db->Execute('START TRANSACTION');
        try {
            if ($previousCount !== 0) {
                $db->Execute('DELETE FROM TranslatablePlaces WHERE (tpPackage = ?) AND (tpVersion = ?)', array($packageHandle, $packageVersion));
            }
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
                    } else {
                        $previousKept++;
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
            $result['removed'] = $previousCount - ($result['updated'] + $previousKept);

            return $result;
        } catch (Exception $x) {
            try {
                $db->Execute('ROLLBACK');
            } catch (Exception $foo) {
            }
            throw $x;
        }
    }
}
