define([
        'jquery',
        'Akitogo_MelissaAddressValidator/js/validator'
    ],
    function ($, validator) {
        'use strict';
        return {
            init: function () {
                window.akitogoMelissaAddressValid = false;
                window.akitogoMelissaAddressValidator = validator;
            }
        };
    }
);
