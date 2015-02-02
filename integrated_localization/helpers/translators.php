<?php defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Handles translatable strings from core/packages
 */
class TranslatorsHelper
{
    /**
     * @return string
     */
    public function getAdministratorsGroupName()
    {
        return 'Translations administrators';
    }
    /**
     * @return string
     */
    public function getAdministratorsGroupDescription()
    {
        return 'Users that can administer all the locales and translations';
    }
    /**
     * return Group|null
     */
    public function getAdministratorsGroup()
    {
        $group = Group::getByName($this->getAdministratorsGroupName());
        if (isset($group) && ($group->isError() || (!$group->getGroupID()))) {
            $group = null;
        }

        return $group;
    }
    /**
     * @param IntegratedLocale|string|null $locale
     * @return string
     */
    public function getCurrentUserAccess($locale = null)
    {
        Loader::model('user');
        $me = User::isLoggedIn() ? new User() : null;

        return $this->getUserAccess($me, $locale);
    }
    /**
     * 
     * @param User|int|null $user
     * @param IntegratedLocale|string|null $locale
     * @return string '', 'aspirant', 'translator', 'admin' 
     */
    public function getUserAccess($user, $locale = null)
    {
        $result = '';
        Loader::model('user');
        if(isset($user) && (!is_object($user))) {
            if(is_string($user)) {
                if($user === '') {
                    $user = null;
                }
                elseif(is_numeric($user)) {
                    $user = @intval($user);
                }
            }
            if(is_int($user) && ($user !== 0)) {
                $user = User::getByUserID($user);
            }
        }
        if(is_object($user)) {
            $g = $this->getAdministratorsGroup();
            if($g && $user->inGroup($g)) {
                $result = 'admin';
            }
            else {
                if(isset($locale) && (!is_object($locale))) {
                    if(is_string($locale) && ($locale !== '')) {
                        Loader::model('integrated_locale', 'integrated_localization');
                        $locale = IntegratedLocale::getByID($locale);
                    }
                    else {
                        $locale = null;
                    }
                }
                if(isset($locale)) {
                    $g = $locale->getAdministratorsGroup();
                    if($g && $user->inGroup($g)) {
                        $result = 'admin';
                    }
                    else {
                        $g = $locale->getTranslatorsGroup();
                        if($g && $user->inGroup($g)) {
                            $result = 'translator';
                        }
                        else {
                            $g = $locale->getAspirantTranslatorsGroup();
                            if($g && $user->inGroup($g)) {
                                $result = 'aspirant';
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }
}
