<?php defined('C5_EXECUTE') or die('Access Denied.');

class IntegratedLocalizationLocalesController extends Controller
{
    public function on_start()
    {
        $th = Loader::helper('translators', 'integrated_localization');
        /* @var $th TranslatorsHelper */
        if ($th->getCurrentUserAccess() < TranslatorAccess::GLOBAL_ADMINISTRATOR) {
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
            $this->redirect('/integrated_localization/locales', 'approved', $locale->getName());
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
            $name = $locale->getName();
            $locale->delete();
            $this->redirect('/integrated_localization/locales', 'deleted', $name);
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
    private static function getLanguages()
    {
        $languages = array();
        foreach (\Gettext\Languages\Language::getAll(true, true) as $language) {
            if (strpos($language->id, '_') === false) {
                $languages[$language->id] = $language->name;
            }
        }
        natcasesort($languages);

        return $languages;
    }
    private static function getCountries()
    {
        $countries = array();
        foreach (\Gettext\Languages\CldrData::getTerritoryNames() as $territoryID => $territoryName) {
            if (preg_match('/^[A-Z][A-Z]$/', $territoryID)) {
                $countries[$territoryID] = $territoryName;
            }
        }
        natcasesort($countries);

        return $countries;
    }
    public function edit($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID, true);
        if ($locale) {
            $this->set('editing', $locale);
            $this->set('languages', self::getLanguages());
            $this->set('countries', self::getCountries());
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
                $languages = self::getLanguages();
                if (!isset($languages[$languageID])) {
                    throw new Exception(t('Invalid language identifier: %s', $languageID));
                }
                $countryID = $this->post('country');
                if (!is_string($countryID)) {
                    $countryID = '';
                }
                if ($countryID !== '') {
                    $countries = self::getCountries();
                    if (!isset($countries[$countryID])) {
                        throw new Exception(t('Invalid country identifier: %s', $countryID));
                    }
                }
                $builtID = $languageID.(($countryID === '') ? '' : "_$countryID");
                $languageInfo = \Gettext\Languages\Language::getById($builtID);
                if (!isset($languageInfo)) {
                    $_POST['auto_plural'] = '';
                    throw new Exception(t('Invalid language and/or country.'));
                }
                if ($this->post('auto_name') === '1') {
                    $name = $languageInfo->name;
                } else {
                    $s = $this->post('name');
                    $name = is_string($s) ? trim($s) : '';
                    if ($name === '') {
                        throw new Exception(t('Please specify the locale name'));
                    }
                }
                $pluralCases = array();
                if ($this->post('auto_plural') === '1') {
                    $pluralFormula = $languageInfo->formula;
                    foreach ($languageInfo->categories as $category) {
                        $pluralCases[$category->id] = $category->formula;
                    }
                } else {
                    $s = $this->post('pluralFormula');
                    $pluralFormula = is_string($s) ? trim($s) : '';
                    if ($pluralFormula === '') {
                        throw new Exception(t('Please specify the plurals formula'));
                    }
                    foreach (array(
                            'zero' => t('Zero'),
                            'one' => t('One'),
                            'two' => t('Two'),
                            'few' => t('Few'),
                            'many' => t('Many'),
                            'other' => t('Other'),
                    ) as $pluralCaseID => $pluralCaseName) {
                        $checked = $this->post("pluralcase-$pluralCaseID");
                        if (empty($checked)) {
                            if ($pluralCaseID === 'other') {
                                throw new Exception(t("The plural case '%s' must be specified", $pluralCaseID));
                            }
                        } else {
                            $pluralCases[$pluralCaseID] = $this->post("pluralcase-$pluralCaseID-examples");
                            $pluralCases[$pluralCaseID] = is_string($pluralCases[$pluralCaseID]) ? trim($pluralCases[$pluralCaseID]) : '';
                            if ($pluralCases[$pluralCaseID] === '') {
                                throw new Exception(t("The examples for the plural case '%s' must be specified", $pluralCaseID));
                            }
                        }
                    }
                }
                if ($languageInfo->id !== $locale->getID()) {
                    if (IntegratedLocale::getByID($languageInfo->id, true, true)) {
                        throw new Exception(t('Another locale with id "%s" is already defined', $languageInfo->id));
                    }
                }
                $locale->update(array(
                    'id' => $languageInfo->id,
                    'name' => $name,
                    'pluralFormula' => $pluralFormula,
                    'pluralCases' => $pluralCases,
                ));
                $this->redirect('/integrated_localization/locales', 'saved', $locale->getName());
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
