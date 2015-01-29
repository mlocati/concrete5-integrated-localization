<?php defined('C5_EXECUTE') or die('Access Denied.');

class IntegratedLocalizationPackage extends Package
{

    protected $pkgHandle = 'integrated_localization';

    protected $appVersionRequired = '5.5.2';

    protected $pkgVersion = '0.0.2';

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
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $db->Execute("
            INSERT INTO Locales
                (lID     , lName                             , lIsSource, lPluralCount, lPluralRule                                                                                  )
                VALUES
                ('ar'    , 'Arabic'                          , 0        , 6           , 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5'),
                ('ast_ES', 'Asturian (Spain)'                , 0        , 2           , '(n != 1)'                                                                                   ),
                ('bg_BG' , 'Bulgarian (Bulgaria)'            , 0        , 2           , '(n != 1)'                                                                                   ),
                ('bs_BA' , 'Bosnian (Bosnia and Herzegovina)', 0        , 3           , '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'         ),
                ('ca'    , 'Catalan'                         , 0        , 2           , '(n != 1)'                                                                                   ),
                ('cs_CZ' , 'Czech (Czech Republic)'          , 0        , 3           , '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2'                                                        ),
                ('da_DK' , 'Danish (Denmark)'                , 0        , 2           , '(n != 1)'                                                                                   ),
                ('de_DE' , 'German (Germany)'                , 0        , 2           , '(n != 1)'                                                                                   ),
                ('el_GR' , 'Greek (Greece)'                  , 0        , 2           , '(n != 1)'                                                                                   ),
                ('en_GB' , 'English (United Kingdom)'        , 0        , 2           , '(n != 1)'                                                                                   ),
                ('en_US' , 'English (United States)'         , 1        , 2           , '(n != 1)'                                                                                   ),
                ('es_AR' , 'Spanish (Argentina)'             , 0        , 2           , '(n != 1)'                                                                                   ),
                ('es_ES' , 'Spanish (Spain)'                 , 0        , 2           , '(n != 1)'                                                                                   ),
                ('es_MX' , 'Spanish (Mexico)'                , 0        , 2           , '(n != 1)'                                                                                   ),
                ('es_PE' , 'Spanish (Peru)'                  , 0        , 2           , '(n != 1)'                                                                                   ),
                ('et_EE' , 'Estonian (Estonia)'              , 0        , 2           , '(n != 1)'                                                                                   ),
                ('fa_IR' , 'Persian (Iran)'                  , 0        , 1           , '0'                                                                                          ),
                ('fi_FI' , 'Finnish (Finland)'               , 0        , 2           , '(n != 1)'                                                                                   ),
                ('fr_FR' , 'French (France)'                 , 0        , 2           , '(n > 1)'                                                                                    ),
                ('he_IL' , 'Hebrew (Israel)'                 , 0        , 2           , '(n != 1)'                                                                                   ),
                ('hi_IN' , 'Hindi (India)'                   , 0        , 2           , '(n != 1)'                                                                                   ),
                ('hr_HR' , 'Croatian (Croatia)'              , 0        , 3           , 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'           ),
                ('hu_HU' , 'Hungarian (Hungary)'             , 0        , 2           , '(n != 1)'                                                                                   ),
                ('id_ID' , 'Indonesian (Indonesia)'          , 0        , 1           , '0'                                                                                          ),
                ('it_IT' , 'Italian (Italy)'                 , 0        , 2           , '(n != 1)'                                                                                   ),
                ('ja_JP' , 'Japanese (Japan)'                , 0        , 1           , '0'                                                                                          ),
                ('km_KH' , 'Khmer (Cambodia)'                , 0        , 1           , '0'                                                                                          ),
                ('ko_KR' , 'Korean (Korea)'                  , 0        , 1           , '0'                                                                                          ),
                ('ku'    , 'Kurdish'                         , 0        , 2           , '(n != 1)'                                                                                   ),
                ('lt_LT' , 'Lithuanian (Lithuania)'          , 0        , 3           , '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2)'                    ),
                ('lv_LV' , 'Latvian (Latvia)'                , 0        , 3           , '(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2)'                                                ),
                ('mk_MK' , 'Macedonian (Macedonia)'          , 0        , 2           , '(n % 10 == 1 && n % 100 != 11) ? 0 : 1'                                                     ),
                ('ml_IN' , 'Malayalam (India)'               , 0        , 2           , '(n != 1)'                                                                                   ),
                ('my_MM' , 'Burmese (Myanmar)'               , 0        , 1           , '0'                                                                                          ),
                ('nb_NO' , 'Norwegian Bokmål (Norway)'       , 0        , 2           , '(n != 1)'                                                                                   ),
                ('nl_NL' , 'Dutch (Netherlands)'             , 0        , 2           , '(n != 1)'                                                                                   ),
                ('pl_PL' , 'Polish (Poland)'                 , 0        , 3           , '(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'                         ),
                ('pt_BR' , 'Portuguese (Brazil)'             , 0        , 2           , '(n > 1)'                                                                                    ),
                ('pt_PT' , 'Portuguese (Portugal)'           , 0        , 2           , '(n != 1)'                                                                                   ),
                ('ro_RO' , 'Romanian (Romania)'              , 0        , 3           , '(n==1?0:(((n%100>19)||((n%100==0)&&(n!=0)))?2:1))'                                          ),
                ('ru_RU' , 'Russian (Russia)'                , 0        , 3           , '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'         ),
                ('sk_SK' , 'Slovak (Slovakia)'               , 0        , 3           , '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2'                                                        ),
                ('sl_SI' , 'Slovenian (Slovenia)'            , 0        , 4           , '(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3)'                               ),
                ('sr_RS' , 'Serbian (Serbia)'                , 0        , 3           , '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'         ),
                ('sv_SE' , 'Swedish (Sweden)'                , 0        , 2           , '(n != 1)'                                                                                   ),
                ('ta_IN' , 'Tamil (India)'                   , 0        , 2           , '(n != 1)'                                                                                   ),
                ('th_TH' , 'Thai (Thailand)'                 , 0        , 1           , '0'                                                                                          ),
                ('tr_TR' , 'Turkish (Turkey)'                , 0        , 1           , '0'                                                                                          ),
                ('uk_UA' , 'Ukrainian (Ukraine)'             , 0        , 3           , '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'         ),
                ('vi_VN' , 'Vietnamese (Viet Nam)'           , 0        , 1           , '0'                                                                                          ),
                ('zh_CN' , 'Chinese (China)'                 , 0        , 1           , '0'                                                                                          ),
                ('zh_TW' , 'Chinese (Taiwan)'                , 0        , 1           , '0'                                                                                          )
        ");
        Loader::model('job');
        if (!Job::getByHandle('fetch_git_translations')) {
            Job::installByPackage('fetch_git_translations', $pkg);
        }
    }

    public function on_start()
    {
        Loader::library('3rdparty/gettext/gettext/src/autoloader', 'integrated_localization');
        Loader::library('3rdparty/mlocati/concrete5-translation-library/src/autoloader', 'integrated_localization');
    }
}
