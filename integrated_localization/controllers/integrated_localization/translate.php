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
        $this->set('locales', $this->getLocales($selectedLocale));
        $this->loadCoreVersions(true);
    }
    public function core_releases($selectedLocale = '*auto*', $selectedVersion = '')
    {
        $this->addTabs(__FUNCTION__);
        $this->set('locales', $this->getLocales($selectedLocale));
        $this->loadCoreVersions(false);
    }
    public function packages($selectedLocale = '*auto*')
    {
        $this->addTabs(__FUNCTION__);
        $this->set('locales', $this->getLocales($selectedLocale));
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
}
