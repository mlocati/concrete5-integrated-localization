<?php defined('C5_EXECUTE') or die('Access Denied.');

class IntegratedLocale
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var bool
     */
    private $isSource;
    /**
     * @var int
     */
    private $pluralCount;
    /**
     * @var string
     */
    private $pluralRule;
    /**
     * @var bool
     */
    private $approved;
    /**
     * @var int|null
     */
    private $requestedBy;
    /**
     * @var string
     */
    private $requestedOn;
    /**
     * @param array $row
     */
    private function __construct($row)
    {
        $this->id = $row['ilID'];
        $this->name = $row['ilName'];
        $this->isSource = empty($row['ilIsSource']) ? false : true;
        $this->pluralCount = (int) $row['ilPluralCount'];
        $this->pluralRule = $row['ilPluralRule'];
        $this->approved = empty($row['ilApproved']) ? false : true;
        $this->requestedBy = isset($row['ilRequestedBy']) ? ((int) $row['ilRequestedBy']) : null;
        $this->requestedOn = isset($row['ilRequestedOn']) ? $row['ilRequestedOn'] : '';
    }
    /**
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }
    /**
     * @return string
     */
    public function getLanguage()
    {
        $info = $this->splitID();

        return $info['language'];
    }
    /**
     * @return string
     */
    public function getScript()
    {
        $info = $this->splitID();

        return $info['script'];
    }
    /**
     * @return string
     */
    public function getTerritory()
    {
        $info = $this->splitID();

        return $info['territory'];
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @return bool
     */
    public function getIsSource()
    {
        return $this->isSource;
    }
    /**
     * @return int
     */
    public function getPluralCount()
    {
        return $this->pluralCount;
    }
    /**
     * @return string
     */
    public function getPluralRule()
    {
        return $this->pluralRule;
    }
    /**
     * @return bool
     */
    public function getApproved()
    {
        return $this->approved;
    }
    /**
     * @return int|null
     */
    public function getRequestedBy()
    {
        return $this->requestedBy;
    }
    /**
     * @return string
     */
    public function getRequestedOn()
    {
        return $this->requestedOn;
    }
    /**
     * @param string $localeID
     * @param bool $onlyIfApproved
     * @return IntegratedLocale|null
     */
    public static function getByID($localeID, $includeUnapproved = false, $includeSourceLocale = false)
    {
        $w = array();
        $q = array();
        $w[] = '(ilID = ?)';
        $q[] = $localeID;
        if (!$includeUnapproved) {
            $w[] = '(ilApproved = 1)';
        }
        if (!$includeSourceLocale) {
            $w[] = '(ilIsSource = 0)';
        }
        $list = self::find(implode(' AND ', $w), $q);

        return empty($list) ? null : $list[0];
    }
    /**
     * @param bool $onlyApproved
     * @param bool $excludeSourceLocale
     * @return IntegratedLocale[]
     */
    public static function getList($includeUnapproved = false, $includeSourceLocale = false)
    {
        $w = array();
        $q = array();
        if (!$includeUnapproved) {
            $w[] = '(ilApproved = 1)';
        }
        if (!$includeSourceLocale) {
            $w[] = '(ilIsSource = 0)';
        }

        return self::find($w, $q);
    }
    /**
     * @param string $where
     * @param array $q
     * @return IntegratedLocale[]
     */
    protected static function find($where = '', $q = array())
    {
        $result = array();
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $sql = 'SELECT * FROM IntegratedLocales';
        if (is_string($where) && ($where !== '')) {
            $sql .= ' WHERE '.$where;
        }
        $sql .= ' ORDER BY ilName';
        $rs = $db->Query($sql, $q);
        /* @var $rs ADORecordSet_mysql */
        while ($row = $rs->FetchRow()) {
            $result[] = new IntegratedLocale($row);
        }
        $rs->Close();

        return $result;
    }
    /**
     * @return string
     */
    public function getAdministratorsGroupName()
    {
        return sprintf('Locale administrators for %s', $this->getID());
    }
    /**
     * @return string
     */
    public function getTranslatorsGroupName()
    {
        return sprintf('Locale translators for %s', $this->getID());
    }
    /**
     * @return string
     */
    public function getAdministratorsGroupDescription()
    {
        return sprintf('Administrators for the locale %s', $this->getName());
    }
    /**
     * @return string
     */
    public function getTranslatorsGroupDescription()
    {
        return sprintf('Translators for the locale %s', $this->getName());
    }
    /**
     * return Group|null
     */
    public function getAdministratorsGroup()
    {
        $group = Group::getByName($this->getAdministratorsGroupName());
        if (isset($group) && ($group->isError() || (!$group->getGroupID()))) {
            $group = null;
        }

        return $group;
    }
    /**
     * return Group|null
     */
    public function getTranslatorsGroup()
    {
        $group = Group::getByName($this->getTranslatorsGroupName());
        if (isset($group) && ($group->isError() || (!$group->getGroupID()))) {
            $group = null;
        }

        return $group;
    }
    /**
     *
     */
    public function approve()
    {
        $g = $this->getAdministratorsGroup();
        if (!$g) {
            Group::add($this->getAdministratorsGroupName(), $this->getAdministratorsGroupDescription());
        }
        $g = $this->getTranslatorsGroup();
        if (!$g) {
            Group::add($this->getTranslatorsGroupName(), $this->getTranslatorsGroupDescription());
        }
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $db->Execute('UPDATE IntegratedLocales SET ilApproved = 1 WHERE ilID = ? LIMIT 1', array($this->getID()));
    }
    /**
     *
     */
    public function delete()
    {
        $g = $this->getAdministratorsGroup();
        if ($g) {
            $g->delete();
        }
        $g = $this->getTranslatorsGroup();
        if ($g) {
            $g->delete();
        }
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $db->Execute('DELETE FROM IntegratedTranslations WHERE itLocale = ?', array($this->getID()));
        $db->Execute('DELETE FROM IntegratedLocales WHERE ilID = ? LIMIT 1', array($this->getID()));
    }
    /**
     * @return array
     */
    protected function splitID()
    {
        preg_match('/^([a-z]{2,3})(?:[_\-]([a-z]{4}))?(?:[_\-]([a-z]{2}|[0-9]{3}))?(?:$|[_\-])/i', $this->getID(), $matches);

        return array(
            'language' => strtolower($matches[1]),
            'script' => isset($matches[2]) ? ucfirst(strtolower($matches[2])) : '',
            'territory' => isset($matches[3]) ? strtoupper($matches[3]) : '',
        );
    }
    /**
     * @return int
     */
    public function getTotalPluralTranslations()
    {
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $n = $db->GetOne("SELECT COUNT(*) FROM IntegratedTranslations WHERE (itLocale = ?) AND (itText1 IS NOT NULL) AND (itText1 <> '')", array($this->getID()));

        return empty($n) ? 0 : (int) $n;
    }
}
