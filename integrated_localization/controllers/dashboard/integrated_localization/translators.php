<?php defined('C5_EXECUTE') or die('Access Denied.');

class DashboardIntegratedLocalizationTranslatorsController extends Controller
{
    public function view()
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locales = IntegratedLocale::getList(true, true);
        $this->set('locales', $locales);
    }
    public function group($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID);
        if ($locale) {
            $th = Loader::helper('translators', 'integrated_localization');
            /* @var $th TranslatorsHelper */
            $this->set('me', User::isLoggedIn() ? new User() : null);
            $this->set('myAccess', $th->getCurrentUserAccess($locale));
            $this->set('viewingLocale', $locale);
            $g = $th->getAdministratorsGroup();
            $this->set('globalAdministratorsGroup', $g);
            $globalAdministrators = self::simplifyUsers($g ? $g->getGroupMembers() : array(), array());
            $this->set('globalAdministrators', $globalAdministrators);
            $g = $locale->getAdministratorsGroup();
            $this->set('groupAdministratorsGroup', $g);
            $groupAdministrators = self::simplifyUsers($g ? $g->getGroupMembers() : array(), array($globalAdministrators));
            $this->set('groupAdministrators', $groupAdministrators);
            $g = $locale->getTranslatorsGroup();
            $this->set('groupTranslatorsGroup', $g);
            $groupTranslators = self::simplifyUsers($g ? $g->getGroupMembers() : array(), array($globalAdministrators, $groupAdministrators));
            $this->set('groupTranslators', $groupTranslators);
            $g = $locale->getAspirantTranslatorsGroup();
            $this->set('groupAspirantTranslatorsGroup', $g);
            $groupAspirantTranslators = self::simplifyUsers($g ? $g->getGroupMembers() : array(), array($globalAdministrators, $groupAdministrators, $groupTranslators));
            $this->set('groupAspirantTranslators', $groupAspirantTranslators);
            
        } else {
            $th = Loader::helper('text');
            /* @var $th TextHelper */
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }
    public function leave_group($localeID, $groupID)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID);
        if ($locale) {
            $group = Group::getByID(@intval($groupID));
            if(is_object($group)) {
                $me = User::isLoggedIn() ? new User() : null;
                if(is_object($me)) {
                    $me->exitGroup($group);
                    $this->set('message', t('You left the group "%s"', $th->specialchars($group->getGroupName())));
                }
                else {
                    $this->set('error', t('No logged-in user.'));
                }
            }
            $this->group($locale->getID());
        } else {
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }
    public function enter_group($localeID)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID);
        if ($locale) {
            $me = User::isLoggedIn() ? new User() : null;
            if(is_object($me)) {
                $trh = Loader::helper('translators', 'integrated_localization');
                /* @var $trh TranslatorsHelper */
                if($trh->getCurrentUserAccess($locale) === '') {
                    $g = $locale->getAspirantTranslatorsGroup();
                    if($g) {
                        $me->enterGroup($g);
                        $this->set('message', t('You asked to join the translators group for "%s"', $th->specialchars($locale->getName())));
                    }
                    else {
                        $this->set('error', t('Internal error: no applicant group found!'));
                    }
                }
            }
            else {
                $this->set('error', t('No logged-in user.'));
            }
            $this->group($locale->getID());
        } else {
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }
    private static function simplifyUsers($users, $substractLists) {
        $result = array();
        foreach($users as $u) {
            if(is_object($u) && $u->getUserID()) {
                $skip = false;
                foreach($substractLists as $substractList) {
                    foreach($substractList as $substractUser) {
                        if($substractUser->getUserID() == $u->getUserID()) {
                            $skip = true;
                            break;
                        }
                    }
                    if($skip) {
                        break;
                    }
                }
                if(!$skip) {
                    $result[] = $u;
                }
            }
        }
        return $result;
    }
}
