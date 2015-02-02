<?php defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this View */

$cdh = Loader::helper('concrete/dashboard');
/* @var $cdh ConcreteDashboardHelper */

$th = Loader::helper('text');
/* @var $th TextHelper */

$fh = Loader::helper('form');
/* @var $fh FormHelper */

$cih = Loader::helper('concrete/interface');
/* @var $cih ConcreteInterfaceHelper */

/* @var $locales IntegratedLocale[] */

if ($editing) {
    /* @var $editing IntegratedLocale */
    echo $cdh->getDashboardPaneHeaderWrapper(t('Edit %s', $editing->getName()), false, 'span16', false);
    $totalPluralTranslations = $editing->getTotalPluralTranslations();
    ?>
    <script>
    $(document).ready(function() {
        var originalPluralCount = <?php echo json_encode($editing->getPluralCount()); ?>;
        $('#pluralCount').prop({type: 'number', min: 1, max: 6});
        $('#integratedlocalization-locale-details')
            .removeAttr('onsubmit')
            .on('submit', function() {
                var pluralCount = parseInt($('#pluralCount').val(), 10);
                if((!pluralCount) || (pluralCount < 1) || (pluralCount > 6)) {
                    alert(<?php echo json_encode('Please enter the number of plurals, between 1 and 6'); ?>);
                    $('#pluralCount').select().focus();
                    return false;
                }
                $('#pluralCount').val(pluralCount.toString());
                <?php if ($totalPluralTranslations > 0) { ?>
                    if(pluralCount != originalPluralCount) {
                        if(!confirm(<?php echo json_encode(t("WARNING!!!\nIf you change the number of plural rules, all the %d plural translations will be deleted!!!\n\nAre you sure you want to proceed anyway?", $totalPluralTranslations)); ?>)) {
                            return false;
                        }
                    }
                <?php } ?>
                return true;
            })
        ;
        function updateAutoName() {
            $('#name').closest('.clearfix')[$('#auto_name').is(':checked') ? 'hide' : 'show']('fast');
        }
        $('#auto_name').on('change', function() {
            updateAutoName();
        });
        updateAutoName();
        function updateAutoPlural() {
            $('#pluralCount,#pluralRule').closest('.clearfix')[$('#auto_plural').is(':checked') ? 'hide' : 'show']('fast');
        }
        $('#auto_plural').on('change', function() {
            updateAutoPlural();
        });
        updateAutoPlural();
    });
    </script>
    <form method="POST" action="<?php echo $this->action('save', $editing->getID());?>" id="integratedlocalization-locale-details" name="integratedlocalization-locale-details" onsubmit="return false">
        <div class="ccm-pane-body">
            <div class="clearfix">
                <label for="language"><?php echo t('Language'); ?></label>
                <div class="input"><?php echo $fh->select('language', $languages, $editing->getLanguage(), array('required' => 'required')); ?></div>
            </div>
            <div class="clearfix">
                <label for="country"><?php echo t('Country'); ?></label>
                <div class="input"><?php echo $fh->select('country', array_merge(array('' => t('*** No Country')), $countries), $editing->getTerritory()); ?></div>
            </div>
            <div class="clearfix">
                <label for="auto_name"><?php echo t('Auto-generate name'); ?></label>
                <div class="input"><?php echo $fh->checkbox('auto_name', '1', false); ?></div>
            </div>
            <div class="clearfix">
                <label for="name"><?php echo t('Name'); ?></label>
                <div class="input"><?php echo $fh->text('name', $editing->getName(), array('maxlength' => '100', 'required' => 'required')); ?></div>
            </div>
            <div class="clearfix">
                <label for="auto_plural"><?php echo t('Auto-generate plural rules'); ?></label>
                <div class="input"><?php echo $fh->checkbox('auto_plural', '1', false); ?></div>
            </div>
            <div class="clearfix">
                <label for="pluralCount"><?php echo t('# of plurals'); ?></label>
                <div class="input"><?php echo $fh->text('pluralCount', (string) $editing->getPluralCount(), array('required' => 'required', 'class' => 'span1')); ?></div>
            </div>
            <div class="clearfix">
                <label for="pluralRule"><?php echo t('Plural rule'); ?></label>
                <div class="input"><?php echo $fh->text('pluralRule', $editing->getPluralRule(), array('maxlength' => '255', 'required' => 'required', 'class' => 'span12')); ?></div>
            </div>
        </div>
        <div class="ccm-pane-footer">
            <?php echo $cih->submit(t('Save'), false, 'right', 'primary'); ?>
            <?php echo $cih->button(t('Cancel'), View::url('/dashboard/integrated_localization/locales'), 'right'); ?>
        </div>
    </form>
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
                                <th><?php echo t('Name'); ?></th>
                                <th><?php echo t('Identifier'); ?></th>
                                <?php
                                if (!$approved) {
                                    ?>
                                    <th><?php echo t('Requested by'); ?></th>
                                    <th><?php echo t('Requested on'); ?></th>
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
                ?><tr<?php echo $locale->getIsSource() ? ' style="background-color: #fafcba"' : ''; ?>>
                    <td><a <?php
                        if ($locale->getIsSource()) {
                            ?> href="javascript:void(0)" disabled="disabled" onclick="<?php echo $th->specialchars('alert('.json_encode(t("This is the source locale and can't be modified")).')'); ?>"<?php

                        } else {
                            ?> href="<?php echo $th->specialchars(View::url('/dashboard/integrated_localization/locales/', 'edit', $locale->getID())); ?>"<?php
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
                                if (ENABLE_USER_PROFILES) {
                                    ?><a href="<?php echo $th->specialchars(View::url('/profile', $locale->getRequestedBy())); ?>"><?php
                                }
                                echo $th->specialchars($requestedBy->getUserName());
                                if (ENABLE_USER_PROFILES) {
                                    ?></a><?php
                                }
                            } elseif ($locale->getRequestedBy()) {
                                echo t('Deleted user (id: %d)', $locale->getRequestedBy());
                            } else {
                                echo t('Nobody');
                            }
                        ?></td>
                        <td><?php echo $th->specialchars($locale->getRequestedOn()); ?></td>
                        <td style="white-space: nowrap">
                            <?php echo $cih->button(t('Deny'), View::url('/dashboard/integrated_localization/locales', 'delete', $locale->getID()), '', 'small danger', array('onclick' => $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'))); ?>
                            <?php echo $cih->button(t('Approve'), View::url('/dashboard/integrated_localization/locales', 'approve', $locale->getID()), '', 'small success', array('onclick' => $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'))); ?>
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
