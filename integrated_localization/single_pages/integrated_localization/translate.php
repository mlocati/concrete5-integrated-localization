<?php defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this View */

$th = Loader::helper('text');
/* @var $th TextHelper */

$languagePlaceholderURL = $this->action($tab, 'LOCALE_PLACEHOLDER');
if (isset($locales['selected']) && isset($selectedVersion)) {
    switch ($tab) {
        case 'core_development':
        case 'core_releases':
            $languagePlaceholderURL = $this->action($tab, 'LOCALE_PLACEHOLDER', preg_replace('/^dev-/', '', $selectedVersion));
            break;
        case 'packages':
            if (isset($selectedPackage)) {
                $languagePlaceholderURL = $this->action($tab, 'LOCALE_PLACEHOLDER', $selectedPackage, $selectedVersion);
            }
            break;
    }
}
?>
<div>
    <?php echo t('Selected language:'); ?>
    <select onchange="<?php echo $th->specialchars('window.location.href = '.json_encode($languagePlaceholderURL).'.replace("LOCALE_PLACEHOLDER", this.value);'); ?>"><?php
        if (!isset($locales['selected'])) {
            ?><option value="" selected="selected"><?php echo t('*** Please select'); ?></option><?php
        }
        if ((count($locales['mine']) > 0) && (count($locales['notMine']) > 0)) {
            ?>
            <optgroup label="<?php echo t('My languages'); ?>">
                <?php
                foreach ($locales['mine'] as $l) {
                    ?><option value="<?php echo $th->specialchars(rawurlencode($l->getID())); ?>"<?php echo ($locales['selected'] === $l) ? ' selected="selected"' : ''; ?>><?php echo $th->specialchars($l->getName()); ?></option><?php
                }
                ?>
            </optgroup>
            <optgroup label="<?php echo t('Other languages'); ?>">
                <?php
                foreach ($locales['notMine'] as $l) {
                    ?><option value="<?php echo $th->specialchars(rawurlencode($l->getID())); ?>"<?php echo ($locales['selected'] === $l) ? ' selected="selected"' : ''; ?>><?php echo $th->specialchars($l->getName()); ?></option><?php
                }
                ?>
            </optgroup>
            <?php
        } else {
            foreach ($locales['all'] as $l) {
                ?><option value="<?php echo $th->specialchars(rawurlencode($l->getID())); ?>"<?php echo ($locales['selected'] === $l) ? ' selected="selected"' : ''; ?>><?php echo $th->specialchars($l->getName()); ?></option><?php
            }
        }
    ?></select>
</div>

<?php
if (!isset($locales['selected'])) {
    return;
}
?>
<ul class="integrated_localization-tabs">
  <li<?php echo ($tab === 'core_development') ? ' class="active"' : ''; ?>><a href="<?php echo $this->action('core_development', $locales['selected']->getID()); ?>"><?php echo t('Core versions - Development'); ?></a></li>
  <li<?php echo ($tab === 'core_releases') ? ' class="active"' : ''; ?>><a href="<?php echo $this->action('core_releases', $locales['selected']->getID()); ?>"><?php echo t('Core versions - Releases'); ?></a></li>
  <li<?php echo ($tab === 'packages') ? ' class="active"' : ''; ?>><a href="<?php echo $this->action('packages', $locales['selected']->getID()); ?>"><?php echo t('Packages'); ?></a></li>
