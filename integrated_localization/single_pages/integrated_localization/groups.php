<?php use Gettext\Translation;
defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this View */

$th = Loader::helper('text');
/* @var $th TextHelper */

$db = Loader::db();
/* @var $db ADODB_mysql */

$fh = Loader::helper('form');
/* @var $fh FormHelper */

if (isset($addingNewLocale) && $addingNewLocale) {
    ?>
    <h2><?php echo $th->specialchars(t('Suggest the creation of a new translation group')); ?></h2>
    <form method="POST" action="<?php echo $this->action('add_new_locale'); ?>" class="form-horizontal">
        <div class="control-group">
            <label class="control-label" for="language"><?php echo t('Language'); ?></label>
            <div class="controls">
                <div class="input"><?php echo $fh->select('language', array_merge(array('' => t('*** Please select')), $languages), false, array('required' => 'required')); ?></div>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="territory"><?php echo t('Country'); ?></label>
            <div class="controls">
                <div class="input"><?php echo $fh->select('territory', array_merge(array('' => t('*** Please select')), $territories, array('-' => t('*** No Country-specific'))), false, array('required' => 'required')); ?></div>
            </div>
        </div>
        <div class="control-group">
            <div class="controls">
                <a href="<?php echo $this->getViewPath(); ?>" class="btn"><?php echo t('Cancel'); ?></a>
                <input type="submit" class="btn btn-primary" />
            </div>
        </div>
    </form>
    <?php
} elseif(isset($viewingLocale) && is_object($viewingLocale)) {
    /* @var $viewingLocale IntegratedLocale */
    ?><h2><?php echo $th->specialchars(t('Translators for %s', $viewingLocale->getName())); ?></h2><?php
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
                                        <a class="btn btn-mini btn-success" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('update_user_group', $viewingLocale->getID(), $member->getUserID(), TranslatorAccess::LOCALE_ASPIRANT, TranslatorAccess::LOCALE_TRANSLATOR); ?>"><?php echo t('Accept as translator'); ?></a>
                                        <a class="btn btn-mini btn-warning" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('update_user_group', $viewingLocale->getID(), $member->getUserID(), TranslatorAccess::LOCALE_ASPIRANT, TranslatorAccess::LOCALE_ADMINISTRATOR); ?>"><?php echo t('Accept as administrator'); ?></a>
                                        <a class="btn btn-mini btn-danger" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('kickuser', $viewingLocale->getID(), $member->getUserID()); ?>"><?php echo t('Deny'); ?></a>
                                        <?php
                                        break;
                                    case TranslatorAccess::LOCALE_TRANSLATOR:
                                        ?>
                                        <a class="btn btn-mini btn-success" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('update_user_group', $viewingLocale->getID(), $member->getUserID(), TranslatorAccess::LOCALE_TRANSLATOR, TranslatorAccess::LOCALE_ADMINISTRATOR); ?>"><?php echo t('Promote to administrators'); ?></a>
                                        <a class="btn btn-mini btn-danger" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('kickuser', $viewingLocale->getID(), $member->getUserID()); ?>"><?php echo t('Remove from translators'); ?></a>
                                        <?php
                                        break;
                                    case TranslatorAccess::LOCALE_ADMINISTRATOR:
                                        ?>
                                        <a class="btn btn-mini btn-warning" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>" href="<?php echo $this->action('update_user_group', $viewingLocale->getID(), $member->getUserID(), TranslatorAccess::LOCALE_ADMINISTRATOR, TranslatorAccess::LOCALE_TRANSLATOR); ?>"><?php echo t('Downgrate to translators'); ?></a>
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
        if (isset($me)) {
            if ($myAccess === TranslatorAccess::NONE) {
                ?><a class="pull-right btn btn-primary" href="<?php echo $this->action('enter_group', $viewingLocale->getID()); ?>"><span><?php echo t('Join this translators group!'); ?></span></a></p><?php
            }
        } else {
            ?><a class="pull-right btn" href="<?php echo View::url('/login?rcID='.$c->getCollectionID()); ?>"><span><?php echo t('Login to join this translators group'); ?></span></a></p><?php
        }
    ?></div><?php
} else {
    /* @var $locales IntegratedLocale[] */
    ?>
    <h2><?php echo t('Translators\' groups'); ?></h2>
    <p><?php echo t('Please select a translators\' group'); ?></p>
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
                            if(isset($myID) && ($locale->getRequestedBy() == $myID)) {
                                ?> <a class="btn btn-mini btn-danger" href="<?php echo $th->specialchars($this->action('cancel_group_request', $locale->getID())); ?>" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure you want to cancel your request?')).')'); ?>"><span><?php echo t('Cancel request'); ?></span></a><?php
                            }
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
    if(User::isLoggedIn()) {
        ?>
        <div>
            <a class="btn btn-" href="<?php echo $th->specialchars($this->action('new_locale')); ?>"><span><?php echo t('Ask the creation of a new language'); ?></span></a>
        </div>
        <?php
    }
    else {
        ?><p><?php echo t('In order to request a new language you have to <a href="%s">login</a>.', View::url('/login?rcID='.$c->getCollectionID())); ?></p><?php
    }
}
