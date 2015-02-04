<?php defined('C5_EXECUTE') or die('Access Denied.');

/* @var $this View */

$th = Loader::helper('text');
/* @var $th TextHelper */

?>
<div>
    <?php echo t('Selected language:'); ?> 
    <select onchange="<?php echo $th->specialchars('window.location.href = '.json_encode($this->action($tab, 'LOCALE_PLACEHOLDER')).'.replace("LOCALE_PLACEHOLDER", this.value);'); ?>"><?php
        if(!isset($locales['selected'])) {
            ?><option value="" selected="selected"><?php echo t('*** Please select'); ?></option><?php
        }
        if ((count($locales['mine']) > 0) && (count($locales['notMine']) > 0)) {
            ?>
            <optgroup label="<?php echo t('My languages'); ?>">
                <?php
                foreach($locales['mine'] as $l) {
                    ?><option value="<?php echo $th->specialchars(rawurlencode($l->getID())); ?>"<?php echo ($locales['selected'] === $l) ? ' selected="selected"' : ''; ?>><?php echo $th->specialchars($l->getName()); ?></option><?php
                }
                ?>
            </optgroup>
            <optgroup label="<?php echo t('Other languages'); ?>">
                <?php
                foreach($locales['notMine'] as $l) {
                    ?><option value="<?php echo $th->specialchars(rawurlencode($l->getID())); ?>"<?php echo ($locales['selected'] === $l) ? ' selected="selected"' : ''; ?>><?php echo $th->specialchars($l->getName()); ?></option><?php
                }
                ?>
            </optgroup>
            <?php
        }
        else {
            foreach($locales['all'] as $l) {
                ?><option value="<?php echo $th->specialchars(rawurlencode($l->getID())); ?>"<?php echo ($locales['selected'] === $l) ? ' selected="selected"' : ''; ?>><?php echo $th->specialchars($l->getName()); ?></option><?php
            }
        }
    ?></select>
</div>

<?php
if(!isset($locales['selected'])) {
    ?><div class="alert"><?php echo t('Please select a language'); ?></div><?php
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
        
        ?>
        <table class="table table-striped table-condensed table-bordered table-hover">
            <thead><tr>
                <th><?php echo t('Version'); ?></th>
                <th><?php echo t('Details'); ?></th>
                <th><?php echo t('Progress'); ?></th>
            </tr></thead>
            <tbody><?php
                foreach ($versions as $version => $name) {
                    $stats = $this->controller->getVersionStats('-', $version, $locales['selected']);
                    ?>
                    <tr>
                        <th><a href="<?php echo $this->action($tab, $locales['selected']->getID(), preg_replace('/^dev-/', '', $version)); ?>"><?php echo $th->specialchars($name); ?></a></th>
                        <td><?php echo t('%s out of %s', $stats['translated'], $stats['total']); ?></td>
                        <td style="width: 120px"><div class="integrated_localization-progress integrated_localization-progress-<?php echo floor($stats['progress'] / 10); ?>" title="<?php echo t('%s %%', $stats['progress']); ?>">
                            <span style="width: <?php echo $stats['progress']; ?>%" ><?php echo t('%s %%', $stats['progress']); ?></span>
                        </div></td>
                    </tr>
                    <?php
                }
                ?>       
            </tbody>
        </table>
        <?php
        break;
    case 'packages':
        break;
}
