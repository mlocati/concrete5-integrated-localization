<?php defined('C5_EXECUTE') or die('Access Denied.');

class IntegratedLocalizationPackage extends Package
{

    protected $pkgHandle = 'integrated_localization';

    protected $appVersionRequired = '5.5.2';

    protected $pkgVersion = '0.0.6';

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
        Loader::model('job');
        Loader::model('page');
        Loader::model('single_page');
        Loader::model('block_type');
        $db = Loader::db();
        if ($fromVersion === '') {
            /* @var $db ADODB_mysql */
            $db->Execute("
                INSERT IGNORE INTO IntegratedLocales
                    (ilID    , ilName                            , ilIsSource, ilPluralCount, ilPluralRule                                                                                 , ilApproved, ilRequestedBy, ilRequestedOn)
                    VALUES
                    ('en_US' , 'English (United States)'         , 1         , 2            , '(n != 1)'                                                                                   , 1         , NULL         , NULL         ),
                    ('ar'    , 'Arabic'                          , 0         , 6            , 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5', 0         , 1            , NOW()        ),
                    ('ast_ES', 'Asturian (Spain)'                , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('bg_BG' , 'Bulgarian (Bulgaria)'            , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('bs_BA' , 'Bosnian (Bosnia and Herzegovina)', 0         , 3            , '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'         , 0         , 1            , NOW()        ),
                    ('ca'    , 'Catalan'                         , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('cs_CZ' , 'Czech (Czech Republic)'          , 0         , 3            , '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2'                                                        , 0         , 1            , NOW()        ),
                    ('da_DK' , 'Danish (Denmark)'                , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('de_DE' , 'German (Germany)'                , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('el_GR' , 'Greek (Greece)'                  , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('en_GB' , 'English (United Kingdom)'        , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('es_AR' , 'Spanish (Argentina)'             , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('es_ES' , 'Spanish (Spain)'                 , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('es_MX' , 'Spanish (Mexico)'                , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('es_PE' , 'Spanish (Peru)'                  , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('et_EE' , 'Estonian (Estonia)'              , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('fa_IR' , 'Persian (Iran)'                  , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        ),
                    ('fi_FI' , 'Finnish (Finland)'               , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('fr_FR' , 'French (France)'                 , 0         , 2            , '(n > 1)'                                                                                    , 0         , 1            , NOW()        ),
                    ('he_IL' , 'Hebrew (Israel)'                 , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('hi_IN' , 'Hindi (India)'                   , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('hr_HR' , 'Croatian (Croatia)'              , 0         , 3            , 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'           , 0         , 1            , NOW()        ),
                    ('hu_HU' , 'Hungarian (Hungary)'             , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('id_ID' , 'Indonesian (Indonesia)'          , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        ),
                    ('it_IT' , 'Italian (Italy)'                 , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('ja_JP' , 'Japanese (Japan)'                , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        ),
                    ('km_KH' , 'Khmer (Cambodia)'                , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        ),
                    ('ko_KR' , 'Korean (Korea)'                  , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        ),
                    ('ku'    , 'Kurdish'                         , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('lt_LT' , 'Lithuanian (Lithuania)'          , 0         , 3            , '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2)'                    , 0         , 1            , NOW()        ),
                    ('lv_LV' , 'Latvian (Latvia)'                , 0         , 3            , '(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2)'                                                , 0         , 1            , NOW()        ),
                    ('mk_MK' , 'Macedonian (Macedonia)'          , 0         , 2            , '(n % 10 == 1 && n % 100 != 11) ? 0 : 1'                                                     , 0         , 1            , NOW()        ),
                    ('ml_IN' , 'Malayalam (India)'               , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('my_MM' , 'Burmese (Myanmar)'               , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        ),
                    ('nb_NO' , 'Norwegian Bokmål (Norway)'       , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('nl_NL' , 'Dutch (Netherlands)'             , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('pl_PL' , 'Polish (Poland)'                 , 0         , 3            , '(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'                         , 0         , 1            , NOW()        ),
                    ('pt_BR' , 'Portuguese (Brazil)'             , 0         , 2            , '(n > 1)'                                                                                    , 0         , 1            , NOW()        ),
                    ('pt_PT' , 'Portuguese (Portugal)'           , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('ro_RO' , 'Romanian (Romania)'              , 0         , 3            , '(n==1?0:(((n%100>19)||((n%100==0)&&(n!=0)))?2:1))'                                          , 0         , 1            , NOW()        ),
                    ('ru_RU' , 'Russian (Russia)'                , 0         , 3            , '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'         , 0         , 1            , NOW()        ),
                    ('sk_SK' , 'Slovak (Slovakia)'               , 0         , 3            , '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2'                                                        , 0         , 1            , NOW()        ),
                    ('sl_SI' , 'Slovenian (Slovenia)'            , 0         , 4            , '(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3)'                               , 0         , 1            , NOW()        ),
                    ('sr_RS' , 'Serbian (Serbia)'                , 0         , 3            , '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'         , 0         , 1            , NOW()        ),
                    ('sv_SE' , 'Swedish (Sweden)'                , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('ta_IN' , 'Tamil (India)'                   , 0         , 2            , '(n != 1)'                                                                                   , 0         , 1            , NOW()        ),
                    ('th_TH' , 'Thai (Thailand)'                 , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        ),
                    ('tr_TR' , 'Turkish (Turkey)'                , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        ),
                    ('uk_UA' , 'Ukrainian (Ukraine)'             , 0         , 3            , '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'         , 0         , 1            , NOW()        ),
                    ('vi_VN' , 'Vietnamese (Viet Nam)'           , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        ),
                    ('zh_CN' , 'Chinese (China)'                 , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        ),
                    ('zh_TW' , 'Chinese (Taiwan)'                , 0         , 1            , '0'                                                                                          , 0         , 1            , NOW()        )
            ");
            if (!Job::getByHandle('fetch_git_translations')) {
                Job::installByPackage('fetch_git_translations', $pkg);
            }
        }
        foreach (array(
            '/dashboard/integrated_localization' => array('name' => t('Integrated Localization'), 'description' => ''),
            '/dashboard/integrated_localization/locales' => array('name' => t('Locales'), 'description' => ''),
        ) as $path => $info) {
            $sp = Page::getByPath($path);
            /* @var $sp Page */
            if ((!is_object($sp)) || $sp->isError() || (!$sp->getCollectionID())) {
                $sp = SinglePage::add($path, $pkg);
                if (is_object($sp)) {
                    $sp->update(array('cName' => $info['name'], 'cDescription' => $info['description']));
                }
            }
        }
        $bt = BlockType::getByHandle('package_languages_builder');
        if (!is_object($bt)) {
            BlockType::installBlockTypeFromPackage('package_languages_builder', $pkg);
        }
    }

    public function on_start()
    {
        Loader::library('3rdparty/gettext/gettext/src/autoloader', 'integrated_localization');
        Loader::library('3rdparty/mlocati/concrete5-translation-library/src/autoloader', 'integrated_localization');
    }
}
