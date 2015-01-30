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
    public function approved($localeName)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        $this->set('message', t("The locale '%s' has been approved", $th->specialchars($localeName)));
        $this->view();
    }
    public function deleted($localeName)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        $this->set('message', t("The request to adopt the locale '%s' has been denied", $th->specialchars($localeName)));
        $this->view();
    }

    public function approve($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID, true);
        if ($locale) {
            $locale->approve();
            $this->redirect('/dashboard/integrated_localization/locales', 'approved', $locale->getName());
        } else {
            $this->set('error', t('Unable to find the locale with id %s', $localeID));
            $this->view();
        }
    }
    public function delete($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID, true);
        if ($locale) {
            $locale->delete();
            $this->redirect('/dashboard/integrated_localization/locales', 'deleted', $locale-getName());
        } else {
            $this->set('error', t('Unable to find the locale with id %s', $localeID));
            $this->view();
        }
    }
}
