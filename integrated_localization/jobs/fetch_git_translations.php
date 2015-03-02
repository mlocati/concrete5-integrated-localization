<?php defined('C5_EXECUTE') or die('Access Denied.');

class FetchGitTranslations extends Job
{

    public function getJobName()
    {
        return t('Fetch git translations');
    }

    public function getJobDescription()
    {
        return t('Fetch the git repositories and extract translatable strings.');
    }

    public function run()
    {
        if (!@ini_get('safe_mode')) {
            @set_time_limit(0);
            @ini_set('max_execution_time', 0);
        }
        $tsh = Loader::helper('translations', 'integrated_localization');
        /* @var $tsh TranslationsHelper */
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        Loader::model('integrated_translated_repository', 'integrated_localization');
        $stats = array(
            'repositories' => 0,
            'branches' => 0,
        );
        foreach(IntegratedTranslatedRepository::getListForAutomatedJob() as $itr) {
            $repository = $itr->getGitRepository();
            // Fetch latest version and switch to HEAD
            $repository->update();
            if ($itr->getDevelopKey() !== '') {
                $stats = self::parseRepositoryDirectory($stats, $tsh, $repository->getDirectory(), $itr->getPackageHandle(), $itr->getDevelopKey(), $itr->getWebRoot());
                $stats['branches']++;
            }
            if($itr->getTagsFilter() !== '') {
                foreach ($repository->getTaggedVersions() as $tag => $version) {
                    // Load new versions
                    if (!$db->GetOne("SELECT itpTranslatable FROM IntegratedTranslatablePlaces WHERE (itpPackage = ?) AND (itpVersion = ?)", array($itr->getPackageHandle(), $version))) {
                        $repository->checkout("tags/$tag");
                        $stats = self::parseRepositoryDirectory($stats, $tsh, $repository->getDirectory(), $itr->getPackageHandle(), $version, $itr->getWebRoot());
                        $stats['branches']++;
                    }
                }
            }
            $stats['repositories']++;
        }

        return implode(
            "<br>",
            array(
                t('Repositories analyzed: %d', $stats['repositories']),
                t('Branches analyzed: %d', $stats['branches']),
                t('Found strings: %d', $stats['total']),
                t('Updated strings: %d', $stats['updated']),
                t('New strings: %d', $stats['added']),
            )
        );
    }
    /**
     * Parse a core directory containing
     * @param array|null $stats
     * @param TranslationsHelper $tsh
     * @param string $directory
     * @param string $packageHandle
     * @param string $version
     * @param string $webRoot;
     * @throws Exception
     * @return array Same result of TranslationsHelper::saveTranslatables
     * @see TranslationsHelper::saveTranslatables
     */
    private static function parseRepositoryDirectory($stats, $tsh, $directory, $packageHandle, $version, $webRoot)
    {
        if ($webRoot !== '') {
            $directory = "$directory/$webRoot";
        }
        if($packageHandle === '_') {
            if (!is_file("$directory/index.php") && is_file("$directory/concrete/dispatcher.php")) {
                throw new Exception(t('Unable to find the core web root in the directory %s', $directory));
            }
        } else {
            if (!is_file("$directory/controller.php")) {
                throw new Exception(t('Unable to find package controller in the directory %s', $directory));
            }
        }
        $translations = new \Gettext\Translations();
        \C5TL\Parser::clearCache();
        foreach (\C5TL\Parser::getAllParsers() as $parser) {
            if ($parser->canParseDirectory()) {
                $parser->parseDirectory($directory, '', $translations);
            }
        }
        \C5TL\Parser::clearCache();
        $statsThis = $tsh->saveTranslatables($translations, $packageHandle, $version);
        if (is_array($stats)) {
            $result = $stats;
            array_walk(
                $statsThis,
                function ($num, $key) use ($stats, &$result) {
                    $result[$key] = isset($result[$key]) ? ($result[$key] + $num) : $num;
                }
            );
        } else {
            $result = $statsThis;
        }

        return $result;
    }
}
