<?php defined('C5_EXECUTE') or die('Access Denied.');

class IntegratedLocalizationTranslateOnlineController extends Controller
{
    public function view($package = '', $version = '', $localeID = '')
    {
        try {
            if (!(
                is_string($package) && ($package !== '')
                &&
                is_string($version) && ($version !== '')
                &&
                is_string($localeID) && ($localeID !== '')
            )) {
                $this->redirect('/integrated_localization/translate');
            }
            Loader::model('integrated_locale', 'integrated_localization');
            $locale = IntegratedLocale::getByID($localeID);
            if (!isset($locale)) {
                throw new Exception(t('Invalid locale identifier: %s', $localeID));
            }
            $th = Loader::helper('translators', 'integrated_localization');
            /* @var $th TranslatorsHelper */
            $access = $th->getCurrentUserAccess($locale);
            if ($access < TranslatorAccess::LOCALE_TRANSLATOR) {
                throw new Exception(t('Access denied'));
            }
            $isCoordinator = ($access >= TranslatorAccess::LOCALE_ADMINISTRATOR) ? true : false;
            $tsh = Loader::helper('translations', 'integrated_localization');
            /* @var $tsh TranslationsHelper */
            $translations = $tsh->loadTranslationsByPackage($locale, $package, $version, '', true);
            if ($translations->count() < 1) {
                throw new Exception(t('Invalid package/version'));
            }
            $numPlurals = $locale->getPluralCount();
            $jsonTranslations = array();
            foreach ($translations as $translation) {
                /* @var $translation \Gettext\Translation */
                $jsonTranslation = array();
                $jsonTranslation['id'] = md5($translation->getId());
                if ($translation->hasContext()) {
                    $jsonTranslation['context'] = $translation->getContext();
                }
                $jsonTranslation['original'] = $translation->getOriginal();
                if ($translation->hasPlural()) {
                    $jsonTranslation['originalPlural'] = $translation->getPlural();
                }
                $isApproved = false;
                if ($translation->hasTranslation()) {
                    $isApproved = true;
                    $t = array($translation->getTranslation());
                    if (($numPlurals > 1) && $translation->hasPlural()) {
                        $t = array_merge($t, $translation->getPluralTranslation());
                    }
                    $jsonTranslation['translations'] = $t;
                    if ($translation->hasFlags()) {
                        foreach ($translation->getFlags() as $flag) {
                            if ($flag === 'fuzzy') {
                                $isApproved = false;
                                break;
                            }
                        }
                    }
                }
                if ($isApproved === true) {
                    $jsonTranslation['isApproved'] = true;
                }
                $extractedComments = '';
                if ($translation->hasExtractedComments()) {
                    $extractedComments = trim(implode("\n", $translation->getExtractedComments()));
                }
                if ($extractedComments !== '') {
                    $jsonTranslation['comments'] = $extractedComments;
                }
                if ($translation->hasReferences()) {
                    $jsonTranslation['references'] = array();
                    foreach ($translation->getReferences() as $ref) {
                        $jsonTranslation['references'][] = isset($ref[1]) ? "{$ref[0]}:{$ref[1]}" : $ref[0];
                    }
                }
                $jsonTranslations[] = $jsonTranslation;
            }
            $jsonOptions = 0;
            if (defined('\JSON_UNESCAPED_SLASHES')) {
                $jsonOptions |= \JSON_UNESCAPED_SLASHES;
            }
            $this->set('package', $package);
            $this->set('version', $version);
            $this->set('locale', $locale);
            $this->set('jsonOptions', $jsonOptions);
            $this->set('translations', $jsonTranslations);
            $this->set('pluralCases', $locale->getPluralCases());
            $this->set('isCoordinator', $isCoordinator);
            $hh = Loader::helper('html');
            /* @var $hh HtmlHelper */
            $this->addHeaderItem($hh->css('translator.css', 'integrated_localization'));
            $this->addFooterItem($hh->javascript('bs3/dropdown.js', 'integrated_localization'));
            $this->addFooterItem($hh->javascript('translator.js', 'integrated_localization'));
        } catch (Exception $x) {
            $this->set('error', $x->getMessage());
        }
    }

