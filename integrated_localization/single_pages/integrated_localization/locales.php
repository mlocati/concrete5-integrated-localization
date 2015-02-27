<?php defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this View */

$th = Loader::helper('text');
/* @var $th TextHelper */

$fh = Loader::helper('form');
/* @var $fh FormHelper */

/* @var $locales IntegratedLocale[] */

if ($editing) {
    /* @var $editing IntegratedLocale */
    ?><h2><?php echo $th->specialchars(t('Edit %s', $editing->getName())); ?></h2><?php
    $totalPluralTranslations = $editing->getTotalPluralTranslations();
    ?>
    <script>
    $(document).ready(function() {
        var $form = $('#integratedlocalization-locale-details');
        var originalPluralCount = <?php echo json_encode($editing->getPluralCount()); ?>;
        function updateState() {
            var numEnabled = 0;
            $('input.pluralcase-use').each(function() {
                var $checkbox = $(this);
                var pluralCase = $checkbox.attr('data-pluralcase');
                var $examples = $('#pluralcase-' + pluralCase + '-examples');
                if(pluralCase === 'other') {
                    $checkbox.attr('checked', 'checked');
                }
                if($checkbox.is(':checked')) {
                    $examples.removeAttr('readonly').attr('required', 'required');
                    numEnabled++;
                } else {
                    $examples.removeAttr('required').attr('readonly', 'readonly');
                }
            });
            return numEnabled;
        }
        $form.find('input.pluralcase-use').on('click', function() {
            updateState();
        })
        updateState();
        $form
            .removeAttr('onsubmit')
            .on('submit', function() {
                var pluralCount = updateState();
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
            $('#name').closest('.control-group')[$('#auto_name').is(':checked') ? 'hide' : 'show']('fast');
        }
        $('#auto_name').on('change', function() {
            updateAutoName();
        });
        updateAutoName();
        function updateAutoPlural() {
            $('#pluralCount,#pluralFormula').closest('.control-group')[$('#auto_plural').is(':checked') ? 'hide' : 'show']('fast');
        }
        $('#auto_plural').on('change', function() {
            updateAutoPlural();
        });
        updateAutoPlural();
    });
    </script>
    <form method="POST" action="<?php echo $this->action('save', $editing->getID());?>" id="integratedlocalization-locale-details" name="integratedlocalization-locale-details" onsubmit="return false" class="form-horizontal">
        <div class="control-group">
            <label class="control-label" for="language"><?php echo t('Language'); ?></label>
            <div class="controls">
                <div class="input"><?php echo $fh->select('language', $languages, $editing->getLanguage(), array('required' => 'required')); ?></div>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="country"><?php echo t('Country'); ?></label>
            <div class="controls">
                <div class="input"><?php echo $fh->select('country', array_merge(array('' => t('*** No Country')), $countries), $editing->getTerritory()); ?></div>
            </div>
        </div>
        <div class="control-group">
            <div class="controls">
                <label class="checkbox">
                    <?php echo $fh->checkbox('auto_name', '1', true); ?>
                    <?php echo t('Auto-generate name'); ?>
                </label>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="name"><?php echo t('Name'); ?></label>
            <div class="controls">
                <div class="input"><?php echo $fh->text('name', $editing->getName(), array('maxlength' => '100', 'required' => 'required')); ?></div>
            </div>
        </div>
        <div class="control-group">
            <div class="controls">
                <label class="checkbox">
                    <?php echo $fh->checkbox('auto_plural', '1', false); ?>
                    <?php echo t('Auto-generate plural rules'); ?>
                </label>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="pluralFormula"><?php echo t('Plurals'); ?></label>
            <div class="controls">
                <p><b><?php echo t('Formula'); ?></b></p>
                <div class="input"><?php echo $fh->text('pluralFormula', $editing->getPluralFormula(), array('maxlength' => '400', 'required' => 'required', 'class' => 'span9')); ?></div>
                <table class="table">
                    <thead><tr>
                        <th><?php echo t('Plural case'); ?></th>
                        <th><?php echo t('Examples'); ?></th>
                    </tr></thead>
                    <tbody><?php
                        $pluralCases = $editing->getPluralCases();
                        foreach(array(
                            'zero' => t('Zero'),
                            'one' => t('One'),
                            'two' => t('Two'),
                            'few' => t('Few'),
                            'many' => t('Many'),
                            'other' => t('Other'),
                        ) as $pluralCaseID => $pluralCaseName) {
                            $useAttrs = array('class' => 'pluralcase-use', 'data-pluralcase' => $pluralCaseID);
                            $examplesAttrs = array('class' => 'pluralcase-example', 'data-pluralcase' => $pluralCaseID, 'span7');
                            if ($pluralCaseID === 'other') {
                                $useAttrs['readonly'] = 'readonly';
                                $useAttrs['onclick'] = 'return false';
                                $examplesAttrs['required'] = 'required';
                            }
                            ?><tr>
                                <th><label class="checkbox"><?php echo $fh->checkbox("pluralcase-$pluralCaseID", '1',  isset($pluralCases[$pluralCaseID]), $useAttrs); ?><?php echo $pluralCaseName; ?></label></th>
                                <td><?php echo $fh->text("pluralcase-$pluralCaseID-examples", isset($pluralCases[$pluralCaseID]) ? $pluralCases[$pluralCaseID] : '', $examplesAttrs); ?></td>
                            </tr><?php
                        }
                    ?></tbody>
                </table>
            </div>
        </div>
        <div class="clearfix">
            <a class="pull-right btn btn-default" href="<?php echo $this->getViewPath(); ?>"><span><?php echo t('Cancel'); ?></span></a>
            <input type="submit" class="pull-right btn btn-primary" value="<?php echo t('Save'); ?>" />
        </div>
    </form>
    <?php
} else {
    ?><h2><?php echo t('Locales'); ?></h2><?php
    foreach (array(false, true) as $approved) {
        ?><h2><?php echo $approved ? t('Approved locales') : t('Locales awaiting approval'); ?></h2><?php
        $found = false;
        foreach ($locales as $locale) {
            /* @var $locale IntegratedLocale */
            if ($locale->getApproved() !== $approved) {
                continue;
            }
            if (!$found) {
                ?>
                <table class="table table-striped">
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
            $cellAttrs = $locale->getIsSource() ? ' style="background-color: #fafcba"' : '';
            ?><tr>
                <td<?php echo $cellAttrs; ?>><a <?php
                    if ($locale->getIsSource()) {
                        ?> href="javascript:void(0)" disabled="disabled" onclick="<?php echo $th->specialchars('alert('.json_encode(t("This is the source locale and can't be modified")).')'); ?>"<?php

                    } else {
                        ?> href="<?php echo $th->specialchars($this->action('edit', $locale->getID())); ?>"<?php
                    }
                    ?>><?php
                    echo $th->specialchars($locale->getName());
                ?></a></td>
                <td<?php echo $cellAttrs; ?>><?php echo $th->specialchars($locale->getID()); ?></td>
                <?php
                if (!$approved) {
                    ?>
                    <td<?php echo $cellAttrs; ?>><?php
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
                    <td<?php echo $cellAttrs; ?>><?php echo $th->specialchars($locale->getRequestedOn()); ?></td>
                    <td<?php echo $cellAttrs; ?> style="white-space: nowrap">
                        <a class="btn btn-danger" href="<?php echo $this->action('delete', $locale->getID()); ?>" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>"><?php echo t('Deny'); ?></a>
                        <a class="btn btn-success" href="<?php echo $this->action('approve', $locale->getID()); ?>" onclick="<?php echo $th->specialchars('return confirm('.json_encode(t('Are you sure?')).')'); ?>"><?php echo t('Approve'); ?></a>
                    </td<?php echo $cellAttrs; ?>>
                    <?php
                }
            ?>
            </tr><?php
        }
        if ($found) {
            ?></tbody></table><?php

        } else {
            ?><div class="alert"><?php echo t('No locales found.'); ?></div><?php
        }
    }
}
