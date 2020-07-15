define([
    'mage/utils/wrapper'
], function (wrapper) {
    'use strict';

    return function (placeOrderFunction) {
        return wrapper.wrap(placeOrderFunction, function (originalPlaceOrderFunction, paymentData, messageContainer) {
            return originalPlaceOrderFunction(paymentData, messageContainer)
                .always(function () {
                    window.akitogoMelissaAddressValid = false;
                });
        });
    };
});
