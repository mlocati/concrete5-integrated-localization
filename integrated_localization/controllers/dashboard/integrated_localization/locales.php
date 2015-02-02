<?php defined('C5_EXECUTE') or die('Access Denied.');

class DashboardIntegratedLocalizationLocalesController extends Controller
{
    public function on_start()
    {
        $th = Loader::helper('translators', 'integrated_localization');
        /* @var $th TranslatorsHelper */
        if($th->getCurrentUserAccess() !== 'admin') {
            $v = View::getInstance();
            /* @var $v View */
            $v->render('/page_forbidden');
            die();
        }
    }

    public function view()
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locales = IntegratedLocale::getList(true, true);
        $this->set('locales', $locales);
    }
    public function approve($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID, true);
        if ($locale) {
            $locale->approve();
            $this->redirect('/dashboard/integrated_localization/locales', 'approved', $locale->getName());
        } else {
            $th = Loader::helper('text');
            /* @var $th TextHelper */
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }
    public function approved($localeName)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        $this->set('message', t("The locale '%s' has been approved", $th->specialchars($localeName)));
        $this->view();
    }
    public function delete($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID, true);
        if ($locale) {
            $locale->delete();
            $this->redirect('/dashboard/integrated_localization/locales', 'deleted', $locale-getName());
        } else {
            $th = Loader::helper('text');
            /* @var $th TextHelper */
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }
    public function deleted($localeName)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        $this->set('message', t("The request to adopt the locale '%s' has been denied", $th->specialchars($localeName)));
        $this->view();
    }
    public function edit($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID, true);
        if ($locale) {
            $this->set('editing', $locale);
            $languages = \Gettext\Utils\Locales::getLanguages(true, true);
            natcasesort($languages);
            $this->set('languages', $languages);
            $countries = \Gettext\Utils\Locales::getTerritories(true, true);
            natcasesort($countries);
            $this->set('countries', $countries);
        } else {
            $th = Loader::helper('text');
            /* @var $th TextHelper */
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }
    public function save($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID, true);
        if ($locale) {
            try {
                $languageID = $this->post('language');
                if ((!is_string($languageID)) || ($languageID === '')) {
                    throw new Exception(t('Please specify the language'));
                }
                $languages = \Gettext\Utils\Locales::getLanguages(true, true);
                if (!isset($languages[$languageID])) {
                    throw new Exception(t('Invalid language identifier: %s', $languageID));
                }
                $countryID = $this->post('country');
                if (!is_string($country)) {
                    $country = '';
                }
                if ($countryID !== '') {
                    $countries = \Gettext\Utils\Locales::getTerritories(true, true);
                    if (!isset($countries[$countryID])) {
                        throw new Exception(t('Invalid country identifier: %s', $countryID));
                    }
                }
                $id = $languageID.(($countryID === '') ? '' : "_$countryID");
                if ($this->post('auto_name') === '1') {
                    $name = $languages[$languageID];
                    if ($countryID !== '') {
                        $name .= ' ('.$countries[$countryID].')';
                    }
                } else {
                    $s = $this->post('name');
                    $name = is_string($s) ? trim($s) : '';
                    if ($name === '') {
                        throw new Exception(t('Please specify the locale name'));
                    }
                }
                if ($this->post('auto_plural') === '1') {
                    $pluralInfo = \Gettext\Utils\Locales::getLocaleInfo($id);
                    if (!isset($pluralInfo)) {
                        $_POST['auto_plural'] = '';
                        throw new Exception(t('Unable to automatically determine the plurals info. Please specify them manually.'));
                    }
                    $pluralCount = $pluralInfo['plurals'];
                    $pluralRule = $pluralInfo['pluralRule'];
                } else {
                    $s = $this->post('pluralCount');
                    $s = is_string($s) ? preg_replace('/\D/', '', $s) : '';
                    $pluralCount = ($s === '') ? null : intval($s);
                    if ((!isset($pluralCount)) || ($pluralCount < 1) || ($pluralCount > 6)) {
                        throw new Exception(t('Please specify the plural count (between %1$d and %2$d).', 1, 6));
                    }
                    $s = $this->post('pluralRule');
                    $pluralRule = is_string($s) ? trim($s) : '';
                    if ($pluralRule === '') {
                        throw new Exception(t('Please specify the plural rule'));
                    }
                }
                if ($id !== $locale->getID()) {
                    if (IntegratedLocale::getByID($id, true, true)) {
                        throw new Exception(t('Another locale with id "%s" is already defined', $id));
                    }
                }
                $locale->update(array(
                    'id' => $id,
                    'name' => $name,
                    'pluralCount' => $pluralCount,
                    'pluralRule' => $pluralRule,
                ));
                $this->redirect('/dashboard/integrated_localization/locales', 'saved', $locale->getName());
            } catch (Exception $x) {
                $th = Loader::helper('text');
                /* @var $th TextHelper */
                $this->set('error', nl2br($th->specialchars($x->getMessage())));
                $this->edit($locale->getID());
            }
        } else {
            $th = Loader::helper('text');
            /* @var $th TextHelper */
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }
    public function saved($localeName)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        $this->set('message', t("The locale '%s' has been saved", $th->specialchars($localeName)));
        $this->view();
    }
}