</ul>
<?php
switch($tab) {
    case 'core_development':
    case 'core_releases':
        if (isset($selectedVersion)) {
            $selectedPackage = '_';
            $selectedPackageNameWithVersion = $versions[$selectedVersion];
        } else {
            ?>
            <table class="table table-striped table-condensed table-bordered table-hover">
                <thead><tr>
                    <th><?php echo t('Version'); ?></th>
                    <th><?php echo t('Details'); ?></th>
                    <th><?php echo t('Progress'); ?></th>
                </tr></thead>
                <tbody><?php
                    foreach ($versions as $version => $name) {
                        $stats = $this->controller->getVersionStats('_', $version, $locales['selected']);
                        ?>
                        <tr>
                            <th><a href="<?php echo $this->action($tab, $locales['selected']->getID(), preg_replace('/^dev-/', '', $version)); ?>"><?php echo $th->specialchars($name); ?></a></th>
                            <td><?php echo t('%1$s out of %2$s', $stats['translated'], $stats['total']); ?></td>
                            <td style="width: 120px"><div class="integrated_localization-progress integrated_localization-progress-<?php echo floor($stats['progress'] / 10); ?>" title="<?php echo t('%s %%', $stats['progress']); ?>">
                                <span style="width: <?php echo $stats['progress']; ?>%"><?php echo t('%s %%', $stats['progress']); ?></span>
                            </div></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <?php
        }
        break;
    case 'packages':
        if (isset($selectedPackage) && isset($selectedVersion)) {
            $selectedPackageNameWithVersion = ucwords(str_replace('_', ' ', $selectedPackage)).' v'.$selectedVersion;
        } else {
            ?>
            <script>$(document).ready(function() {
var LOCALSTORAGE_KEY = 'integratedLocalization.translate.package.search',
    SELECTED_TEMPLATE = <?php echo json_encode($this->action($tab, $locales['selected']->getID(), '[[PACKAGE]]', '[[VERSION]]')); ?>,
    $form = $('#integrated_localization-package-search'),
    $search = $form.find('input.search-query')
    $submit = $form.find('input[type="submit"]');
var status = (function() {
    var isBusy = true;
    return {
        busy: function(set) {
            if ((typeof(set) === 'undefined') || (set === null)) {
                return isBusy;
            }
            isBusy = !!set;
            if (isBusy) {
                $search.attr('readonly', 'readonly');
                $submit.attr('disabled', 'disabled');
            } else {
                $search.removeAttr('readonly');
                $submit.removeAttr('disabled');
            }
        }
    }
})();
$search.on('change keydown click', function() {
    $form.find('span').css('visibility', 'hidden');
});
function search() {
    if (status.busy()) {
        return;
    }
    if (window.localStorage) {
        window.localStorage.removeItem(LOCALSTORAGE_KEY);
    }
    var q = $.trim($search.val());
    if (!/[0-9a-z]{4,}/i.test(q)) {
        $form.find('span').css('visibility', 'visible');
        $search.focus();
        return;
    }
    status.busy(true);
    $('.integrated_localization-package-results').hide('fast');
    $.ajax({
        async: true,
        cache: true,
        data: {q: q},
        dataType: 'json',
        type: 'GET',
        url: <?php echo json_encode($this->action('search_package', $locales['selected']->getID())); ?>
    })
    .done(function(data) {
        if (data === false) {
            $('#integrated_localization-package-results-no').show('fast');
            return;
        } else {
            if (window.localStorage) {
                window.localStorage.setItem(LOCALSTORAGE_KEY, q);
            }
            var $tb = $('#integrated_localization-package-results-yes tbody').empty();
            $.each(data, function(pHandle, pData) {
                var $td0 = null, $showOthers = null;
                $.each(pData.versions, function(index, vData) {
                    var $tr = $('<tr' + ((index === 0) ? '' : ' style="display: none"') + ' />'), $c;
                    $tr
                        .append($c = $('<th />')
                            .append($('<a />')
                                .attr('href', SELECTED_TEMPLATE.replace(/\[\[PACKAGE\]\]/g, pHandle).replace(/\[\[VERSION\]\]/g, vData.v))
                                .text(pData.name + ' v' + vData.v)
                            )
                        )
                        .append($('<td />').text(
                            <?php echo json_encode(t('%1$s out of %2$s')); ?>
                            .replace(/%1\$s/, vData.stats.translated.toString())
                            .replace(/%2\$s/, vData.stats.total.toString())
                         ))
                         .append($('<td style="width: 120px" />')
                             .append($('<div' +
                                 ' class="integrated_localization-progress integrated_localization-progress-' + Math.floor(vData.stats.progress / 10) + '"' +
                                 ' title="' + vData.stats.progress + ' %" />'
                                 )
                                 .append($('<span style="width: ' + vData.stats.progress + '%" />')
                                     .text(vData.stats.progress + ' %')
                                 )
                             )
                         );
                    $tb.append($tr);
                    if ($td0 === null) {
                        $td0 = $c;
                    } else {
                        if ($showOthers === null) {
                            $td0.append($showOthers = $('<a href="javascript:void(0)" style="font-weight: normal" class="pull-right" />')
                               .text(<?php echo json_encode(t('show older versions')); ?>)
                               .data('rows', [])
                               .data('rowsShown', false)
                               .on('click', function() {
                                   var $me = $(this), show = !$me.data('rowsShown');
                                   $me.data('rowsShown', show);
                                   $.each($me.data('rows'), function() {
                                       this[show ? 'show' : 'hide']('fast');
                                   });
                                   $me.text(show ? <?php echo json_encode(t('hide older versions')); ?> : <?php echo json_encode(t('show older versions')); ?>)
                               })
                            );
                        }
                        $showOthers.data('rows').push($tr);
                    }
                });
            });
            $('#integrated_localization-package-results-yes').show('fast');
        }
    })
    .fail(function(xhr, status, error) {
        if (xhr.status === 400) {
            alert(xhr.responseText);
        } else {
            alert(status);
        }
    })
    .always(function() {
        status.busy(false);
    });
}
$form.removeAttr('onsubmit').on('submit', function() { search(); return false; });
status.busy(false);
if (window.localStorage) {
    var initialSearch = window.localStorage.getItem(LOCALSTORAGE_KEY);
    if ((typeof(initialSearch) === 'string') && (initialSearch !== '')) {
        $search.val(initialSearch);
        search();
    }
}
            });</script>
            <form class="form-search" onsubmit="return false" id="integrated_localization-package-search">
                <input type="search" class="input-medium search-query" readonly="readonly" />
                <input type="submit" class="btn btn-primary" disabled="disabled" value="<?php echo t('Search'); ?>" />
                <span style="visibility: hidden"><?php echo t('Please be more specific...'); ?></span>
            </form>
            <div class="integrated_localization-package-results alert" style="display: none" id="integrated_localization-package-results-no">
                <?php echo t('No package found.'); ?>
            </div>
            <div class="integrated_localization-package-results" style="display: none" id="integrated_localization-package-results-yes">
                <table class="table table-hover table-condensed table-bordered">
                    <thead><tr>
                        <th><?php echo t('Package'); ?></th>
                        <th><?php echo t('Details'); ?></th>
                        <th><?php echo t('Progress'); ?></th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
            <?php
        }
        break;
}

if (isset($selectedPackage) && isset($selectedVersion)) {
    $stats = $this->controller->getVersionStats($selectedPackage, $selectedVersion, $locales['selected'], true);

    $trh = Loader::helper('translators', 'integrated_localization');
    /* @var $trh TranslatorsHelper */

    $isLoggedIn = false;
    $isCoordinator = false;
    $localeAccess = TranslatorAccess::NONE;

    if (User::isLoggedIn()) {
        $localeAccess = $trh->getCurrentUserAccess($locales['selected']);
        switch ($trh->getCurrentUserAccess($locales['selected'])) {
            case TranslatorAccess::SITE_ADMINISTRATOR:
            case TranslatorAccess::LOCALE_ADMINISTRATOR:
            case TranslatorAccess::GLOBAL_ADMINISTRATOR:
                $isCoordinator = true;
                break;
        }
    }
    ?>
    <h3><?php echo $th->specialchars($selectedPackageNameWithVersion); ?></h3>
    <fieldset>
        <legend><?php echo t('Statistics'); ?></legend>
        <table class="table table-bordered" style="width: auto">
            <tr>
                <th><?php echo t('Total number of strings'); ?></th>
                <td colspan="2"><?php echo $stats['total']; ?></td>
            </tr>
            <tr>
                <th><?php echo t('Translated strings'); ?></th>
                <td><?php echo $stats['translated']; ?></td>
                <td><?php echo t('%.2f %%', ($stats['translated'] * 100) / $stats['total']); ?></td>
            </tr>
            <tr>
                <th><?php echo t('Approved strings'); ?></th>
                <td><?php echo $stats['approved']; ?></td>
                <td><?php echo t('%.2f %%', ($stats['approved'] * 100) / $stats['total']); ?></td>
            </tr>
        </table>
    </fieldset>
    <fieldset>
        <legend><?php echo t('Translate Online'); ?></legend>
        <?php
        if ($localeAccess >= TranslatorAccess::LOCALE_TRANSLATOR) {
            ?><p><a href="<?php echo View::url('/integrated_localization/translate/online', 'view', $selectedPackage, $selectedVersion, $locales['selected']->getID()); ?>" class="btn btn-default"><span><?php echo t('Translate'); ?></span></a></p><?php
        } elseif ($localeAccess === TranslatorAccess::LOCALE_ASPIRANT) {
            ?><div class="alert"><?php echo t('You have to wait that your application request will be approved in order to help us with translations'); ?></div><?php
        } elseif ($isLoggedIn) {
            ?><p><?php echo t('Do you want to help us translating? <a href="%1$s">Click here</a> to join the %2$s translation group!', View::url('/integrated_localization/groups', 'group', $locales['selected']->getID()), $th->specialchars($locales['selected']->getName())); ?></p><?php
        } else {
            ?><p><?php echo t('Do you want to help us translating? <a href="%3$s">Login</a> and <a href="%1$s">click here</a> to join the %2$s translation group!', View::url('/integrated_localization/groups', 'group', $locales['selected']->getID()), $th->specialchars($locales['selected']->getName()), View::url('/login?rcID='.$c->getCollectionID())); ?></p><?php
        }
        ?>
    </fieldset>
    <fieldset>
        <legend><?php echo t('Download Translations'); ?></legend>
        <?php
        $hasUntranslated = ($stats['translated'] < $stats['total']) ? true : false;
        $hasTranslated = ($stats['translated'] > 0) ? true : false;
        $hasUnapproved = ($stats['approved'] < $stats['translated']) ? true : false;
        ?>
        <table class="table table-border" style="width: auto">
            <tbody>
                <tr>
                    <th><?php echo t('Compiled format'); ?><br /><small><?php echo t('Useful for users'); ?></small></th>
                    <td colspan="3"><a href="<?php echo $this->action('download', $selectedPackage, $selectedVersion, $locales['selected']->getID(), 'mo') ?>"><?php echo t('download'); ?></a></td>
                </tr>
                <tr>
                    <th><?php echo t('Text format'); ?><br /><small><?php echo t('Useful for translators'); ?></small></th>
                    <td><a href="<?php echo $this->action('download', $selectedPackage, $selectedVersion, $locales['selected']->getID(), 'po'); ?>"><?php echo t('all strings'); ?></a></td>
                    <td><a<?php
                        if ($hasUntranslated) {
                            ?> href="<?php echo $this->action('download', $selectedPackage, $selectedVersion, $locales['selected']->getID(), 'po'); ?>?untranslated"<?php
                        } else {
                            ?> href="javascript:void(0)" disabled="disabled" style="color: gray; cursor: text; text-decoration: none"<?php
                        }
                        ?>><?php echo t('only untranslated strings'); ?></a><?php
                    ?></td>
                    <td><a<?php
                        if ($hasTranslated) {
                            ?> href="<?php echo $this->action('download', $selectedPackage, $selectedVersion, $locales['selected']->getID(), 'po'); ?>?translated"<?php
                        } else {
                            ?> href="javascript:void(0)" disabled="disabled" style="color: gray; cursor: text; text-decoration: none"<?php
                        }
                        ?>><?php echo t('only translated strings'); ?></a>
                    </td>
                </tr>
                <tr>
                    <th><?php echo t('Text format with unapproved strings marked as fuzzy'); ?><br /><small><?php echo t('Useful for translator coordinators'); ?></small></th>
                    <td><a<?php
                        if ($hasUnapproved) {
                            ?> href="<?php echo $this->action('download', $selectedPackage, $selectedVersion, $locales['selected']->getID(), 'po'); ?>?fuzzy"<?php
                        } else {
                            ?> href="javascript:void(0)" disabled="disabled" style="color: gray; cursor: text; text-decoration: none"<?php
                        }
                        ?>><?php echo t('all strings'); ?></a>
                    </td>
                    <td></td>
                    <td><a<?php
                        if ($hasUnapproved && $hasTranslated) {
                            ?> href="<?php echo $this->action('download', $selectedPackage, $selectedVersion, $locales['selected']->getID(), 'po'); ?>?fuzzy&amp;translated"<?php
                        } else {
                            ?> href="javascript:void(0)" disabled="disabled" style="color: gray; cursor: text; text-decoration: none"<?php
                        }
                        ?>><?php echo t('only translated strings'); ?></a>
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>
    <fieldset>
    <fieldset>
        <legend><?php echo t('Upload Translations'); ?></legend>
        <?php
        if ($localeAccess >= TranslatorAccess::LOCALE_TRANSLATOR) {
            ?>
            <form class="form-horizontal" method="POST" enctype="multipart/form-data" onsubmit="if(this.already)return false;this.already=true">
                <div class="control-group">
                    <label class="control-label" for="ilUploadTranslations"><?php echo t('Upload .po file'); ?></label>
                    <div class="controls">
                        <input type="file" id="ilUploadTranslations" name="new-translations" required="required" />
                    </div>
                </div>
                <?php
                if ($isCoordinator) {
                    ?>
                    <div class="control-group">
                        <label class="control-label"><?php echo t('Options'); ?></label>
                        <div class="controls">
                            <label class="checkbox">
                                <input type="checkbox" name="as-approved" value="Sure!"> <?php echo t('Mark non-fuzzy translations as approved'); ?>
                            </label>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="control-group">
                    <div class="controls">
                        <button type="submit" class="btn btn-default"><?php echo t('Upload'); ?></button>
                    </div>
                </div>
            </form>
            <?php
        } elseif ($localeAccess === TranslatorAccess::LOCALE_ASPIRANT) {
            ?><div class="alert"><?php echo t('You have to wait that your application request will be approved in order to submit translations'); ?></div><?php
        } elseif ($isLoggedIn) {
            ?><p><?php echo t('Do you want to help us translating? <a href="%1$s">Click here</a> to join the %2$s translation group!', View::url('/integrated_localization/groups', 'group', $locales['selected']->getID()), $th->specialchars($locales['selected']->getName())); ?></p><?php
        } else {
            ?><p><?php echo t('Do you want to help us translating? <a href="%3$s">Login</a> and <a href="%1$s">click here</a> to join the %2$s translation group!', View::url('/integrated_localization/groups', 'group', $locales['selected']->getID()), $th->specialchars($locales['selected']->getName()), View::url('/login?rcID='.$c->getCollectionID())); ?></p><?php
        }
        ?>
    </fieldset>
    <?php
}
