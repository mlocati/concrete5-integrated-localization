concrete5 Integrated Localization
=================================

Work-in-progress project to integrate a translation system within concrete5.org website.


Available components
--------------------


### Jobs

`Fetch git translations`
Fetches the two GitHub repositories and extracts translatable strings for all the tagged versions as well as for the development branches.
**Warning** the first execution of this job may require a few minutes. Subsequent executions will be much faster (it should complete in seconds).


### Block Types

`Integrated Package Languages Builder`
For package developers: they can upload their package and download the language files for that package


### User Groups

`Translations administrators`
 Users belonging to this group will have global locale administration rights

`Locale administrators for xx_XX` (where `xx_XX` is a locale identifier)
Users belonging to this group will have locale-specific administration rights

`Locale translators for xx_XX` (where `xx_XX` is a locale identifier)
Users belonging to this group will have locale-specific translation rights

`Aspirant locale translators for xx_XX` (where `xx_XX` is a locale identifier)
Users that want start translating a specific locale will be inserted in this group


### Single Pages

`/integrated_localization/locales`
to manage locales (only for site administrators and for global locale administrators)

`/integrated_localization/groups`
to view, join and manage translation groups

`/integrated_localization/translate`
to view core and package translations, and to upload new translations


Example of importing package strings
------------------------------------

```php
<?php
define('C5_ENVIRONMENT_ONLY', true);
define('DIR_BASE', dirname(__DIR__));

require_once __DIR__.'/../concrete/dispatcher.php';

$localFile = __dir__.'/testpackage.zip';
try {

    if (!(is_file($localFile) && filesize($localFile))) {
        echo "Downloading a sample package... ";
        if (!file_put_contents($localFile, file_get_contents('http://www.concrete5.org/download_file/-/64021/0/'))) {
            die('Error downloading remote file');
        }
        echo "done.\n";
    }

    echo "Initializing package worker... ";
    Loader::model('integrated_package_localizer', 'integrated_localization');
    $ipl = new IntegratedPackageLocalizer($localFile);
    echo "done.\n";

    echo "Package handle: ", $ipl->getPackageHandle(), "\n";
    echo "Package version: ", $ipl->getPackageVersion(), "\n";

    echo "Importing/updating translatable strings... ";
    $tsh = Loader::helper('translations_source', 'integrated_localization');
    /* @var $tsh TranslationsSourceHelper */
    $tsh->saveTranslatables($ipl->getTranslatables(), $ipl->getPackageHandle(), $ipl->getPackageVersion());
    echo "done.\n";
    
    Loader::model('integrated_locale', 'integrated_localization');
    foreach (IntegratedLocale::getList() as $locale) {
        try {
           $translations = $ipl->getTranslations($locale, true, false, false);
           if($translations->count() > 0) {
               echo "Importing translated strings for ", $locale->getName(), "... ";
               $tsh->saveTranslations($locale, $translations, false);
               echo "done.\n";
           }
        } catch (Exception $x) {
        }
    }
    
    die('All done.');
}
catch(Exception $x) {
    die('Error: '.$x->getMessage());
}
```