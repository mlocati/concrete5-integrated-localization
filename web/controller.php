<?php
class IntegratedLocalizationPackage extends Package {

    protected $pkgHandle = 'integrated_localization';

    protected $appVersionRequired = '5.5.2';

    protected $pkgVersion = '0.0.1';

    public function getPackageName() {
    	return t('Integrated Localization');
    }

    public function getPackageDescription() {
    	return t('Manage core and package translations.');
    }

}
