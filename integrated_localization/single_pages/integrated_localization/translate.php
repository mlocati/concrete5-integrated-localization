<?php defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this View */

$th = Loader::helper('text');
/* @var $th TextHelper */

$fh = Loader::helper('form');
/* @var $fh FormHelper */

?><h2><?php echo t('Core Translations'); ?></h2>

<?php
foreach ($coreVersions as $$version => $name) {
	?><p><?php echo $name; ?></p><?php
}
