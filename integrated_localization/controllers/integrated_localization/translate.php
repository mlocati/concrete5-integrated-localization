<?php defined('C5_EXECUTE') or die('Access Denied.');

class IntegratedLocalizationTranslateController extends Controller
{
    private function addTabs($section)
    {
        $hh = Loader::helper('html');
        /* @var $hh HtmlHelper */
        $this->addHeaderItem($hh->css('tabs.css', 'integrated_localization'));
        $this->addHeaderItem($hh->css('progress.css', 'integrated_localization'));
        $this->set('tab', $section);
    }
    public function view()
    {
        $this->redirect('/integrated_localization/translate/core_development');
    }
    public function core_development($selectedLocale = '*auto*', $selectedVersion = '')
    {
        $this->addTabs(__FUNCTION__);
        $locales = $this->getLocales($selectedLocale);
        $this->set('locales', $locales);
        $this->loadCoreVersions(true);
        if (($selectedLocale !== '*auto*') && isset($locales['selected']) && is_string($selectedVersion) && ($selectedVersion !== '')) {
            $selectedVersion = 'dev-'.$selectedVersion;
            $this->checkPackageVersion('-', $selectedVersion);
            $this->set('selectedVersion', $selectedVersion);
        }
    }
    public function core_releases($selectedLocale = '*auto*', $selectedVersion = '')
    {
        $this->addTabs(__FUNCTION__);
        $locales = $this->getLocales($selectedLocale);
        $this->set('locales', $locales);
        $this->loadCoreVersions(false);
        if (($selectedLocale !== '*auto*') && isset($locales['selected']) && is_string($selectedVersion) && ($selectedVersion !== '')) {
            $this->checkPackageVersion('-', $selectedVersion);
            $this->set('selectedVersion', $selectedVersion);
        }
    }
    public function packages($selectedLocale = '*auto*', $selectedPackage = '', $selectedVersion = '')
    {
        $this->addTabs(__FUNCTION__);
        $locales = $this->getLocales($selectedLocale);
        $this->set('locales', $locales);
        if (($selectedLocale !== '*auto*') && isset($locales['selected']) && is_string($selectedPackage) && ($selectedPackage !== '') && is_string($selectedVersion) && ($selectedVersion !== '')) {
            $this->checkPackageVersion($selectedPackage, $selectedVersion);
            $this->set('selectedPackage', $selectedPackage);
            $this->set('selectedVersion', $selectedVersion);
        }
    }
    private function loadCoreVersions($dev)
    {
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $list = array();
        foreach ($db->GetCol("SELECT DISTINCT itpVersion FROM IntegratedTranslatablePlaces WHERE (itpPackage = '-') AND (itpVersion ".($dev ? '' : 'NOT ')."LIKE 'dev-%')") as $v) {
            if ($dev) {
                $list[$v] = substr($v, 4);
            } else {
                $list[$v] = $v;
            }
        }
        uasort($list, 'version_compare');
        $list = array_reverse($list, true);
        $versions = array();
        $n = count($list);
        $i = 0;
        foreach ($list as $real => $shown) {
            if ($dev) {
                $i++;
                if (($i > 1) && ($i === $n)) {
                    $name = t('Development version (up to the %s series)', $shown);
                } else {
                    $name = t('Development version (from the %s series)', $shown);
                }
            } else {
                $name = t('concrete5 %s', $shown);
            }
            $versions[$real] = $name;
        }
        $this->set('versions', $versions);
    }
    private function getLocales($selectedLocale = '*auto*')
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $result = array(
            'all' => IntegratedLocale::getList(),
            'mine' => array(),
            'notMine' => array(),
        );
        if (User::isLoggedIn()) {
            $th = Loader::helper('translators', 'integrated_localization');
            /* @var $th TranslatorsHelper */
            $me = new User();
            foreach ($result['all'] as $locale) {
                if ($th->getUserAccess($me, $locale) >= TranslatorAccess::LOCALE_TRANSLATOR) {
                    $result['mine'][] = $locale;
                } else {
                    $result['notMine'][] = $locale;
                }
            }
        }
        $locale = null;
        if (is_object($selectedLocale)) {
            $locale = $selectedLocale;
        } elseif (is_string($selectedLocale) && ($selectedLocale !== '')) {
            if ($selectedLocale === '*auto*') {
                if (count($result['mine']) > 0) {
                    $locale = $result['mine'][0];
                }
            } else {
                foreach ($result['all'] as $l) {
                    if ($l->getID() === $selectedLocale) {
                        $locale = $l;
                        break;
                    }
                }
                if (!isset($locale)) {
                    throw new Exception(t("Unable to find the locale with id '%s'", $selectedLocale));
                }
            }
        }
        $result['selected'] = $locale;

