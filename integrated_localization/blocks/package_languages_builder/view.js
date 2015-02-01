/* jshint unused:vars, undef:true, browser:true, jquery:true */
(function() {
'use strict';

function PackageLanguagesBuilder(bID) {
	var me = this;
	this.$form = $('#form-package_languages_builder-' + bID);
	this.$submit = $('input[type="submit"]', this.$form);
	this.$createPot = $('input[name="create_pot"]', this.$form);
	this.$createPo = $('input[name="create_po"]', this.$form);
	this.$createMo = $('input[name="create_mo"]', this.$form);
	this.$allLocales = $('input[name="all_locales"]', this.$form);
	this.$selectedLocales = $('input.selected_locale', this.$form);
	this.update();
	this.$form.on('change', function() {
		me.update();
	});
	this.$form.removeAttr('onsubmit');
}
PackageLanguagesBuilder.prototype = {
	update: function() {
		if(this.$allLocales.is(':checked')) {
			this.$selectedLocales.attr('disabled', 'disabled');
		}
		else {
			this.$selectedLocales.removeAttr('disabled');
		}
		var ok = true;
		if(ok) {
			if(!(this.$createPot.is(':checked') || this.$createPo.is(':checked') || this.$createMo.is(':checked'))) {
				ok = false;
			}
		}
		if(ok) {
			if(this.$createPo.is(':checked') || this.$createMo.is(':checked')) {
				if(!this.$allLocales.is(':checked')) {
					ok = false;
					this.$selectedLocales.each(function() {
						if($(this).is(':checked')) {
							ok = true;
							return false;
						}
					});
				}
			}
		}
		$('div.need_locales', this.$form)[(this.$createPo.is(':checked') || this.$createMo.is(':checked')) ? 'show' : 'hide']('fast');
		$('div.selected_locales', this.$form)[this.$allLocales.is(':checked') ? 'hide' : 'show']('fast');
		if(ok) {
			this.$submit.removeAttr('disabled');
		}
		else {
			this.$submit.attr('disabled', 'disabled');
		}
	}
};


if(!window.PackageLanguagesBuilder) {
	window.PackageLanguagesBuilder = PackageLanguagesBuilder;
}

})();
