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
     * @param string $id
     * @param string $name
     * @return string
     */
    private static function getAdministratorsGroupNameFor($id, $name)
    {
        return sprintf('Locale administrators for %s', $id);
    }
    /**
     * @return string
     */
    public function getAdministratorsGroupName()
    {
        return self::getAdministratorsGroupNameFor($this->getID(), $this->getName());
    }
    /**
     * @param string $id
     * @param string $name
     * @return string
     */
    private static function getTranslatorsGroupNameFor($id, $name)
    {
        return sprintf('Locale translators for %s', $id);
    }
    /**
     * @return string
     */
    public function getTranslatorsGroupName()
    {
        return self::getTranslatorsGroupNameFor($this->getID(), $this->getName());
    }
    /**
     * @param string $id
     * @param string $name
     * @return string
     */
    private static function getAspirantTranslatorsGroupNameFor($id, $name)
    {
        return sprintf('Aspirant locale translators for %s', $id);
    }
    /**
     * @return string
     */
    public function getAspirantTranslatorsGroupName()
    {
        return self::getAspirantTranslatorsGroupNameFor($this->getID(), $this->getName());
    }
    /**
     * @param string $id
     * @param string $name
     * @return string
     */
    private static function getAdministratorsGroupDescriptionFor($id, $name)
    {
        return sprintf('Administrators for the locale %s', $name);
    }
    /**
     * @return string
     */
    public function getAdministratorsGroupDescription()
    {
        return self::getAdministratorsGroupDescriptionFor($this->getID(), $this->getName());
    }
    /**
     * @param string $id
     * @param string $name
     * @return string
     */
    private static function getTranslatorsGroupDescriptionFor($id, $name)
    {
        return sprintf('Translators for the locale %s', $name);
    }
    /**
     * @return string
     */
    public function getTranslatorsGroupDescription()
    {
        return self::getTranslatorsGroupDescriptionFor($this->getID(), $this->getName());
    }
    /**
     * @param string $id
     * @param string $name
     * @return string
     */
    private static function getAspirantTranslatorsGroupDescriptionFor($id, $name)
    {
        return sprintf('Aspirant translators for the locale %s', $name);
    }
    /**
     * @return string
     */
    public function getAspirantTranslatorsGroupDescription()
    {
        return self::getAspirantTranslatorsGroupDescriptionFor($this->getID(), $this->getName());
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
     * return Group|null
     */
    public function getAspirantTranslatorsGroup()
    {
        $group = Group::getByName($this->getAspirantTranslatorsGroupName());
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
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $db->Execute('START TRANSACTION');
        try {
            $g = $this->getAdministratorsGroup();
            if (!$g) {
                Group::add($this->getAdministratorsGroupName(), $this->getAdministratorsGroupDescription());
            }
            $g = $this->getTranslatorsGroup();
            if (!$g) {
                Group::add($this->getTranslatorsGroupName(), $this->getTranslatorsGroupDescription());
            }
            $g = $this->getAspirantTranslatorsGroup();
            if (!$g) {
                Group::add($this->getAspirantTranslatorsGroupName(), $this->getAspirantTranslatorsGroupDescription());
            }
            $db->Execute('UPDATE IntegratedLocales SET ilApproved = 1 WHERE ilID = ? LIMIT 1', array($this->getID()));
            $db->Execute('COMMIT');
        } catch (Exception $x) {
            try {
                $db->Execute('ROLLBACK');
            } catch (Exception $foo) {
            }
            throw $x;
        }
    }
    /**
     *
     */
    public function delete()
    {
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $db->Execute('START TRANSACTION');
        try {
            $g = $this->getAdministratorsGroup();
            if ($g) {
                $g->delete();
            }
            $g = $this->getTranslatorsGroup();
            if ($g) {
                $g->delete();
            }
            $g = $this->getAspirantTranslatorsGroup();
            if ($g) {
                $g->delete();
            }
            $db->Execute('DELETE FROM IntegratedTranslations WHERE itLocale = ?', array($this->getID()));
            $db->Execute('DELETE FROM IntegratedLocales WHERE ilID = ? LIMIT 1', array($this->getID()));
            $db->Execute('COMMIT');
        } catch (Exception $x) {
            try {
                $db->Execute('ROLLBACK');
            } catch (Exception $foo) {
            }
            throw $x;
        }
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
    /**
     * @param array $newInfo
     */
    public function update($newInfo)
    {
        if (!is_array($newInfo)) {
            $newInfo = array();
        }
        if (isset($newInfo['id'])) {
            $id = $newInfo['id'];
            if ((!is_string($id)) || ($id !== trim($id)) || ($id === '')) {
                throw new Exception(t('Invalid locale identifier'));
            }
        } else {
            $id = $this->getID();
        }
        if (isset($newInfo['name'])) {
            $name = $newInfo['name'];
            if ((!is_string($name)) || ($name !== trim($name)) || ($name === '')) {
                throw new Exception(t('Invalid locale name'));
            }
        } else {
            $name = $this->getName();
        }
        if (isset($newInfo['pluralCount'])) {
            $pluralCount = $newInfo['pluralCount'];
            if (!is_int($pluralCount)) {
                if (is_string($pluralCount) && is_numeric($pluralCount)) {
                    $pluralCount = @intval($pluralCount);
                }
            }
            if ((!is_int($pluralCount)) || ($pluralCount < 1) || ($pluralCount > 6)) {
                throw new Exception(t('Invalid plural count'));
            }
        } else {
            $pluralCount = $this->getPluralCount();
        }
        if (isset($newInfo['pluralRule'])) {
            $pluralRule = $newInfo['pluralRule'];
            if ((!is_string($pluralRule)) || ($pluralRule !== trim($pluralRule)) || ($pluralRule === '')) {
                throw new Exception(t('Invalid plural rule'));
            }
        } else {
            $pluralRule = $this->getPluralRule();
        }
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $db->Execute('START TRANSACTION');
        try {
            if (($id !== $this->getID()) || ($name !== $this->getName())) {
                $g = $this->getAdministratorsGroup();
                if ($g) {
                    $g->update(self::getAdministratorsGroupNameFor($id, $name), self::getAdministratorsGroupDescriptionFor($id, $name));
                }
                $g = $this->getTranslatorsGroup();
                if ($g) {
                    $g->update(self::getTranslatorsGroupNameFor($id, $name), self::getTranslatorsGroupDescriptionFor($id, $name));
                }
                $g = $this->getAspirantTranslatorsGroup();
                if ($g) {
                    $g->update(self::getAspirantTranslatorsGroupNameFor($id, $name), self::getAspirantTranslatorsGroupDescriptionFor($id, $name));
                }
            }
            if ($pluralCount !== $this->getPluralCount()) {
                $db->Execute("DELETE FROM IntegratedTranslations WHERE (itLocale = ?) AND (itText1 IS NOT NULL) AND (itText1 <> '')", array($this->getID()));
            }
            if ($id !== $this->getID()) {
                $db->Execute("UPDATE IntegratedTranslations SET itLocale = ? WHERE itLocale = ?", array($id, $this->getID()));
            }
            $db->Execute(
                '
                    UPDATE IntegratedLocales SET
                        ilID = ?,
                        ilName = ?,
                        ilPluralCount = ?,
                        ilPluralRule = ?
                    WHERE
                        ilID = ?
                    LIMIT 1
                ',
                array(
                    $id,
                    $name,
                    $pluralCount,
                    $pluralRule,
                    $this->getID(),
                )
            );
            $db->Execute('COMMIT');
        } catch (Exception $x) {
            try {
                $db->Execute('ROLLBACK');
            } catch (Exception $foo) {
            }
            throw $x;
        }
        $this->id = $id;
        $this->name = $name;
        $this->pluralCount = $pluralCount;
        $this->pluralRule = $pluralRule;
    }
}
