var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/payment/additional-validators': {
                'Akitogo_MelissaAddressValidator/js/mixins/additionalValidators': true
            },
            'Magento_Checkout/js/action/place-order': {
                'Akitogo_MelissaAddressValidator/js/mixins/placeOrder': true
            }
        }
    },
    map: {
        '*': {
            akitogoMelissaAddressValidation: ''
        }
    }
};
