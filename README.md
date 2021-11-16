# Akitogo Melissa Address Validator for Magento 2
Magento 2 Extension for validating postal addresses using Melissa Data api service.

Please note that this module requires a valid api key from Melissa Data to work, please check https://www.melissa.com/developer/

If you find any bug, feel free to submit a pull request or file a bug report. If you are interested in commercial support, please get in touch with Akitogo. Please check https://www.akitogo.com/blog/address-validation-for-magento-2-with-melissa-data

The extension is as well officially available at the Magento Marketplace https://marketplace.magento.com/akitogo-melissa-address-validator.html

### Please note
If your theme uses a custom button/mechanism for triggering place order, the triggerPlaceOrder function has to be overridden accordingly.

Please do:
Create a Mixin (`app/design/frontend/<Vendor>/<Theme>/web/js/mixins/melissa-address-validator.js`)
For Mixins see: https://devdocs.magento.com/guides/v2.4/javascript-dev-guide/javascript/js_mixins.html

Add mixin to melissa validator js (`app/design/frontend/<Vendor>/<Theme>/requirejs-config.js`)
```
var config = {
    config: {
        mixins: {
            'Akitogo_MelissaAddressValidator/js/validator': {
                'js/mixins/melissa-address-validator': true
            }
        }
    },
```
In this example we have a custom methods and buttons for placing order - we are looking for button with different classes that have _active class:

![Example](README.png?raw=true)

### COMPOSER INSTALLATION
* run composer command:
>`$> composer require akitogo/melissa-address-validator`

### MANUAL INSTALLATION
* extract files from an archive

* deploy files into Magento2 folder `app/code/akitogo/MelissaAddressValidator`

### Versions
* 1.0.3 16th November 2021 fixing bug with default Magento theme and multiple payment methods
* 1.0.2 9th June 2021 no code changes, just checked compatibility with 2.4
* 1.0.1 21st July 2020 fixing api response handling
* 1.0.1 15th July 2020 Initial release

### About Akitogo
We are specialised in developing customized e-commerce solutions based on Magento.

### Are you interested in Melissa's solutions? 
Do you have any further questions? Then get in touch via sales@melissa.de or call us Melissa Data at +49 (0)221 97 58 92 40.
You are also welcome to visit www.melissa.de.

### About Melissa
Melissa is a leading provider of data quality, identity verification and address management solutions. Melissa supports companies in customer acquisition and in the validation and correction of contact data, in the optimization of marketing and sales processes ROIs and risk management. Since 1985 Melissa has been working for companies like Mercury insurance, Xerox, Disney, AAA and Nestlé a reliable partner in the improvement customer communication.

## Disclaimer
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
