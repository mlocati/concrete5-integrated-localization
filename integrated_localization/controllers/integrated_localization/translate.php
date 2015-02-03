<?php defined('C5_EXECUTE') or die('Access Denied.');

class IntegratedLocalizationTranslateController extends Controller
{
    public function view()
    {
        $db = Loader::db();
        /* @var $db ADODB_mysql */
        $devVersions = array();
        $releaseVersions = array();
        foreach ($db->GetCol("SELECT DISTINCT itpVersion FROM IntegratedTranslatablePlaces WHERE (itpPackage = '-')") as $v) {
            if (strpos($v, 'dev-') === 0) {
                $devVersions[$v] = substr($v, 4);
            } else {
                $releaseVersions[$v] = $v;
            }
        }
        uasort($devVersions, 'version_compare');
        $devVersions = array_reverse($devVersions, true);
        uasort($releaseVersions, 'version_compare');
        $releaseVersions = array_reverse($releaseVersions, true);
        $coreVersions = array();
        $nDevVersions = count($devVersions);
        $iDevVersion = 0;
        foreach (array_keys(array_merge($devVersions, $releaseVersions)) as $v) {
            if (strpos($v, 'dev-') === 0) {
                $iDevVersion++;
                if (($iDevVersion > 1) && ($iDevVersion === $nDevVersions)) {
                    $name = t('Development version (up to the %s series)', substr($v, 4));
                } else {
                    $name = t('Development version (from the %s series)', substr($v, 4));
                }
            } else {
                $name = t('Release %s', $v);
            }
            $coreVersions[$v] = $name;
        }
        $this->set('coreVersions', $coreVersions);
    }
}
