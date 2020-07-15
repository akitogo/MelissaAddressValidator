define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    return function (additionalValidators) {
        additionalValidators.validate = wrapper.wrapSuper(additionalValidators.validate, function (hideError) {
            const valid = this._super(hideError);
            if (valid && typeof window.akitogoMelissaAddressValid !== 'undefined') {
                const validator = window.akitogoMelissaAddressValidator;
                if (window.akitogoMelissaAddressValid) {
                    return true;
                } else if (typeof validator !== 'undefined')  {
                    validator.validate();
                    return false;
                }
            }
            return valid;
        });

        return additionalValidators;
    };
});
