<?php

namespace ClassyLlama\AvaTax\Framework\Interaction\Address;

use AvaTax\SeverityLevel;
use AvaTax\TextCase;
use AvaTax\ValidateRequestFactory;
use ClassyLlama\AvaTax\Exception\AddressValidateException;
use ClassyLlama\AvaTax\Framework\Interaction\Address;
use ClassyLlama\AvaTax\Framework\Interaction\Cacheable\AddressService;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class Validation
{
    /**
     * @var Address
     */
    protected $interactionAddress = null;

    /**
     * @var AddressService
     */
    protected $addressService = null;

    /**
     * @var ValidateRequestFactory
     */
    protected $validateRequestFactory = null;

    /**
     * @param Address $interactionAddress
     * @param AddressService $addressService
     * @param ValidateRequestFactory $validateRequestFactory
     */
    public function __construct(
        Address $interactionAddress,
        AddressService $addressService,
        ValidateRequestFactory $validateRequestFactory
    ) {
        $this->interactionAddress = $interactionAddress;
        $this->addressService = $addressService;
        $this->validateRequestFactory = $validateRequestFactory;
    }

    /**
     * Validate address using AvaTax Address Validation API
     *
     * @author Jonathan Hodges <jonathan@classyllama.com>
     * @param array|\Magento\Customer\Api\Data\AddressInterface|\Magento\Sales\Api\Data\OrderAddressInterface|/AvaTax/ValidAddress|\Magento\Customer\Api\Data\AddressInterface|\Magento\Quote\Api\Data\AddressInterface|\Magento\Sales\Api\Data\OrderAddressInterface|array|null
     * @return array|\Magento\Customer\Api\Data\AddressInterface|\Magento\Sales\Api\Data\OrderAddressInterface|/AvaTax/ValidAddress|\Magento\Customer\Api\Data\AddressInterface|\Magento\Quote\Api\Data\AddressInterface|\Magento\Sales\Api\Data\OrderAddressInterface|array|null
     * @throws AddressValidateException
     * @throws LocalizedException
     */
    public function validateAddress($addressInput)
    {
        $returnCoordinates = 1;
            $validateRequest = $this->validateRequestFactory->create(
                [
                'address' => $this->interactionAddress->getAddress($addressInput),
                    'textCase' => (TextCase::$Mixed ? TextCase::$Mixed : TextCase::$Default),
                    'coordinates' => $returnCoordinates,
                ]
            );
            $validateResult = $this->addressService->validate($validateRequest);

        if ($validateResult->getResultCode() == SeverityLevel::$Success) {
            $validAddresses = $validateResult->getValidAddresses();

                if (isset($validAddresses[0])) {
                    $validAddress = $validAddresses[0];
                } else {
                    return null;
                }
            // Convert data back to the type it was passed in as
            // TODO: Return null if address could not be converted to original type
            switch (true) {
                case ($addressInput instanceof \Magento\Customer\Api\Data\AddressInterface):
                    $validAddress = $this->interactionAddress
                        ->convertAvaTaxValidAddressToCustomerAddress($validAddress, $addressInput);
                    break;
                case ($addressInput instanceof \Magento\Quote\Api\Data\AddressInterface):
                    $validAddress = $this->interactionAddress
                        ->convertAvaTaxValidAddressToQuoteAddress($validAddress, $addressInput);
                    break;
                case ($addressInput instanceof \Magento\Sales\Api\Data\OrderAddressInterface):
                    $validAddress = $this->interactionAddress
                        ->convertAvaTaxValidAddressToOrderAddress($validAddress, $addressInput);
                    break;
                case (is_array($addressInput)):
                    $validAddress = $this->interactionAddress->convertAvaTaxValidAddressToArray($validAddress);
                    break;
            }

            return $validAddress;
        } else {
            throw new AddressValidateException(__($validateResult->getMessages()[0]->getSummary()));
        }
    }
}