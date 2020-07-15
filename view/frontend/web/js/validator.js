define([
        'jquery',
        'mage/translate',
        'Magento_Checkout/js/checkout-data',
        'text!Akitogo_MelissaAddressValidator/templates/modal/melissa-validate-address.html',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/action/select-billing-address',
        'Magento_Checkout/js/model/shipping-save-processor/payload-extender',
        'mage/storage',
        'Magento_Checkout/js/model/resource-url-manager',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/payment/method-converter',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/url',
        'Magento_Ui/js/modal/modal',
    ],
    function (
        $,
        $t,
        checkoutData,
        melissaValidateAddressTpl,
        quote,
        customerData,
        setShippingInformation,
        selectBillingAddress,
        payloadExtender,
        storage,
        resourceUrlManager,
        paymentService,
        errorProcessor,
        methodConverter,
        fullScreenLoader,
        urlBuilder) {
        'use strict';

        const validator = {
            modalContent: document.createElement('div'),
            countryData: customerData.get('directory-data'),
            triggerPlaceOrder: function () {
                window.akitogoMelissaAddressValid = true;
                $('.actions-toolbar button.action.checkout').first().trigger('click');
            },
            getCountryName: function (countryId) {
                return this.countryData()[countryId] !== undefined ? this.countryData()[countryId].name : '';
            },
            getAddressFieldValue: function (type) {
                return $(`.address-radio[name="useOriginal[${type}]"]:checked`).val();
            },
            saveShippingInformation: function () {
                let payload = {
                    addressInformation: {
                        'shipping_address': quote.shippingAddress(),
                        'billing_address': quote.billingAddress(),
                        'shipping_method_code': quote.shippingMethod()['method_code'],
                        'shipping_carrier_code': quote.shippingMethod()['carrier_code']
                    }
                };
                payloadExtender(payload);
                fullScreenLoader.startLoader();

                return storage.post(
                    resourceUrlManager.getUrlForSetShippingInformation(quote),
                    JSON.stringify(payload)
                ).done(
                    function () {
                        fullScreenLoader.stopLoader();
                    }
                ).fail(
                    function (response) {
                        errorProcessor.process(response);
                        fullScreenLoader.stopLoader();
                    }
                );
            },
            getBillingSameAsShipping: function () {
                return $('input[name="billing-address-same-as-shipping"]').is(':checked');
            },
            replaceAddressesAndPlaceOrder: function () {
                const useShippingOriginal = this.getAddressFieldValue('shippingAddress');
                if (useShippingOriginal === "0") {
                    const shippingAddress = quote.shippingAddress();
                    const suggestedAddress = this.modalConfig.addresses[0].suggestedAddress;
                    delete shippingAddress.customerAddressId;
                    shippingAddress.postcode = suggestedAddress.postcode;
                    shippingAddress.region = suggestedAddress.region;
                    shippingAddress.region_id = suggestedAddress.region_id;
                    shippingAddress.region_code = suggestedAddress.region_code;
                    shippingAddress.city = suggestedAddress.city;
                    shippingAddress.street = suggestedAddress.street;
                    if(this.getBillingSameAsShipping()) {
                        selectBillingAddress(quote.shippingAddress());
                    }
                    this.saveShippingInformation()
                        .done(function () {
                            $(this.modalContent).modal('closeModal');
                            validator.triggerPlaceOrder();
                        });
                } else {
                    if(this.getBillingSameAsShipping()) {
                        selectBillingAddress(quote.shippingAddress());
                    }
                    this.triggerPlaceOrder();
                }
            },
            getAddresses: function (shippingAddress, suggestedAddresses) {
                const shippingAddressFromData = quote.shippingAddress();
                shippingAddress.title = $t('Shipping Address');
                shippingAddress.type = 'shippingAddress';
                shippingAddress.firstname = shippingAddressFromData.firstname;
                shippingAddress.lastname = shippingAddressFromData.lastname;
                shippingAddress.middlename = shippingAddressFromData.middlename;
                shippingAddress.country = this.getCountryName(shippingAddress.country_id);
                shippingAddress.suggestedAddress = suggestedAddresses[0];
                if (!shippingAddress.suggestedAddress.region) {
                    shippingAddress.suggestedAddress.region = shippingAddress.region;
                    shippingAddress.suggestedAddress.region_id = shippingAddress.region_id;
                    shippingAddress.suggestedAddress.region_code = shippingAddress.region_code;
                }
                return [shippingAddress]
            },
            modalConfig: {
                type: 'popup',
                popupTpl: melissaValidateAddressTpl,
                modalClass: 'melissa-validation',
                title: $t('Address Validation'),
                responsive: true,
                buttons: [
                    {
                        text: $t('Close'),
                        click: function () {
                            this.closeModal();
                        }
                    },
                    {
                        class: 'action primary',
                        text: $t('Continue'),
                        click: function () {
                            this.closeModal();
                            validator.replaceAddressesAndPlaceOrder();
                        }
                    },
                ],
                useOriginalAddress: $t('Use Original Address'),
                useSuggestedAddress: $t('Use Suggested Address'),
                addresses: [],
            },
            getFieldsForValidation: function (address) {
                return {
                    postcode: address.postcode,
                    country_id: address.countryId,
                    region: address.region,
                    city: address.city,
                    street: address.street,
                    company: address.company
                }
            },
            openModal: function () {
                $('.melissa-validation').remove();
                $(this.modalContent).modal(this.modalConfig).modal('openModal');
            },
            validate: function () {
                if (quote.isVirtual()) {
                    validator.triggerPlaceOrder();
                } else {
                    const shippingAddress = this.getFieldsForValidation(quote.shippingAddress());
                    $.ajax({
                        showLoader: true,
                        url: urlBuilder.build('melissa/address/validate'),
                        type: 'POST',
                        data: {
                            form_key: $.mage.cookies.get('form_key'),
                            addresses: [shippingAddress]
                        },
                        dataType: 'json'
                    }).done(function (data) {
                        if (data.placeOrder) {
                            validator.replaceAddressesAndPlaceOrder();
                        } else {
                            validator.modalConfig.addresses = validator.getAddresses(shippingAddress, data.errorSuggestedAddresses);
                            validator.openModal();
                        }
                    }).fail(function () {
                        validator.triggerPlaceOrder();
                    });
                }
            }
        };

        return validator;
    }
);