    public function save_translation($package, $version, $localeID)
    {
        $jh = Loader::helper('json');
        /* @var $jh JsonHelper */
        try {
            if (!(is_string($package) && ($package !== ''))) {
                throw new Exception(t('Invalid parameter: %s', 'package'));
            }
            if (!(is_string($version) && ($version !== ''))) {
                throw new Exception(t('Invalid parameter: %s', 'version'));
            }
            if (!(is_string($localeID) && ($localeID !== ''))) {
                throw new Exception(t('Invalid parameter: %s', 'localeID'));
            }
            Loader::model('integrated_locale', 'integrated_localization');
            $locale = IntegratedLocale::getByID($localeID);
            if (!isset($locale)) {
                throw new Exception(t('Invalid locale identifier: %s', $localeID));
            }
            $th = Loader::helper('translators', 'integrated_localization');
            /* @var $th TranslatorsHelper */
            $access = $th->getCurrentUserAccess($locale);
            if ($access < TranslatorAccess::LOCALE_TRANSLATOR) {
                throw new Exception(t('Access denied'));
            }
            $db = Loader::db();
            /* @var $db ADODB_mysql */
            $current = $db->GetRow(
                "
                    select
                        itID as translationID,
                        IFNULL(itPlural, '') != '' as plural,
                        itTranslatable as translated,
                        itApproved as approved
                    from
                        IntegratedTranslatables
                        left join IntegratedTranslations on IntegratedTranslatables.itID = IntegratedTranslations.itTranslatable
                    where
                        IntegratedTranslatables.itHash = ?
                        and ((itLocale is null) or (itLocale = ?))
                    limit 1
                ",
                array($this->post('id'), $locale->getID())
            );
            if (empty($current) || empty($current['translationID'])) {
                throw new Exception(t('Unable to find the specified translatable string'));
            }
            $translationID = intval($current['translationID']);
            if (!empty($current['approved'])) {
                if ($access < TranslatorAccess::LOCALE_ADMINISTRATOR) {
                    throw new Exception(t('Access denied'));
                }
            }
            if ($this->post('clear') === '1') {
                $db->Execute('delete from IntegratedTranslations where (itLocale = ?) and (itTranslatable = ?) limit 1', array($locale->getID(), $translationID));
            } else {
                $translatedStrings = $this->post('translated');
                if (!is_array($translatedStrings)) {
                    throw new Exception(t('Invalid parameter: %s', 'translated'));
                }
                $fields = array();
                $values = array();
                $count = empty($current['plural']) ? 1 : $locale->getPluralCount();
                for ($index = 0; $index < $count; $index++) {
                    $value = '';
                    if (isset($translatedStrings[$index]) && is_string($translatedStrings[$index])) {
                        $value = $translatedStrings[$index];
                    }
                    if ($value === '') {
                        throw new Exception(t('Invalid parameter: %s', 'translated['.$index.']'));
                    }
                    $fields[] = 'itText'.$index;
                    $values[] = $value;
                }
                if ($access >= TranslatorAccess::LOCALE_ADMINISTRATOR) {
                    $approve = $this->post('approved');
                    if (($approve === '0') || ($approve === '1')) {
                        $fields[] = 'itApproved';
                        $values[] = intval($approve);
                    } else {
                        throw new Exception(t('Invalid parameter: %s', 'approve'));
                    }
                }
                if (empty($current['translated'])) {
                    $sql = 'insert into IntegratedTranslations set ';
                } else {
                    $sql = 'update IntegratedTranslations set ';
                }
                foreach ($fields as $index => $name) {
                    $sql .= (($index > 0) ? ', ' : '').$name.' = ?';
                }
                if (empty($current['translated'])) {
                    $sql .= ', itLocale = ?, itTranslatable = ?';
                    $values[] = $locale->getID();
                    $values[] = $translationID;
                } else {
                    $sql .= ' where (itLocale = ?) and (itTranslatable = ?) limit 1';
                    $values[] = $locale->getID();
                    $values[] = $translationID;
                }
                $db->Execute($sql, $values);
            }
        } catch (Exception $x) {
            echo $jh->encode(array(
                'error' => true,
                'errors' => array($x->getMessage()),
            ));
        }
        die();
    }
}
