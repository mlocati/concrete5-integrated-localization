<?php defined('C5_EXECUTE') or die('Access Denied.');

class DashboardIntegratedLocalizationLocalesController extends Controller
{
    public function view()
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locales = IntegratedLocale::getList(true, true);
        $editingLocale = false;
        $ilID = $this->get('ilID');
        if (is_string($ilID)) {
            foreach ($locales as $locale) {
                if ($locale->getID() === $ilID) {
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

    public function set_approved()
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($this->get('ilID'), true);
        if ($locale) {
            $approve = null;
            $a = $this->get('approve');
            if ($a === 'yes') {
                $locale->approve();
                $this->redirect('/dashboard/integrated_localization/locales/approved');
            } elseif ($a === 'no') {
                $locale->delete();
                $this->redirect('/dashboard/integrated_localization/locales/unapproved');
            }
        }
        $this->view();
    }
}
