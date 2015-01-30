<?php defined('C5_EXECUTE') or die('Access Denied.');

class DashboardIntegratedLocalizationLocalesController extends Controller
{
    public function view()
    {
        $tsh = Loader::helper('translations_source', 'integrated_localization');
        /* @var $tsh TranslationsSourceHelper */
        $locales = $tsh->getAvailableLocales(false);
        $editingLocale = false;
        $lID = $this->get('lID');
        if (is_string($lID)) {
            foreach ($locales as $locale) {
                if ($locale['id'] === $lID) {
                    $editingLocale = $locale;
                    break;
                }
            }
        }
        $this->set('locales', $locales);
        $this->set('editingLocale', $editingLocale);
    }
    public function approved()
    {
        $this->set('message', t('The locale has been approved'));
        $this->view();
    }
    public function unapproved()
    {
        $this->set('message', t('The locale request has been denied'));
        $this->view();
    }

    public function approve()
    {
        $locale = null;
        $approve = null;
        $tsh = Loader::helper('translations_source', 'integrated_localization');
        /* @var $tsh TranslationsSourceHelper */
        $locales = $tsh->getAvailableLocales(false);
        $lID = $this->get('lID');
        if (is_string($lID)) {
            foreach ($locales as $l) {
                if ($l['id'] === $lID) {
                    $locale = $l;
                    break;
                }
            }
        }

        $a = $this->get('ok');
        if ($a === '1') {
            $approve = true;
        } elseif ($a === '0') {
            $approve = false;
        }
        if (isset($locale) && isset($approve)) {
            $db = Loader::db();
            /* @var $db ADODB_mysql */
            if ($approve) {
                $db->Execute('UPDATE Locales SET lApproved = 1 WHERE lID = ? LIMIT 1', array($locale['id']));
                $this->redirect('/dashboard/integrated_localization/locales/approved');
            } else {
                $db->Execute('DELETE FROM Locales WHERE lID = ? LIMIT 1', array($locale['id']));
                $this->redirect('/dashboard/integrated_localization/locales/unapproved');
            }
        } else {
            $this->view();
        }
    }
}
