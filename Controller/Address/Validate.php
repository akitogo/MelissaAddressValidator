<?php

namespace Akitogo\MelissaAddressValidator\Controller\Address;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Directory\Model\Region;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;
use Akitogo\MelissaAddressValidator\Helper\Data;
use Magento\Framework\Data\Form\FormKey\Validator;

class Validate extends Action implements HttpPostActionInterface
{
    const API_REQUEST_URI = 'https://address.melissadata.net/';
    const API_REQUEST_ENDPOINT = 'v3/WEB/GlobalAddress/doGlobalAddress';

    protected $jsonFactory;
    protected $clientFactory;
    protected $logger;
    protected $request;
    protected $region;
    protected $helperData;
    protected $formKeyValidator;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ClientFactory $clientFactory,
        LoggerInterface $logger,
        Region $region,
        Data $helperData,
        Validator $formKeyValidator
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->request = $this->getRequest();
        $this->region = $region;
        $this->helperData = $helperData;
        $this->formKeyValidator = $formKeyValidator;
    }

    public function execute()
    {
        $response = $this->jsonFactory->create();
        if (!$this->isEnabled()) {
            $response->setStatusHeader(404);
            return $response;
        }
        if (!$this->formKeyValidator->validate($this->request)) {
            $response->setStatusHeader(401);
            return $response;
        }
        if (!$this->getCustomerId()) {
            $this->logger->critical('MelissaApi Error: ', [
                'message' => 'Missing Customer Id',
            ]);
            return $response->setData([
                'placeOrder' => true,
                'errorSuggestedAddresses' => []
            ]);
        }
        $params = $this->prepareParams($this->request);
        $requestResponse = $this->doRequest($params);
        $processedResponse = $this->processResponse($requestResponse);
        return $response->setData($processedResponse);
    }

    protected function isEnabled()
    {
        return $this->helperData->getAddressConfig('enable');
    }

    protected function getCustomerId()
    {
        return $this->helperData->getAddressConfig('customer_id');
    }

    protected function showFrontendErrors()
    {
        return $this->helperData->getAddressConfig('show_errors_frontend');
    }

    protected function prepareParams(RequestInterface $request)
    {
        $params = [
            'TransmissionReference' => 1,
            'CustomerID' => $this->getCustomerId(),
            'Options' => 'DeliveryLines:ON',
            'Records' => []
        ];
        $addresses = $request->getParam('addresses');
        foreach ($addresses as $index => $address) {
            $tmp = [
                'RecordID' => $index
            ];
            foreach ($address as $key => $value) {
                $this->addField($tmp, $key, $value);
            }
            $params['Records'][] = $tmp;
        }
        return $params;
    }

    protected function addField(&$address, $field, $value)
    {
        switch ($field) {
            case 'postcode':
                $address['PostalCode'] = $value;
                break;
            case 'country_id':
                $address['Country'] = $value;
                break;
            case 'city':
                $address['Locality'] = $value;
                break;
            case 'street':
                $filtered = array_filter($value, function ($street) {
                    return strlen(trim($street)) > 0;
                });
                foreach ($filtered as $index => $streetAddress) {
                    if ($index > 7) {
                        break;
                    }
                    if ($streetAddress) {
                        $num = $index + 1;
                        $address["AddressLine${num}"] = $streetAddress;
                    }
                }
                break;
            case 'region':
                $address['AdministrativeArea'] = $value;
                break;
            case 'company':
                $address['Organization'] = $value;
                break;
        }
    }

    protected function doRequest($params = [])
    {
        $client = $this->clientFactory->create(['config' => [
            'base_uri' => self::API_REQUEST_URI,
            'headers' => [
                'Accept' => 'application/json'
            ]
        ]]);

        try {
            $request = $client->request(
                Request::HTTP_METHOD_POST,
                self::API_REQUEST_ENDPOINT,
                [
                    'json' => $params
                ]
            );
            $response = json_decode($request->getBody()->getContents(), true);
            $response['MelissaSuccess'] = 1;
        } catch (GuzzleException $exception) {
            $this->logger->critical('MelissaApi Error: ', [
                'exception' => $exception->getMessage(),
                'code' => $exception->getCode()
            ]);
            $response = ['MelissaSuccess' => 0];
        }
        return $response;
    }

    protected function processResponse($response = [])
    {
        $processedResponse = [
            'placeOrder' => true,
            'errorSuggestedAddresses' => []
        ];
        if (!$response['MelissaSuccess'] || $response['TransmissionResults']) {
            $this->handleMelissaError($response['TransmissionResults']);
            return $processedResponse;
        }
        foreach ($response['Records'] as $validatedAddress) {
            $statuses = $validatedAddress['Results'];
            if ((strpos($statuses, 'AV25') === false && strpos($statuses, 'AV24') === false && strpos($statuses, 'AV23') === false) || $this->isAddressSuggested($statuses)) {
                $processedResponse['placeOrder'] = false;
                $processedResponse['errorSuggestedAddresses'][] = $this->mapMelissaAddress($validatedAddress);
            }
        }

        return $processedResponse;
    }

    protected function handleMelissaError($status = null)
    {
        if (!$status) {
            return;
        }
        switch ($status) {
            case 'GE02':
                $logMessage = 'Empty Request Record Structure';
                break;
            case 'GE03':
                $logMessage = 'Records Per Request Exceeded';
                break;
            case 'GE04':
                $logMessage = 'Empty License Key';
                break;
            case 'GE05':
                $logMessage = 'Invalid License Key';
                break;
            case 'GE06':
                $logMessage = 'Disabled License Key';
                break;
            case 'GE08':
                $logMessage = 'Product/Level Not Enabled';
                break;
            case 'GE09':
                $logMessage = 'Customer Does Not Exist';
                break;
            case 'GE10':
                $logMessage = 'Customer License Disabled';
                break;
            case 'GE11':
                $logMessage = 'Customer Disabled';
                break;
            case 'GE12':
                $logMessage = 'IP Blacklisted';
                break;
            case 'GE13':
                $logMessage = 'IP Not Whitelisted';
                break;
            case 'GE14':
                $logMessage = 'Out of Credits';
                break;
            default:
                $logMessage = 'Unknown code';
                break;
        }
        $this->logger->critical('MelissaApi Error: ', [
            'code' => $status,
            'message' => $logMessage
        ]);
    }

    protected function mapMelissaAddress($melissaAddress)
    {
        $givenStatuses = $melissaAddress['Results'];
        $region = $this->region->loadByName($melissaAddress['SubNationalArea'], $melissaAddress['CountryISO3166_1_Alpha2']);
        if ($region->getId()) {
            $regionName = $melissaAddress['SubNationalArea'];
            $regionId = $region->getId();
            $regionCode = $region->getCode();
        } else {
            $regionName = null;
            $regionId = null;
            $regionCode = null;
        }
        $mapped = [
            'is_suggested' => $this->isAddressSuggested($givenStatuses),
            'formatted_address' => $melissaAddress['FormattedAddress'],
            'address_index' => $melissaAddress['RecordID'],
            'company' => $melissaAddress['Organization'],
            'region_id' => $regionId,
            'region_code' => $regionCode,
            'region' => $regionName,
            'country_id' => $melissaAddress['CountryISO3166_1_Alpha2'],
            'country' => $melissaAddress['CountryName'],
            'postcode' => $melissaAddress['PostalCode'],
            'city' => $melissaAddress['Locality'],
            'street' => [],
            'errors' => []
        ];
        if ($this->showFrontendErrors()) {
            $mapped['errors'] = $this->getAddressErrors($givenStatuses);
        }
        for ($i = 1; $i <= 8; $i++) {
            $addressLine = $melissaAddress["AddressLine${i}"];
            if (strlen(trim($addressLine))) {
                $mapped['street'][] = $addressLine;
            }
        }
        return $mapped;
    }

    protected function isAddressSuggested($givenStatuses)
    {
        $suggestedStatuses = ['AC01', 'AC03', 'AC10', 'AC11', 'AC12', 'AC17', 'AC20', 'AC22'];
        $suggested = false;
        foreach ($suggestedStatuses as $status) {
            if (strpos($givenStatuses, $status) !== false) {
                $suggested = true;
                break;
            }
        }
        return $suggested;
    }

    protected function getAddressErrors($givenStatuses)
    {
        $errors = [];
        if (strpos($givenStatuses, 'AV11') !== false) {
            $errors[] = __('The address has been partially verified to the Administrative Area (State) Level.');
        }
        if (strpos($givenStatuses, 'AV12') !== false) {
            $errors[] = __('The address has been partially verified to the Locality (City) Level.');
        }
        if (strpos($givenStatuses, 'AV13') !== false) {
            $errors[] = __('The address has been partially verified to the Thoroughfare (Street) Level.');
        }
        if (strpos($givenStatuses, 'AE01') !== false) {
            $errors[] = __('The address could not be verified at least up to the postal code level.');
        }
        if (strpos($givenStatuses, 'AE02') !== false) {
            $errors[] = __('Could not match the input street to a unique street name. Either no matches or too many matches found.');
        }
        if (strpos($givenStatuses, 'AE03') !== false) {
            $errors[] = __('The combination of directionals (N, E, SW, etc) and the suffix (AVE, ST, BLVD) is not correct and produced multiple possible matches.');
        }
        if (strpos($givenStatuses, 'AE05') !== false) {
            $errors[] = __('The address was matched to multiple records. There is not enough information available in the address to break the tie between multiple records.');
        }
        if (strpos($givenStatuses, 'AE08') !== false) {
            $errors[] = __('The thoroughfare (street address) was found but the sub premise (suite) was not valid.');
        }
        if (strpos($givenStatuses, 'AE09') !== false) {
            $errors[] = __('The thoroughfare (street address) was found but the sub premise (suite) was missing.');
        }
        if (strpos($givenStatuses, 'AE10') !== false) {
            $errors[] = __('The premise (house or building) number for the address is not valid.');
        }
        if (strpos($givenStatuses, 'AE11') !== false) {
            $errors[] = __('The premise (house or building) number for the address is missing.');
        }
        if (strpos($givenStatuses, 'AE12') !== false) {
            $errors[] = __('The PO (Post Office Box), RR (Rural Route), or HC (Highway Contract) Box number is invalid.');
        }
        if (strpos($givenStatuses, 'AE13') !== false) {
            $errors[] = __('The PO (Post Office Box), RR (Rural Route), or HC (Highway Contract) Box number is missing.');
        }
        if (strpos($givenStatuses, 'AE14') !== false) {
            $errors[] = __('The address is a Commercial Mail Receiving Agency (CMRA) and the Private Mail Box (PMB or #) number is missing.');
        }
        return $errors;
    }
}
