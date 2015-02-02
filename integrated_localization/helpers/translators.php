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
}
