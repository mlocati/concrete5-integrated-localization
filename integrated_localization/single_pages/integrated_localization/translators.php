<?php use Gettext\Translation;
defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this View */

$th = Loader::helper('text');
/* @var $th TextHelper */

$db = Loader::db();
/* @var $db ADODB_mysql */

$cih = Loader::helper('concrete/interface');
/* @var $cih ConcreteInterfaceHelper */

if(isset($viewingLocale) && is_object($viewingLocale)) {
    /* @var $viewingLocale IntegratedLocale */
    $pageTitle = t('Translators for %s', $viewingLocale->getName());
    foreach (array(
        array('title' => t('Global administrators'), 'group' => $globalAdministratorsGroup, 'members' => $globalAdministrators, 'accessLevel' => TranslatorAccess::GLOBAL_ADMINISTRATOR),
        array('title' => t('Administrators for %s', $viewingLocale->getName()), 'group' => $groupAdministratorsGroup, 'members' => $groupAdministrators, 'accessLevel' => TranslatorAccess::LOCALE_ADMINISTRATOR),
        array('title' => t('Translators for %s', $viewingLocale->getName()), 'group' => $groupTranslatorsGroup, 'members' => $groupTranslators, 'accessLevel' => TranslatorAccess::LOCALE_TRANSLATOR),
        array('title' => t('Aspirant translators for %s', $viewingLocale->getName()), 'group' => $groupAspirantTranslatorsGroup, 'members' => $groupAspirantTranslators, 'accessLevel' => TranslatorAccess::LOCALE_ASPIRANT),
    ) as $current) {
        ?>
        <h4><?php echo $current['title']; ?></h4>
        <?php
        if (empty($current['members'])) {
            ?><div class="alert"><?php echo t('No members.'); ?></div><?php
        } else {
            $canTakeActions = (($myAccess >= TranslatorAccess::LOCALE_ADMINISTRATOR) && ($current['accessLevel'] < $myAccess)) ? true : false;
            ?><table class="table table-striped">
                <thead><tr>
                    <th><?php echo t('User'); ?></th>
                    <th><?php echo t('Since'); ?></th>
                    <?php
                    if ($canTakeActions) {
                        ?><th><?php echo t('Action'); ?></th><?php
                    }
                    ?>
                </tr></thead>
                <tbody><?php
                    foreach($current['members'] as $member) {
                        /* @var $member User */
                        ?><tr>
                            <td><?php
                                if(isset($me) && ($me->getUserID() == $member->getUserID())) {
                                    echo $th->specialchars($member->getUserName());
                                    ?> <a class="btn btn-mini btn-danger" href="<?php echo $th->specialchars($this->action('leave_group', $viewingLocale->getID(), $current['group']->getGroupID())); ?>" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure you want to leave this group?')).')'); ?>"><span><?php echo t('Leave'); ?></span></a><?php
                                }
                                else {
                                    if (ENABLE_USER_PROFILES) {
                                        ?><a href="<?php echo $th->specialchars(View::url('/profile', $member->getUserID())); ?>"><?php
                                    }
                                    echo $th->specialchars($member->getUserName());
                                    if (ENABLE_USER_PROFILES) {
                                        ?></a><?php
                                    }
                                }
                            ?></td>
                            <td><?php
                                $dt = $db->GetOne('SELECT ugEntered FROM UserGroups WHERE uID = ? AND gID = ?', array($member->getUserID(), $current['group']->getGroupID()));
                                if ($dt) {
                                    echo $th->specialchars($dt);
                                } else {
                                    echo '-';
                                }
                            ?></td>
                            <?php
                            if ($canTakeActions) {
                                ?><td><?php
                                switch($current['accessLevel']) {
                                    case TranslatorAccess::LOCALE_ASPIRANT:
                                        ?>
                                        <a class="btn btn-mini btn-success" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('accept_aspirant', $viewingLocale->getID(), $member->getUserID()); ?>"><?php echo t('Accept'); ?></a>
                                        <a class="btn btn-mini btn-danger" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('kickuser', $viewingLocale->getID(), $member->getUserID()); ?>"><?php echo t('Deny'); ?></a>
                                        <?php
                                        break;
                                    case TranslatorAccess::LOCALE_TRANSLATOR:
                                        ?>
                                        <a class="btn btn-mini btn-success" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('promote_to_administrator', $viewingLocale->getID(), $member->getUserID()); ?>"><?php echo t('Promote to administrators'); ?></a>
                                        <a class="btn btn-mini btn-danger" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('kickuser', $viewingLocale->getID(), $member->getUserID()); ?>"><?php echo t('Remove from translators'); ?></a>
                                        <?php
                                        break;
                                    case TranslatorAccess::LOCALE_ADMINISTRATOR:
                                        ?>
                                        <a class="btn btn-mini btn-warning" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('downgrade_to_translators', $viewingLocale->getID(), $member->getUserID()); ?>"><?php echo t('Downgrate to translators'); ?></a>
                                        <a class="btn btn-mini btn-danger" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('kickuser', $viewingLocale->getID(), $member->getUserID()); ?>"><?php echo t('Remove from translators'); ?></a>
                                        <?php
                                        break;
                                }
                                ?></td><?php
                            }
                            ?>
                        </tr><?php
                    }
                ?></tbody>
            </table><?php
        }
    }
    ?><div><?php
        ?><a class="pull-left btn" href="<?php echo $this->action(''); ?>"><span><?php echo t('Back to language list'); ?></span></a></p><?php
        if (isset($me) && ($myAccess === TranslatorAccess::NONE)) {
            ?><a class="pull-right btn btn-primary" href="<?php echo $this->action('enter_group', $viewingLocale->getID()); ?>"><span><?php echo t('Join this translators group!'); ?></span></a></p><?php
        }
    ?></div><?php
} else {
    /* @var $locales IntegratedLocale[] */
    $pageTitle = t('Translators');
    ?>
    <h2><?php echo t('Please select a language'); ?></h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?php echo t('Name'); ?></th>
                <th><?php echo t('Identifier'); ?></th>
                <th><?php echo t('Status'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($locales as $locale) {
                /* @var $locale IntegratedLocale */
                ?><tr>
                    <td><?php
                        if((!$locale->getIsSource() && $locale->getApproved())) {
                            ?><a href="<?php echo $th->specialchars($this->action('group', $locale->getID())); ?>"><?php
                        }
                        echo $th->specialchars($locale->getName());
                        if($locale->getApproved() && (!$locale->getIsSource())) {
                            ?></a><?php
                        }
                    ?></td>
                    <td><?php echo $th->specialchars($locale->getName()); ?></td>
                    <td><?php
                        if($locale->getIsSource()) {
                            echo t('Source language');
                        } elseif (!$locale->getApproved()) {
                            echo t('Awaiting approval');
                        } else {
                            echo t('Active');
                        }
                    ?></td>
                </tr><?php
            }
            ?>
        </tbody>
    </table>
    <?php
}
