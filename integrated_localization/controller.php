<?php defined('C5_EXECUTE') or die('Access Denied.');

class IntegratedLocalizationPackage extends Package
{
    protected $pkgHandle = 'integrated_localization';

    protected $appVersionRequired = '5.5.2';

    protected $pkgVersion = '0.1.0';

    public function getPackageName()
    {
        return t('Integrated Localization');
    }

    public function getPackageDescription()
    {
        return t('Manage core and package translations.');
    }

    public function install()
    {
        $pkg = parent::install();
        self::installOrUpgrade($pkg, '');
    }

    public function upgrade()
    {
        $fromVersion = $this->getPackageVersion();
        parent::upgrade();
        self::installOrUpgrade($this, $fromVersion);
    }

    private static function installOrUpgrade($pkg, $fromVersion)
    {
        self::on_start();
        Loader::model('job');
        Loader::model('page');
        Loader::model('single_page');
        Loader::model('block_type');
        Loader::model('group');
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        if ($fromVersion === '') {
            Loader::model('integrated_locale', 'integrated_localization');
            foreach (array(
                'en_US' => true,
                'ar' => false,
                'ast_ES' => false,
                'bg_BG' => false,
                'bs_BA' => false,
                'ca' => false,
                'cs_CZ' => false,
                'da_DK' => false,
                'de_DE' => false,
                'el_GR' => false,
                'en_GB' => false,
                'es_AR' => false,
                'es_ES' => false,
                'es_MX' => false,
                'es_PE' => false,
                'et_EE' => false,
                'fa_IR' => false,
                'fi_FI' => false,
                'fr_FR' => false,
                'he_IL' => false,
                'hi_IN' => false,
                'hr_HR' => false,
                'hu_HU' => false,
                'id_ID' => false,
                'it_IT' => false,
                'ja_JP' => false,
                'km_KH' => false,
                'ko_KR' => false,
                'ku' => false,
                'lt_LT' => false,
                'lv_LV' => false,
                'mk_MK' => false,
                'ml_IN' => false,
                'my_MM' => false,
                'nb_NO' => false,
                'nl_NL' => false,
                'pl_PL' => false,
                'pt_BR' => false,
                'pt_PT' => false,
                'ro_RO' => false,
                'ru_RU' => false,
                'sk_SK' => false,
                'sl_SI' => false,
                'sr_RS' => false,
                'sv_SE' => false,
                'ta_IN' => false,
                'th_TH' => false,
                'tr_TR' => false,
                'uk_UA' => false,
                'vi_VN' => false,
                'zh_CN' => false,
                'zh_TW' => false,
            ) as $localeID => $isSource) {
                $existing = IntegratedLocale::getByID($localeID, true, true);
                if (!isset($existing)) {
                    $language = \Gettext\Languages\Language::getById($localeID);
                    $pluralCases = array();
                    foreach ($language->categories as $category) {
                        $pluralCases[$category->id] = $category->examples;
                    }
                    IntegratedLocale::add(
                        $language->id,
                        $language->name,
                        $language->formula,
                        $pluralCases,
                        true
                    );
                    if ($isSource) {
                        $db->Execute("UPDATE IntegratedLocales SET ilIsSource = 1, ilRequestedBy = NULL, ilRequestedOn = NULL WHERE ilID = ? LIMIT 1", $language->id);
                    }
                }
            }
            if (!Job::getByHandle('fetch_git_translations')) {
                Job::installByPackage('fetch_git_translations', $pkg);
            }
        }
        $th = Loader::helper('translators', 'integrated_localization');
        /* @var $th TranslatorsHelper */
        $administrators = $th->getAdministratorsGroup();
        if (!$administrators) {
            $administrators = Group::add($th->getAdministratorsGroupName(), $th->getAdministratorsGroupDescription());
        }
        foreach (array(
            //'/integrated_localization' => array('name' => t('Integrated Localization'), 'description' => '', 'standardPage' => true),
            '/integrated_localization/locales' => array('name' => t('Locales'), 'description' => ''),
            '/integrated_localization/groups' => array('name' => t('Translators\' Groups'), 'description' => ''),
            '/integrated_localization/translate' => array('name' => t('Translate'), 'description' => ''),
            '/integrated_localization/translate/online' => array('name' => t('Translate Online'), 'description' => ''),
        ) as $path => $info) {
            $sp = Page::getByPath($path);
            /* @var $sp Page */
            if ((!is_object($sp)) || $sp->isError() || (!$sp->getCollectionID())) {
                if (isset($info['standardPage']) && $info['standardPage']) {
                    //Collection::add();
                } else {
                    $sp = SinglePage::add($path, $pkg);
                    if (is_object($sp)) {
                        $sp->update(array('cName' => $info['name'], 'cDescription' => $info['description']));
                    }
                }
            }
        }
        $bt = BlockType::getByHandle('package_languages_builder');
        if (!is_object($bt)) {
            BlockType::installBlockTypeFromPackage('package_languages_builder', $pkg);
        }
        if ($fromVersion === '') {
            $db->Execute("
                insert ignore into IntegratedTranslatedRepositories set
                    itrName = 'concrete5 Core pre 5.7',
		              itrPackageHandle = '_',
		              itrUri = 'https://github.com/concrete5/concrete5.git',
		              itrDevelopBranch = 'master',
                    itrDevelopKey = 'dev-5.6',
		              itrTagsFilter = '< 5.7',
                    itrInAutomatedJob = 1,
                    itrWebRoot = 'web'
            ");
            $db->Execute("
                insert ignore into IntegratedTranslatedRepositories set
                    itrName = 'concrete5 Core from 5.7',
		              itrPackageHandle = '_',
		              itrUri = 'https://github.com/concrete5/concrete5-5.7.0.git',
		              itrDevelopBranch = 'develop',
                    itrDevelopKey = 'dev-5.7',
		              itrTagsFilter = '>= 5.7',
                    itrInAutomatedJob = 1,
                    itrWebRoot = 'web'
            ");
        }
    }

    public function on_start()
    {
        Loader::library('3rdparty/gettext/languages/src/autoloader', 'integrated_localization');
        Loader::library('3rdparty/gettext/gettext/src/autoloader', 'integrated_localization');
        Loader::library('3rdparty/mlocati/concrete5-translation-library/src/autoloader', 'integrated_localization');
    }
}
