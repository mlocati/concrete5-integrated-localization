<?php defined('C5_EXECUTE') or die('Access Denied.');

$cdh = Loader::helper('concrete/dashboard');
/* @var $cdh ConcreteDashboardHelper */

$th = Loader::helper('text');
/* @var $th TextHelper */

$cih = Loader::helper('concrete/interface');
/* @var $cih ConcreteInterfaceHelper */

/* @var $locales IntegratedLocale[] */

if ($editingLocale) {
    /* @var $editingLocale IntegratedLocale */
    echo $cdh->getDashboardPaneHeaderWrapper(t('Edit %s', $editingLocale->getName()), false, 'span16', false);
    ?>
    <div class="ccm-pane-body">

    </div>
    <div class="ccm-pane-footer">
        <?php echo $cih->submit(t('Save'), false, 'right', 'primary'); ?>
        <?php echo $cih->button(t('Cancel'), View::url('/dashboard/integrated_localization/locales'), 'right'); ?>
    </div>
    <?php
    echo $cdh->getDashboardPaneFooterWrapper();
} else {
    echo $cdh->getDashboardPaneHeaderWrapper(t('Locales'), false, 'span16', false);
    ?>
    <div class="ccm-pane-body ccm-pane-body-footer">
        <?php
        foreach (array(false, true) as $approved) {
            ?><h2><?php echo $approved ? t('Approved locales') : t('Locales awaiting approval');
            ?></h2><?php
            $found = false;
            foreach ($locales as $locale) {
                /* @var $locale IntegratedLocale */
                if ($locale->getApproved() !== $approved) {
                    continue;
                }
                if (!$found) {
                    ?>
                    <table border="0" cellspacing="0" cellpadding="0" class="ccm-results-list">
                        <thead>
                            <tr>
                                <th><?php echo t('Name');
                    ?></th>
                                <th><?php echo t('Identifier');
                    ?></th>
                                <?php
                                if (!$approved) {
                                    ?>
                                    <th><?php echo t('Requested by');
                                    ?></th>
                                    <th><?php echo t('Requested on');
                                    ?></th>
                                    <th style="width: 100px"></th>
                                    <?php

                                }
                    ?>
                            </tr>
                        </thead>
                        <tbody>
                    <?php
                    $found = true;
                }
                ?><tr<?php echo $locale->getIsSource() ? ' style="background-color: #fafcba"' : '';
                ?>>
                    <td><a <?php
                        if ($locale->getIsSource()) {
                            ?> href="javascript:void(0)" disabled="disabled" onclick="<?php echo $th->specialchars('alert('.json_encode(t("This is the source locale and can't be modified")).')'); ?>"<?php

                        } else {
                            ?> href="<?php echo $th->specialchars(View::url('/dashboard/integrated_localization/locales/?ilID='.rawurlencode($locale->getID()))); ?>"<?php
                        }
                        ?>><?php
                        echo $th->specialchars($locale->getName());
                    ?></a></td>
                    <td><?php echo $th->specialchars($locale->getName()); ?></td>
                    <?php
                    if (!$approved) {
                        ?>
                        <td><?php
                            $requestedBy = $locale->getRequestedBy() ? User::getByUserID($locale->getRequestedBy()) : null;
                            if (is_object($requestedBy) && $requestedBy->getUserID()) {
                                ?><a href="<?php echo $th->specialchars(View::url('/index.php/dashboard/users/search?uID='.$locale->getRequestedBy()));
                                ?>"><?php echo $th->specialchars($requestedBy->getUserName());
                                ?></a><?php
                            } elseif ($locale->getRequestedBy()) {
                                echo t('Deleted user (id: %d)', $locale->getRequestedBy());
                            } else {
                                echo t('Nobody');
                            }
                        ?></td>
                        <td><?php echo $th->specialchars($locale->getRequestedOn());
                        ?></td>
                        <td style="white-space: nowrap">
                            <?php echo $cih->button(t('Deny'), View::url('/dashboard/integrated_localization/locales/set_approved?approve=no&ilID='.rawurlencode($locale->getID())), '', 'small danger', array('onclick' => $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'))); ?>
                            <?php echo $cih->button(t('Approve'), View::url('/dashboard/integrated_localization/locales/set_approved?approve=yes&ilID='.rawurlencode($locale->getID())), '', 'small success', array('onclick' => $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'))); ?>
                        </td>
                        <?php
                    }
                ?>
                </tr><?php
            }
            if ($found) {
                ?></tbody></table><?php

            } else {
                ?><div class="alert-message"><?php echo t('No locales found.'); ?></div><?php
            }
        }
    ?>
    </div>
    <?php
    echo $cdh->getDashboardPaneFooterWrapper();
}
