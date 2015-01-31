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
        $tsh = Loader::helper('translations_source', 'integrated_localization');
        /* @var $tsh TranslationsSourceHelper */
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        Loader::model('git_repository', 'integrated_localization');
        $stats = array(
            'repositories' => 0,
            'branches' => 0,
        );
        $repositories = array();
        $repositories['dev-5.6'] = new GitRepository('https://github.com/concrete5/concrete5.git', 'master', '< 5.7');
        $repositories['dev-5.7'] = new GitRepository('https://github.com/concrete5/concrete5-5.7.0.git', 'develop', '>= 5.7');
        foreach ($repositories as $devVersion => $repository) {
            /* @var $repository GitRepository */
            // Fetch latest version and switch to HEAD
            $repository->update();
            $stats = self::parseCoreDirectory($stats, $tsh, $repository->getDirectory(), $devVersion);
            $stats['branches']++;
            foreach ($repository->getTaggedVersions() as $tag => $version) {
                // Load new versions
                if (!$db->GetOne("SELECT itpTranslatable FROM IntegratedTranslatablePlaces WHERE (itpPackage = '-') AND (itpVersion = ?)", array($version))) {
                    $repository->checkout("tags/$tag");
                    $stats = self::parseCoreDirectory($stats, $tsh, $repository->getDirectory(), $version);
                    $stats['branches']++;
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
     * @param TranslationsSourceHelper $tsh
     * @param string $directory
     * @param string $version
     * @throws Exception
     * @return array Same result of TranslationsSourceHelper::importTranslatables
     * @see TranslationsSourceHelper::importTranslatables
     */
    private static function parseCoreDirectory($stats, $tsh, $directory, $version)
    {
        /* @var $tsh TranslationsSourceHelper */
        if (!(is_file("$directory/index.php") && is_file("$directory/concrete/dispatcher.php"))) {
            $directory2 = "$directory/web";
            if (is_file("$directory2/index.php") && is_file("$directory2/concrete/dispatcher.php")) {
                $directory = $directory2;
            } else {
                throw new Exception(t('Unable to find the core web root in the directory %s', $directory));
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
        $statsThis = $tsh->importTranslatables($translations, '-', $version);
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