        return $result;
    }
    public function getVersionStats($package, $version, IntegratedLocale $locale)
    {
        $result = array(
            'total' => 0,
            'translated' => 0,
            'progress' => 0,
        );
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $n = $db->GetOne(
            '
                SELECT COUNT(itpTranslatable)
                FROM IntegratedTranslatablePlaces
                WHERE (itpPackage = ?) AND (itpVersion = ?)
            ',
            array($package, $version)
        );
        if ($n) {
            $result['total'] = intval($n);
        }
        if ($result['total'] > 0) {
            $n = $db->GetOne(
                '
                    SELECT COUNT(*)
                    FROM IntegratedTranslatablePlaces
                    INNER JOIN IntegratedTranslations ON IntegratedTranslatablePlaces.itpTranslatable = IntegratedTranslations.itTranslatable
                    WHERE (IntegratedTranslatablePlaces.itpPackage = ?) AND (IntegratedTranslatablePlaces.itpVersion = ?) AND (IntegratedTranslations.itLocale = ?)
                ',
                array($package, $version, $locale->getID())
            );
            if ($n) {
                $result['translated'] = intval($n);
                if ($result['translated'] === $result['total']) {
                    $result['progress'] = 100;
                } else {
                    $p = round($result['translated'] * 100 / $result['total']);
                    if ($p < 1) {
                        $p = 1;
                    } elseif ($p == 100) {
                        $p = 99;
                    }
                    $result['progress'] = $p;
                }
            }
        }

        return $result;
    }
    private function checkPackageVersion($package, $version)
    {
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        if (!$db->GetOne(
            '
                SELECT itpTranslatable
                FROM IntegratedTranslatablePlaces
                WHERE (itpPackage = ?) AND (itpVersion = ?)
            ',
            array($package, $version)
        )) {
            throw new Exception(t('The package "%1$s" does not have a version "%2$s"', $package, $version));
        }
    }
    public function search_package($localeID)
    {
        try {
            Loader::model('integrated_locale', 'integrated_localization');
            $locale = IntegratedLocale::getByID($localeID);
            if (!isset($locale)) {
                throw new Exception(t('Invalid locale identifier: %s', $localeID));
            }
            $q = $this->get('q');
            $q = is_string($q) ? trim(preg_replace('/\s+/', ' ', $q)) : '';
            if (!preg_match('/[0-9a-z]{4,}/i', $q)) {
                throw new Exception(t('Please be more specific...'));
            }
            $qReal = strtolower(str_replace(' ', '_', $q));
            $result = array();
            if (preg_match('/^[a-z0-9_]+$/', $qReal)) {
                $db = Loader::db();
                /* @var $db ADODB_mysql */
                $rs = $db->Query(
                    'SELECT DISTINCT itpPackage, itpVersion FROM IntegratedTranslatablePlaces WHERE IntegratedTranslatablePlaces.itpPackage LIKE ? ORDER BY itpPackage',
                    array('%'.$qReal.'%')
                );
                /* @var $rs ADORecordSet_mysql */
                while ($row = $rs->FetchRow()) {
                    if (!isset($result[$row['itpPackage']])) {
                        $result[$row['itpPackage']] = array(
                            'name' => ucwords(str_replace('_', ' ', $row['itpPackage'])),
                            'versions' => array(),
                        );
                    }
                    $result[$row['itpPackage']]['versions'][] = $row['itpVersion'];
                }
                $rs->Close();
                if (!empty($result)) {
                    foreach (array_keys($result) as $package) {
                        $versions = $result[$package]['versions'];
                        usort($versions, 'version_compare');
                        $versions = array_reverse($versions);
                        $result[$package]['versions'] = array();
                        foreach ($versions as $version) {
                            $result[$package]['versions'][] = array(
                                'v' => $version,
                                'stats' => $this->getVersionStats($package, $version, $locale),
                            );
                        }
                    }
                }
            }
            header('Content-Type: text/plain; charset='.APP_CHARSET);
            echo empty($result) ? 'false' : json_encode($result);
        } catch (Exception $x) {
            header('400 Bad Request', true, 400);
            echo $x->getMessage();
        }
        die();
    }
}
