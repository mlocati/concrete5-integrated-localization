<?php defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this View */

if(empty($translations)) {
    return;
}

$th = Loader::helper('text');
/* @var $th TextHelper */
$jh = Loader::helper('json');
/* @var $jh JsonHelper */

/* @var $package string */
/* @var $version string */
/* @var $locale IntegratedLocale */

?>
<script type="text/javascript">
$(document).ready(function() {
  ccmTranslator.setI18NDictionart({
    AskDiscardDirtyTranslation: <?php echo $jh->encode(t("The current item has changed.\nIf you proceed you will lose your changes.\n\nDo you want to proceed anyway?")); ?>,
    Comments: <?php echo $jh->encode(t('Comments')); ?>,
    Context: <?php echo $jh->encode(t('Context')); ?>,
    ExamplePH: <?php echo $jh->encode(t('Example: %s')); ?>,
    Filter: <?php echo $jh->encode(t('Filter')); ?>,
    Original_String: <?php echo $jh->encode(t('Original String')); ?>,
    Please_fill_in_all_plurals: <?php echo $jh->encode(t('Please fill-in all plural forms')); ?>,
    Plural_Original_String: <?php echo $jh->encode(t('Plural Original String')); ?>,
    References: <?php echo $jh->encode(t('References')); ?>,
    Save_and_Continue: <?php echo $jh->encode(t('Save & Continue')); ?>,
    Search_for_: <?php echo $jh->encode(t('Search for...')); ?>,
    Search_in_contexts: <?php echo $jh->encode(t('Search in contexts')); ?>,
    Search_in_originals: <?php echo $jh->encode(t('Search in originals')); ?>,
    Search_in_translations: <?php echo $jh->encode(t('Search in translations')); ?>,
    Show_approved: <?php echo $jh->encode(t('Show approved')); ?>,
    Show_translated: <?php echo $jh->encode(t('Show translated')); ?>,
    Show_unapproved: <?php echo $jh->encode(t('Show unapproved')); ?>,
    Show_untranslated: <?php echo $jh->encode(t('Show untranslated')); ?>,
    Singular_Original_String: <?php echo $jh->encode(t('Singular Original String')); ?>,
    Toggle_Dropdown: <?php echo $jh->encode(t('Toggle Dropdown')); ?>,
    TAB: <?php echo $jh->encode(t('[TAB] Forward')); ?>,
    TAB_SHIFT: <?php echo $jh->encode(t('[SHIFT]+[TAB] Backward')); ?>,
    Translate: <?php echo $jh->encode(t('Translate')); ?>,
    Translation: <?php echo $jh->encode(t('Translation')); ?>,
    PluralNames: {
      zero: <?php echo $jh->encode(t('Zero')); ?>,
      one: <?php echo $jh->encode(t('One')); ?>,
      two: <?php echo $jh->encode(t('Two')); ?>,
      few: <?php echo $jh->encode(t('Few')); ?>,
      many: <?php echo $jh->encode(t('Many')); ?>,
      other: <?php echo $jh->encode(t('Other')); ?>
    }
  });
  ccmTranslator.configureFrontend({
    colFilter: 'span12',
    colOriginal: 'span5',
    colTranslations: 'span7'
  });
  ccmTranslator.initialize({
    container: '#integratedlocalization-translator-interface',
    height: $(window).height() - 300,
    saveAction: <?php echo $jh->encode($this->action('save_translation', $package, $version, $locale->getID())); ?>,
    plurals: <?php echo $jh->encode($pluralCases); ?>,
    translations: <?php echo $jh->encode($translations, $jsonOptions); ?>,
    approvalSupport: true,
    canModifyApproved: <?php echo $isCoordinator ? 'true' : 'false'; ?>,
    referencePatterns: <?php echo $jh->encode($referencePatterns); ?>
  })
});
</script>
<div id="integratedlocalization-translator-interface" class="integratedlocalization-translator"></div>
