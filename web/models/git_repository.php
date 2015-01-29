<?php defined('C5_EXECUTE') or die('Access Denied.');

class GitRepository
{
    protected $url;

    protected $branch;

    protected $tagsFilter;

    protected $directory;

    private function run($cmd)
    {
        static $execAvailable;
        if (!isset($execAvailable)) {
            $safeMode = @ini_get('safe_mode');
            if (!empty($safeMode)) {
                throw new Exception(t("Safe-mode can't be on"));
            }
            if (!function_exists('exec')) {
                throw new Exception(t("exec() function is missing"));
            }
            if (in_array('exec', array_map('trim', explode(',', strtolower(@ini_get('disable_functions')))))) {
                throw new Exception(t("exec() function is disabled"));
            }
            $execAvailable = true;
        }
        $rc = 1;
        $output = array();
        $previousDir = @getcwd();
        if ($previousDir === false) {
            throw new Exception(t('Unable to determine the current directory'));
        }
        if (@chdir($this->directory) !== true) {
            throw new Exception(t('Unable to change the current directory'));
        }
        @exec($cmd.' 2>&1', $output, $rc);
        @chdir($previousDir);
        if ($rc !== 0) {
            throw new Exception(t('Command failed with return code %1$s: %2$s', $rc, implode("\n", $output)));
        }

        return $output;
    }

    public function __construct($url, $branch, $tagsFilter = '')
    {
        $this->url = $url;
        $this->branch = $branch;
        $this->tagsFilter = null;
        if (is_string($tagsFilter) && preg_match('/^\s*([<>=]+)\s*(\d+(?:\.\d+)?)\s*$/', $tagsFilter, $m)) {
            switch ($m[1]) {
                case '<=':
                case '<':
                case '=':
                case '>=':
                case '>':
                    $this->tagsFilter = array('operator' => $m[1], 'version' => $m[2]);
                    break;
            }
        }
        $this->directory = str_replace(DIRECTORY_SEPARATOR, '/', Loader::helper('file')->getTemporaryDirectory()).'/localization/'.strtolower(trim(preg_replace('/[^\w\.]+/', '-', $url), '-'));
    }

    public function update()
    {
        if (!(is_dir($this->directory) && is_dir($this->directory.'/.git'))) {
            $this->initialize();
        } else {
            $this->run('git checkout '.escapeshellarg($this->branch));
            $this->run('git fetch origin');
            $this->run('git merge --ff-only '.escapeshellarg('origin/'.$this->branch));
        }
    }

    public function initialize()
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, DIRECTORY_PERMISSIONS_MODE, true);
            if (!is_dir($this->directory)) {
                throw new Exception(t('Unable to create the directory %s', $this->directory));
            }
        }
        if (!is_dir($this->directory.'/.git')) {
            try {
                $this->run('git clone --quiet --branch '.escapeshellarg($this->branch).' '.escapeshellarg($this->url).' .');
            } catch (Exception $x) {
                Loader::helper('file_extended', 'integrated_localization')->deleteFromFileSystem($this->directory);
                throw $x;
            }
        }
    }
    public function getTaggedVersions()
    {
        $taggedVersions = array();
        foreach ($this->run('git tag --list') as $tag) {
            $version = trim($tag);
            if (preg_match('/^v\.?\s*(\d.+)$/', $version, $m)) {
                $version = $m[1];
            }
            if (preg_match('/^\d+(\.\d+)+$/', $version)) {
                if ((!isset($this->tagsFilter)) || version_compare($version, $this->tagsFilter['version'], $this->tagsFilter['operator'])) {
                    $taggedVersions[$tag] = $version;
                }
            }
        }

        return $taggedVersions;
    }
    public function getDirectory()
    {
        return $this->directory;
    }
    public function checkout($to)
    {
        $this->run('git checkout '.escapeshellarg($to));
    }
}
