<?php

/**
 * Manage packages translations for packages.
 */
class IntegratedTranslatedRepository
{
    /**
     * Record identifier.
     *
     * @var int
     */
    protected $id;
    /**
     * Returns the record identifier.
     *
     * @return int
     */
    public function getID()
    {
        return $this->id;
    }
    /**
     * Repository shown name.
     *
     * @var string
     */
    protected $name;
    /**
     * Returns the repository shown name.
     *
     * @var string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * Package handle ('_' for the core versions).
     *
     * @var string
     */
    protected $packageHandle;
    /**
     * Returns the package handle ('_' for the core versions).
     *
     * @var string
     */
    public function getPackageHandle()
    {
        return $this->packageHandle;
    }
    /**
     * Repository URI.
     *
     * @var string
     */
    protected $uri;
    /**
     * Returns the repository URI.
     *
     * @var string
     */
    public function getUri()
    {
        return $this->uri;
    }
    /**
     * Name of the development branch.
     *
     * @var string
     */
    protected $developBranch;
    /**
     * Returns the name of the development branch.
     *
     * @var string
     */
    public function getDevelopBranch()
    {
        return $this->developBranch;
    }
    /**
     * Key for the development branch.
     *
     * @var string
     */
    protected $developKey;
    /**
     * Returns the key for the development branch.
     *
     * @var string
     */
    public function getDevelopKey()
    {
        return $this->developKey;
    }
    /**
     * Filter for the tagged versions (eg '=> 5.7').
     *
     * @var string
     */
    protected $tagsFilter;
    /**
     * Returns the filter for the tagged versions (eg '=> 5.7').
     *
     * @var string
     */
    public function getTagsFilter()
    {
        return $this->tagsFilter;
    }
    /**
     * Extracts the info for tags filter.
     *
     * @return null|array('operator' => string, 'version' => string)
     */
    public function getTagsFilterExpanded()
    {
        $result = null;
        $m = null;
        if (preg_match('/^\s*([<>=]+)\s*(\d+(?:\.\d+)?)\s*$/', $this->getTagsFilter(), $m)) {
            switch ($m[1]) {
                case '<=':
                case '<':
                case '=':
                case '>=':
                case '>':
                    $result = array(
                        'operator' => $m[1],
                        'version' => $m[2],
                    );
                    break;
            }
        }

        return $result;
    }
    /**
     * Include this repository in the automated job that extracts translations?
     *
     * @var bool
     */
    protected $inAutomatedJob;
    /**
     * Returns true if this repository is included in the automated job that extracts translations.
     *
     * @var bool
     */
    public function getInAutomatedJob()
    {
        return $this->inAutomatedJob;
    }
    /**
     * Relative path to the web root folder.
     *
     * @var string
     */
    protected $webRoot;
    /**
     * Returns the relative path to the web root folder.
     *
     * @return string
     */
    public function getWebRoot()
    {
        return $this->webRoot;
    }
    /**
     * Initializes the instance.
     *
     * @param array $data
     */
    private function __construct($row)
    {
        $this->id = intval($row['itrID']);
        $this->name = strval($row['itrName']);
        $this->packageHandle = strval($row['itrPackageHandle']);
        $this->uri = strval($row['itrUri']);
        $this->developBranch = strval($row['itrDevelopBranch']);
        $this->developKey = strval($row['itrDevelopKey']);
        $this->tagsFilter = strval($row['itrTagsFilter']);
        $this->inAutomatedJob = empty($row['itrInAutomatedJob']) ? false : true;
        $this->webRoot = trim(strval($row['itrWebRoot']), '/');
    }
    /**
     * Returns a list of IntegratedTranslatedRepository instances for the job that automatically extracts translatable strings.
     *
     * @return IntegratedTranslatedRepository[]
     */
    public static function getListForAutomatedJob()
    {
        $result = array();
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $rs = $db->Query('select * from IntegratedTranslatedRepositories where itrInAutomatedJob = 1');
        /* @var $rs ADORecordSet_mysql */
        while ($row = $rs->FetchRow()) {
            $result[] = new IntegratedTranslatedRepository($row);
        }
        $rs->Close();

        return $result;
    }
    /**
     * Find the IntegratedTranslatedRepository instance for a package version.
     *
     * @param string $packageHandle
     * @param string $packageVersion
     *
     * @return IntegratedTranslatedRepository|null
     */
    public static function getByPackageVersion($packageHandle, $packageVersion)
    {
        $result = null;
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $rs = $db->Query('select * from IntegratedTranslatedRepositories where itrPackageHandle = ?', array($packageHandle));
        /* @var $rs ADORecordSet_mysql */
        while ($row = $rs->FetchRow()) {
            $itr = new IntegratedTranslatedRepository($row);
            if(strpos($packageVersion, 'dev-') === 0) {
                if (($itr->getDevelopKey() !== '') && ($packageVersion === $itr->getDevelopKey())) {
                    $result = $itr;
                }
            } else {
                $tagsFilter = $itr->getTagsFilterExpanded();
                if (isset($tagsFilter)) {
                    if (version_compare($packageVersion, $tagsFilter['version'], $tagsFilter['operator'])) {
                        $result = $itr;
                    }
                }
            }
            if (isset($result)) {
                break;
            }
        }
        $rs->Close();

        return $result;
    }
    /**
     * Builds a GitRepository instance.
     *
     * @return GitRepository
     */
    public function getGitRepository()
    {
        Loader::model('git_repository', 'integrated_localization');

        return new GitRepository($this->getUri(), $this->getDevelopBranch(), $this->getTagsFilterExpanded());
    }
    /**
     * Returns the reference patterns for the files associated to this repository.
     *
     * @param string $packageHandle
     * @param string $packageVersion
     *
     * @return null|array('file' => string, 'file_line' => string)
     */
    public static function getReferencePatterns($packageHandle, $packageVersion)
    {
        $result = null;
        $itr = static::getByPackageVersion($packageHandle, $packageVersion);
        if (isset($itr)) {
            $m = null;
            $subPath = ($itr->getWebRoot() === '') ? '' : ('/'.$itr->getWebRoot());
            if (preg_match('%^(https?://github\.com/[^?/]+/[^?/]+)/?$%i', $itr->getUri(), $m)) {
                $baseUrl = $m[1];
                if (preg_match('%^(.+)\.git$%', $baseUrl, $m)) {
                    $baseUrl = $m[1];
                }
                if ($packageVersion === $itr->getDevelopKey()) {
                    $result = array(
                        'file' => $m[1].'/blob/'.$itr->getDevelopBranch().$subPath.'/[[FILE]]',
                        'file_line' => $m[1].'/blob/'.$itr->getDevelopBranch().$subPath.'/[[FILE]]#L[[LINE]]',
                    );
                } else {
                    $result = array(
                        'file' => $m[1].'/blob/'.$packageVersion.$subPath.'/[[FILE]]',
                        'file_line' => $m[1].'/blob/'.$packageVersion.$subPath.'/[[FILE]]#L[[LINE]]',
                    );
                }
            }
        }

        return $result;
    }
}
