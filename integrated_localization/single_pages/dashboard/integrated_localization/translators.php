<?php defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this View */

$cdh = Loader::helper('concrete/dashboard');
/* @var $cdh ConcreteDashboardHelper */

$th = Loader::helper('text');
/* @var $th TextHelper */

$db = Loader::db();
/* @var $db ADODB_mysql */

$cih = Loader::helper('concrete/interface');
/* @var $cih ConcreteInterfaceHelper */

if(isset($viewingLocale) && is_object($viewingLocale)) {
    /* @var $viewingLocale IntegratedLocale */
    echo $cdh->getDashboardPaneHeaderWrapper(t('Translators for %s', $viewingLocale->getName()), false, 'span16', false);
    $askJoin = (isset($me) && ($myAccess === '')) ? true : false;
    ?>
    <div class="ccm-pane-body<?php echo $askJoin ? '' : ' ccm-pane-body-footer'; ?>">
        <?php
        foreach (array(
            array('title' => t('Global administrators'), 'group' => $globalAdministratorsGroup, 'members' => $globalAdministrators),
            array('title' => t('Administrators for %s', $viewingLocale->getName()), 'group' => $groupAdministratorsGroup, 'members' => $groupAdministrators),
            array('title' => t('Translators for %s', $viewingLocale->getName()), 'group' => $groupTranslatorsGroup, 'members' => $groupTranslators),
            array('title' => t('Aspirant translators for %s', $viewingLocale->getName()), 'group' => $groupAspirantTranslatorsGroup, 'members' => $groupAspirantTranslators),
        ) as $current) {
            ?>
            <h4><?php echo $current['title']; ?></h4>
            <?php
            if (empty($current['members'])) {
                ?><div class="alert-message"><?php echo t('No members.'); ?></div><?php
            } else {
                ?><table>
                    <thead><tr>
                        <th><?php echo t('User'); ?></th>
                        <th><?php echo t('Since'); ?></th>
                        <?php
                        if($myAccess === 'admin') {
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
                                        ?> <a href="<?php echo $th->specialchars(View::url('/dashboard/integrated_localization/translators/', 'leave_group', $viewingLocale->getID(), $current['group']->getGroupID())); ?>" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure you want to leave this group?')).')'); ?>"><?php echo t('Leave'); ?></a><?php
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
                            </tr><?php
                        }
                    ?></tbody>
                </table><?php
            }
        }
        ?>    
    </div>
    <?php
    if ($askJoin) {
        ?><div class="ccm-pane-footer">
            <?php echo $cih->button(t('Join this translators group!'), $this->action('enter_group', $viewingLocale->getID()), 'right', 'primary'); ?>
        </div><?php
    }
    echo $cdh->getDashboardPaneFooterWrapper();
} else {
    /* @var $locales IntegratedLocale[] */
    echo $cdh->getDashboardPaneHeaderWrapper(t('Translators'), false, 'span16', false);
    ?>
    <div class="ccm-pane-body ccm-pane-body-footer">
        <h2><?php echo t('Please select a language'); ?></h2>
        <table border="0" cellspacing="0" cellpadding="0" class="ccm-results-list">
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
                                ?><a href="<?php echo $th->specialchars(View::url('/dashboard/integrated_localization/translators/', 'group', $locale->getID())); ?>"><?php
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
    </div>
    <?php
    echo $cdh->getDashboardPaneFooterWrapper();
}
