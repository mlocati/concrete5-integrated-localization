<?php defined('C5_EXECUTE') or die('Access Denied.');

class PackageLanguagesBuilderBlockController extends BlockController
{
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btCacheBlockOutputOnPost = false;
    protected $btCacheBlockOutputForRegisteredUsers = true;
    protected $btCacheBlockOutputLifetime = CACHE_LIFETIME;

    public function getBlockTypeName()
    {
        return t('Integrated Package Languages Builder');
    }

    public function getBlockTypeDescription()
    {
        return t('Create templates languages for package developers');
    }

    public function view()
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $this->set('locales', IntegratedLocale::getList());
    }

    public function action_parse_package()
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        try {
            $feh = Loader::helper('file_extended', 'integrated_localization');
            /* @var $feh FileExtendedHelper */
            $file = $feh->getUploadedFile('package_file');
            if (!$file) {
                throw new Exception(t('Please upload the zip file of your package.'));
            }
            if (!preg_match('/.\.zip$/i', $file['name'])) {
                throw new Exception(t('Please upload a zip file containing your package.'));
            }
            Loader::model('integrated_package_localizer', 'integrated_localization');
            $ipl = new IntegratedPackageLocalizer($file['tmp_name']);
            if ($this->post('create_pot') === '1') {
                $ipl->writePotFile();
            }
            $createMO = ($this->post('create_mo') === '1') ? true : false;
            $createPO = ($this->post('create_po') === '1') ? true : false;
            if ($createMO || $createPO) {
                Loader::model('integrated_locale', 'integrated_localization');
                $allLocales = IntegratedLocale::getList();
                if ($this->post('all_locales') === '1') {
                    $locales = $allLocales;
                } else {
                    $locales = array();
                    $selectedLocales = $this->post('selected_locales');
                    if (!is_array($selectedLocales)) {
                        $selectedLocales = array($selectedLocales);
                    }
                    foreach ($selectedLocales as $selectedLocale) {
                        foreach ($allLocales as $l) {
                            if ($l->getID() === $selectedLocale) {
                                $locales[] = $l;
                                break;
                            }
                        }
                    }
                }
                foreach ($locales as $locale) {
                    $translations = $ipl->getTranslations($locale, true, true);
                    $ipl->writeTranslationsFile($locale, $translations, $createMO, $createPO);
                }
            }
            $ipl->downloadLanguagesFolder();
        } catch (Exception $x) {
            echo nl2br($th->specialchars($x->getMessage()));
        }
        die();
    }
}
