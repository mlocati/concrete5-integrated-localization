<?php defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this BlockView */
/* @var $b Block */
/* @var $locales IntegratedLocale[] */

$th = Loader::helper('text');
/* @var $th TextHelper */

?>
<form method="POST" enctype="multipart/form-data" action="<?php echo $this->action('parse_package'); ?>" target="_blank" id="form-package_languages_builder-<?php echo $b->getBlockID(); ?>" onsubmit="return false">
    <p>
        <?php echo t('Upload a zip archive containing your package'); ?><br />
        <input type="file" name="package_file" required="required" />
    </p>
    <p>
        <?php echo t('Operations'); ?><br />
        <label><input type="checkbox" name="create_pot" checked="checked" value="1" /> <?php echo t('Include translations template file (.pot)'); ?></label><br />
        <label><input type="checkbox" name="create_po" checked="checked" value="1" /> <?php echo t('Create .po files'); ?></label><br />
        <label><input type="checkbox" name="create_mo" checked="checked" value="1" /> <?php echo t('Create .mo files'); ?></label>
    </p>
    <div class="need_locales">
        <?php echo t('Languages to include'); ?><br />
        <label><input type="checkbox" name="all_locales" checked="checked" value="1" /> <?php echo t('Include all available languages'); ?></label><br />
        <div class="selected_locales" style="display: none">
            <?php echo t('Which languages do you want to include?'); ?>
            (
            <a href="javascript:void(0)" onclick="$('input.selected_locale', $(this).closest('form')).prop('checked', true)"><?php echo t('all'); ?></a>
            |
            <a href="javascript:void(0)" onclick="$('input.selected_locale', $(this).closest('form')).prop('checked', false)"><?php echo t('none'); ?></a>
            )
            <div style="padding-left: 20px">
                <?php
                foreach ($locales as $locale) {
                    ?><label><input type="checkbox" class="selected_locale" name="selected_locales" value="<?php echo $th->specialchars($locale->getID()); ?>" /> <?php echo $th->specialchars($locale->getName()); ?></label><br /><?php
                }
                ?>
            </div>
        </div>
    </div>
    <br />
    <input type="submit" disabled="disabled" value="<?php echo $th->specialchars(t('Process package archive')); ?>" />
</form>
<script>
$(document).ready(function() {
    new PackageLanguagesBuilder(<?php echo $b->getBlockID(); ?>);
});
</script>
