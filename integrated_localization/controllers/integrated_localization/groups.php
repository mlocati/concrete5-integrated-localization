<?php defined('C5_EXECUTE') or die('Access Denied.');

class IntegratedLocalizationGroupsController extends Controller
{
    public function on_start()
    {
        Loader::helper('translators', 'integrated_localization');
    }

    public function view()
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locales = IntegratedLocale::getList(true, true);
        $this->set('locales', $locales);
        $me = User::isLoggedIn() ? new User() : null;
        if (isset($me) && $me->getUserID()) {
            $this->set('myID', $me->getUserID());
        }
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
            if (is_object($group)) {
                $me = User::isLoggedIn() ? new User() : null;
                if (is_object($me)) {
                    $me->exitGroup($group);
                    $this->set('message', t('You left the group "%s"', $th->specialchars($group->getGroupName())));
                } else {
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
            if (is_object($me)) {
                $trh = Loader::helper('translators', 'integrated_localization');
                /* @var $trh TranslatorsHelper */
                if ($trh->getCurrentUserAccess($locale) === TranslatorAccess::NONE) {
                    $g = $locale->getAspirantTranslatorsGroup();
                    if ($g) {
                        $me->enterGroup($g);
                        $this->set('message', t('You asked to join the translators group for "%s"', $th->specialchars($locale->getName())));
                    } else {
                        $this->set('error', t('Internal error: no applicant group found!'));
                    }
                }
            } else {
                $this->set('error', t('No logged-in user.'));
            }
            $this->group($locale->getID());
        } else {
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }
    public function kickuser($localeID, $userID)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID);
        if ($locale) {
            $trh = Loader::helper('translators', 'integrated_localization');
            /* @var $trh TranslatorsHelper */
            if ($trh->getCurrentUserAccess($locale) >= TranslatorAccess::LOCALE_ADMINISTRATOR) {
                $user = (is_string($userID) && is_numeric($userID)) ? User::getByUserID($userID) : null;
                if (is_object($user)) {
                    foreach (array($locale->getAdministratorsGroup(), $locale->getTranslatorsGroup(), $locale->getAspirantTranslatorsGroup()) as $g) {
                        if ($g && $user->inGroup($g)) {
                            $user->exitGroup($g);
                        }
                    }
                    $this->set('message', t('The user has been removed from the language translators!'));
                } else {
                    $this->set('error', t('Unable to find the user with id %s', $th->specialchars($userID)));
                }
            } else {
                $this->set('error', t('Access denied!'));
            }
            $this->group($locale->getID());
        } else {
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }
    public function update_user_group($localeID, $userID, $from, $to)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID);
        if (isset($locale)) {
            if (!is_int($from)) {
                $from = (is_string($from) && is_numeric($from)) ? @intval($from) : null;
            }
            if (!is_int($to)) {
                $to = (is_string($to) && is_numeric($to)) ? @intval($to) : null;
            }
            $trh = Loader::helper('translators', 'integrated_localization');
            $myAccess = $trh->getCurrentUserAccess($locale);
            /* @var $trh TranslatorsHelper */
            if (($myAccess >= TranslatorAccess::LOCALE_ADMINISTRATOR) && is_int($from) && ($from <= $myAccess) && is_int($to) && ($to <= $myAccess)) {
                $user = (is_string($userID) && is_numeric($userID)) ? User::getByUserID($userID) : null;
                if (is_object($user)) {
                    switch ($from) {
                        case TranslatorAccess::LOCALE_ASPIRANT:
                            $gFrom = $locale->getAspirantTranslatorsGroup();
                            break;
                        case TranslatorAccess::LOCALE_TRANSLATOR:
                            $gFrom = $locale->getTranslatorsGroup();
                            break;
                        case TranslatorAccess::LOCALE_ADMINISTRATOR:
                            $gFrom = $locale->getAdministratorsGroup();
                            break;
                        default:
                            $gFrom = null;
                    }
                    if (isset($gFrom)) {
                        switch ($to) {
                            case TranslatorAccess::LOCALE_ASPIRANT:
                                $gTo = $locale->getAspirantTranslatorsGroup();
                                break;
                            case TranslatorAccess::LOCALE_TRANSLATOR:
                                $gTo = $locale->getTranslatorsGroup();
                                break;
                            case TranslatorAccess::LOCALE_ADMINISTRATOR:
                                $gTo = $locale->getAdministratorsGroup();
                                break;
                            default:
                                $gTo = null;
                        }
                        if (isset($gTo)) {
                            if ($user->inGroup($gFrom)) {
                                $user->enterGroup($gTo);
                                $user->exitGroup($gFrom);
                                $this->set('message', t('The user %s has been updated', $user->getUserName()));
                                $this->group($locale->getID());
                            } else {
                                $this->set('error', t('The specified user does not belong to the specified group'));
                            }
                        } else {
                            $this->set('error', t('Internal error: invalid/inexistent destination group!'));
                        }
                    } else {
                        $this->set('error', t('Internal error: invalid/inexistent source group!'));
                    }
                } else {
                    $this->set('error', t('Unable to find the user with id %s', $th->specialchars($userID)));
                }
            } else {
                $this->set('error', t('Access denied!'));
            }
            $this->group($locale->getID());
        } else {
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }

    public function new_locale()
    {
        if (!User::isLoggedIn()) {
            $this->set('error', t('You need to login in order to suggest the creation of a new language'));
            $this->view();
        } else {
            $this->set('addingNewLocale', true);
            $languages = array();
            foreach (\Gettext\Utils\Locales::getLanguages(true, true) as $languageID => $languageName) {
                if (\Gettext\Utils\Locales::getLocaleInfo($languageID)) {
                    $languages[$languageID] = $languageName;
                }
            }
            natcasesort($languages);
            $this->set('languages', $languages);
            $territories = \Gettext\Utils\Locales::getTerritories(true, true);
            natcasesort($territories);
            $this->set('territories', $territories);
        }
    }
    public function add_new_locale()
    {
        $me = User::isLoggedIn() ? new User() : null;

        if (!(is_object($me) && $me->getUserID())) {
            $this->set('error', t('You need to login in order to suggest the creation of a new language'));
            $this->view();
        } else {
            $languages = \Gettext\Utils\Locales::getLanguages(true, true);
            $s = $this->post('language');
            $language = is_string($s) ? $s : '';
            if (($language === '') || (!isset($languages[$language]))) {
                $this->set('error', t('Please specify the language'));
                $this->new_locale();
            } else {
                $territories = \Gettext\Utils\Locales::getTerritories(true, true);
                $s = $this->post('territory');
                $territory = is_string($s) ? $s : '';
                if (($territory === '') || (($territory !== '-') && (!isset($territories[$territory])))) {
                    $this->set('error', t('Please specify the country'));
                    $this->new_locale();
                } else {
                    $id = $language;
                    $name = $languages[$language];
                    if ($territory === '-') {
                        $territory = '';
                    } else {
                        $id .= '_'.$territory;
                        $name .= ' ('.$territories[$territory].')';
                    }
                    Loader::model('integrated_locale', 'integrated_localization');
                    $already = IntegratedLocale::getByID($id, true, true);
                    if (isset($already)) {
                        if ($already->getIsSource()) {
                            $this->set('error', t("The language '%s' is the one used by the code and can't be translated.", $already->getName()));
                        } elseif (!$already->getApproved()) {
                            $this->set('error', t("Someone else already asked to create the language '%s'.", $already->getName()));
                        } else {
                            $this->set('error', t("The translation group for '%s' already exists.", $already->getName()));
                        }
                        $this->new_locale();
                    } else {
                        $localeInfo = \Gettext\Utils\Locales::getLocaleInfo($id);
                        if (!isset($localeInfo)) {
                            $this->set('error', t("We don't know the locale code '%s'!", "$name - $id"));
                            $this->new_locale();
                        } else {
                            try {
                                $newLocale = IntegratedLocale::add($id, $name, $localeInfo['plurals'], $localeInfo['pluralRule']);
                            } catch (Exception $x) {
                                $this->set('error', $x->getMessage());
                                $this->new_locale();
                            }
                            if (isset($newLocale)) {
                                $this->set('message', t("Your request to create the translation group for '' has been submitted.", $newLocale->getName()));
                                $this->view();
                            }
                        }
                    }
                }
            }
        }
    }
    public function cancel_group_request($localeID)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        $me = User::isLoggedIn() ? new User() : null;
        if (isset($me) && $me->getUserID()) {
            Loader::model('integrated_locale', 'integrated_localization');
            $locale = IntegratedLocale::getByID($localeID, true);
            if ($locale) {
                if ((!$locale->getIsSource()) && (!$locale->getApproved()) && ($locale->getRequestedBy() == $me->getUserID())) {
                    $name = $locale->getName();
                    $locale->delete();
                    $this->set('message', t("Your request to create the '%s' language group has been cancelled.", $name));
                } else {
                    $this->set('error', t('Access denied.'));
                }
            } else {
                $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            }
        } else {
            $this->set('error', t('Access denied.'));
        }
        $this->view();
    }

    private static function simplifyUsers($users, $substractLists)
    {
        $result = array();
        foreach ($users as $u) {
            if (is_object($u) && $u->getUserID()) {
                $skip = false;
                foreach ($substractLists as $substractList) {
                    foreach ($substractList as $substractUser) {
                        if ($substractUser->getUserID() == $u->getUserID()) {
                            $skip = true;
                            break;
                        }
                    }
                    if ($skip) {
                        break;
                    }
                }
                if (!$skip) {
                    $result[] = $u;
                }
            }
        }

        return $result;
    }
}
