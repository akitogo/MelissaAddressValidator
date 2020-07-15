define(
    [
        'uiComponent',
        'Akitogo_MelissaAddressValidator/js/onepagecheckout'
    ],
    function (Component, addressValidation) {
        'use strict';
        addressValidation.init();
        return Component.extend({});
    }
);
