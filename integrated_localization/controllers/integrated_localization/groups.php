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
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID);
        if ($locale) {
            $group = Group::getByID(@intval($groupID));
            if (is_object($group)) {
                $me = User::isLoggedIn() ? new User() : null;
                if (is_object($me)) {
                    $me->exitGroup($group);
                    $this->redirect('/integrated_localization/groups', 'left_group', $locale->getID(), $group->getGroupID());
                } else {
                    $this->set('error', t('No logged-in user.'));
                }
            }
            $this->group($locale->getID());
        } else {
            $th = Loader::helper('text');
            /* @var $th TextHelper */
            $this->set('error', t('Unable to find the locale with id %s', $th->specialchars($localeID)));
            $this->view();
        }
    }
    public function left_group($localeID, $groupID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID);
        if (isset($locale)) {
            $group = Group::getByID(@intval($groupID));
            if (is_object($group)) {
                $th = Loader::helper('text');
                /* @var $th TextHelper */
                $this->set('message', t('You left the group "%s"', $th->specialchars($group->getGroupName())));
            }
            $this->group($locale->getID());
        } else {
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
                        $this->redirect('/integrated_localization/groups', 'entered_group', $locale->getID());
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
    public function entered_group($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID);
        if (isset($locale)) {
            $th = Loader::helper('text');
            /* @var $th TextHelper */
            $this->set('message', t('You asked to join the translators group for "%s"', $th->specialchars($locale->getName())));
            $this->group($locale->getID());
        } else {
            $this->view();
        }
    }

    public function kick_user($localeID, $userID)
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
                    $this->redirect('/integrated_localization/groups', 'kicked_user', $locale->getID());
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
    public function kicked_user($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID);
        if (isset($locale)) {
            $this->set('message', t('The user has been removed from the language translators!'));
            $this->group($locale->getID());
        } else {
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
                                $this->redirect('/integrated_localization/groups', 'updated_user_group', $locale->getID(), $user->getUserID());
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
    public function updated_user_group($localeID, $userID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $locale = IntegratedLocale::getByID($localeID, true);
        if (isset($locale)) {
            $user = User::getByUserID($userID);
            if (isset($user)) {
                $this->set('message', t('The user %s has been updated', $user->getUserName()));
            }
            $this->group($locale->getID());
        } else {
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
            foreach (\Gettext\Languages\Language::getAll() as $language) {
                if (strpos($language->id, '_') === false) {
                    $languages[$language->id] = $language->name;
                }
            }
            natcasesort($languages);
            $this->set('languages', $languages);
            $territories = array();
            foreach (\Gettext\Languages\CldrData::getTerritoryNames() as $territoryID => $territoryName) {
                if (preg_match('/^[A-Z][A-Z]$/', $territoryID)) {
                    $territories[$territoryID] = $territoryName;
                }
            }
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
            $s = $this->post('language');
            $languageID = is_string($s) ? $s : '';
            if ($languageID === '') {
                $this->set('error', t('Please specify the language'));
                $this->new_locale();
            } else {
                $s = $this->post('territory');
                $territoryID = is_string($s) ? $s : '';
                if ($territoryID === '') {
                    $this->set('error', t('Please specify the country'));
                    $this->new_locale();
                } else {
                    $builtID = $languageID;
                    if ($territoryID === '-') {
                        $territoryID = '';
                    } else {
                        $builtID .= '_'.$territoryID;
                    }
                    $language = \Gettext\Languages\Language::getById($builtID);
                    if (!isset($language)) {
                        $this->set('error', t("We don't know the locale code '%s'!", $builtID));
                        $this->new_locale();
                    } else {
                        $id = $language->id;
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
                            $pluralCases = array();
                            foreach ($language->categories as $category) {
                                $pluralCases[$category->id] = $category->examples;
                            }
                            try {
                                $newLocale = IntegratedLocale::add($id, $language->name, $language->formula, $pluralCases);
                            } catch (Exception $x) {
                                $this->set('error', $x->getMessage());
                                $this->new_locale();
                            }
                            if (isset($newLocale)) {
                                $this->redirect('/integrated_localization/groups', 'new_locale_added', $newLocale->getID());
                            }
                        }
                    }
                }
            }
        }
    }
    public function new_locale_added($localeID)
    {
        Loader::model('integrated_locale', 'integrated_localization');
        $newLocale = IntegratedLocale::getByID($localeID, true);
        if (isset($newLocale)) {
            $this->set('message', t("Your request to create the translation group for '%s' has been submitted.", $newLocale->getName()));
        }
        $this->view();
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
                    $this->redirect('/integrated_localization/groups', 'group_request_canceled', $name);
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
    public function group_request_canceled($name)
    {
        $th = Loader::helper('text');
        /* @var $th TextHelper */
        $this->set('message', t("Your request to create the '%s' language group has been cancelled.", $th->specialchars($name)));
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
