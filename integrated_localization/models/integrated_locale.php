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
     * @var string
     */
    private $pluralFormula;
    /**
     * @var array
     */
    private $pluralCases;
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
        $this->pluralFormula = $row['ilPluralFormula'];
        $this->pluralCases = self::unserializePluralCases($row['ilPluralCases']);
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
        return count($this->pluralCases);
    }
    /**
     * @return string
     */
    public function getPluralFormula()
    {
        return $this->pluralFormula;
    }
    /**
     * @return array
     */
    public function getPluralCases()
    {
        return $this->pluralCases;
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
     *
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
     *
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

        return self::find(implode(' AND ', $w), $q);
    }
    /**
     * @param string $where
     * @param array $q
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     * return Group|null.
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
     * return Group|null.
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
     * return Group|null.
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
        $matches = null;
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
     * @param array $pluralCases
     *
     * @throws Exception
     *
     * @return string
     */
    private static function serializePluralCases($pluralCases)
    {
        if ((!is_array($pluralCases)) || (!isset($pluralCases['other']))) {
            throw new Exception(t('Invalid plural cases'));
        }
        $lines = array();
        foreach ($pluralCases as $case => $examples) {
            switch($case) {
                case 'zero':
                case 'one':
                case 'two':
                case 'few':
                case 'many':
                case 'other':
                    $lines[] = "$case:$examples";
                    break;
                default:
                    throw new Exception(t('Invalid plural cases'));
            }
        }

        return implode("\n", $lines);
    }
    /**
     * @param string $pluralCases
     *
     * @throws Exception
     *
     * @return array
     */
    private static function unserializePluralCases($pluralCases)
    {
        if ((!is_string($pluralCases)) || ($pluralCases === '')) {
            throw new Exception(t('Invalid plural cases'));
        }
        $result = array();
        foreach (explode("\n", $pluralCases) as $line) {
            $line = trim($line);
            if ($line !== '') {
                list($case, $examples) = explode(':', $line, 2);
                switch($case) {
                    case 'zero':
                    case 'one':
                    case 'two':
                    case 'few':
                    case 'many':
                    case 'other':
                        $result[$case] = isset($examples) ? $examples : '';
                        break;
                    default:
                        throw new Exception(t('Invalid plural cases'));
                }
            }
        }
        if (!isset($result['other'])) {
            throw new Exception(t('Invalid plural cases'));
        }

        return $result;
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
        if (isset($newInfo['pluralFormula'])) {
            $pluralFormula = $newInfo['pluralFormula'];
            if ((!is_string($pluralFormula)) || ($pluralFormula !== trim($pluralFormula)) || ($pluralFormula === '')) {
                throw new Exception(t('Invalid plural rule'));
            }
        } else {
            $pluralFormula = $this->getPluralFormula();
        }
        if (isset($newInfo['pluralCases'])) {
            $pluralCases = $newInfo['pluralCases'];
        } else {
            $pluralCases = $this->getPluralCases();
        }
        $pluralCasesString = self::serializePluralCases($pluralCases);

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
            if (count($pluralCases) !== $this->getPluralCount()) {
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
                        ilPluralFormula = ?,
                        ilPluralCases = ?
                    WHERE
                        ilID = ?
                    LIMIT 1
                ',
                array(
                    $id,
                    $name,
                    $pluralFormula,
                    $pluralCasesString,
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
        $this->pluralFormula = $pluralFormula;
        $this->pluralCases = $pluralCases;
    }
    /**
     * @param string $id
     * @param string $name
     * @param string $pluralFormula
     * @param array $pluralCases
     * @param bool $approved
     *
     * @throws Exception
     *
     * @return IntegratedLocale
     */
    public static function add($id, $name, $pluralFormula, $pluralCases, $approved = false)
    {
        $w = 'INSERT INTO IntegratedLocales SET ';
        $q = array();
        $w .= ' ilID = ?';
        $q[] = $id;
        $w .= ', ilName = ?';
        $q[] = $name;
        $w .= ', ilPluralFormula = ?';
        $q[] = $pluralFormula;
        $w .= ', ilPluralCases = ?';
        $q[] = self::serializePluralCases($pluralCases);
        $w .= ', ilApproved = ?';
        $q[] = $approved ? 1 : 0;
        if (User::isLoggedIn()) {
            $user = new User();
            if ($user->getUserID()) {
                $w .= ', ilRequestedBy = ?';
                $q[] = $user->getUserID();
            }
        }
        $w .= ', ilRequestedOn = NOW()';
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $db->Execute('START TRANSACTION');
        try {
            $db->Execute($w, $q);
            $result = static::getByID($id, true);
            if (!isset($result)) {
                throw new Exception(t('Internal error'));
            }
            $db->Execute('COMMIT');
        } catch (Exception $x) {
            try {
                $db->Execute('ROLLBACK');
            } catch (Exception $foo) {
            }
            throw $x;
        }

        return $result;
    }
}
