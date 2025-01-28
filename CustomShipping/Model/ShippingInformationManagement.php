<?php

namespace Fef\CustomShipping\Model;

use Magento\Checkout\Model\PaymentDetailsFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Quote\Model\QuoteAddressValidator;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use Magento\Framework\App\ObjectManager;

/**
 * Class ShippingInformationManagement
 * @package Smartosc\Checkout\Model
 */
class ShippingInformationManagement extends \Smartosc\Checkout\Model\ShippingInformationManagement
{
    const DELIVERY_TYPE = 'shipping_type'; /* Customers choose to receive goods at the store or choose to ship to an address */
    const DELIVERY_TYPE_STORE_PICKUP = 'in_store_pickup';
    const DELIVERY_TYPE_DELIVERY = 'delivery';

    /*Store Pickup information*/
    const PICKUP_NOTE = 'pickup_comments';
    const PICKUP_DATE = 'pickup_date';
    const PICKUP_TIME = 'pickup_time';
    const GIFT_MESSAGE_FROM = 'gift_message_from';
    const GIFT_MESSAGE_TO = 'gift_message_to';
    const GIFT_MESSAGE = 'gift_message';
    const ACCEPT_AUTHORIZE = 'accept_authorize';

    /*Delivery information*/
    const BILLING_BUILDING = 'billing_building';
    const BILLING_FLOOR = 'billing_floor';
    const SHIPPING_BUILDING = 'shipping_building';
    const SHIPPING_FLOOR = 'shipping_floor';
    const DELIVERY_NOTE = 'delivery_note';
    const DELIVERY_DATE = 'delivery_date';
    //RAW
    const DELIVERY_TIMESLOT = 'delivery_timeslot';
    const DELIVERY_STAIRS = 'delivery_stairs';
    const PICKUP_STORE_NAME = 'pickup_store_name';
    const PICKUP_STORE_ADDRESS = 'pickup_store_address';
    const PICKUP_STORE_STATE = 'pickup_store_state';
    const PICKUP_STORE_ZIP = 'pickup_store_zip';

    /**
     * @var \Magento\Quote\Api\PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * @var PaymentDetailsFactory
     */
    protected $paymentDetailsFactory;

    /**
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var QuoteAddressValidator
     */
    protected $addressValidator;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     * @deprecated 100.2.0
     */
    protected $addressRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * @deprecated 100.2.0
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     * @deprecated 100.2.0
     */
    protected $totalsCollector;

    /**
     * @var \Magento\Quote\Api\Data\CartExtensionFactory
     */
    private $cartExtensionFactory;

    /**
     * @var \Magento\Quote\Model\ShippingAssignmentFactory
     */
    protected $shippingAssignmentFactory;

    /**
     * @var \Magento\Quote\Model\ShippingFactory
     */
    private $shippingFactory;

    /**
     * Constructor
     *
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteAddressValidator $addressValidator
     * @param Logger $logger
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     * @param CartExtensionFactory|null $cartExtensionFactory
     * @param ShippingAssignmentFactory|null $shippingAssignmentFactory
     * @param ShippingFactory|null $shippingFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        QuoteAddressValidator $addressValidator,
        Logger $logger,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        CartExtensionFactory $cartExtensionFactory = null,
        ShippingAssignmentFactory $shippingAssignmentFactory = null,
        ShippingFactory $shippingFactory = null
    ) {
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->quoteRepository = $quoteRepository;
        $this->addressValidator = $addressValidator;
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
        $this->scopeConfig = $scopeConfig;
        $this->totalsCollector = $totalsCollector;
        $this->cartExtensionFactory = $cartExtensionFactory ?: ObjectManager::getInstance()
            ->get(CartExtensionFactory::class);
        $this->shippingAssignmentFactory = $shippingAssignmentFactory ?: ObjectManager::getInstance()
            ->get(ShippingAssignmentFactory::class);
        $this->shippingFactory = $shippingFactory ?: ObjectManager::getInstance()
            ->get(ShippingFactory::class);
    }

    /**
     * {@inheritdoc}
     */
    public function saveAddressInformation(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $this->validateQuote($quote);
        $extensionAttributes = $addressInformation->getExtensionAttributes();

        if ($extensionAttributes) {
            $shippingType = $extensionAttributes->getShippingType();
            $quote->setData(self::DELIVERY_TYPE, $shippingType);

            if ($shippingType == self::DELIVERY_TYPE_DELIVERY) {

                if ($extensionAttributes->getGiftMessageFrom()) {
                    $quote->setData(self::GIFT_MESSAGE_FROM, $extensionAttributes->getGiftMessageFrom());
                }
                if ($extensionAttributes->getGiftMessageTo()) {
                    $quote->setData(self::GIFT_MESSAGE_TO, $extensionAttributes->getGiftMessageTo());
                }
                if ($extensionAttributes->getGiftMessage()) {
                    $quote->setData(self::GIFT_MESSAGE, $extensionAttributes->getGiftMessage());
                }
                if ($extensionAttributes->getAcceptAuthorize()) {
                    $quote->setData(self::ACCEPT_AUTHORIZE, $extensionAttributes->getAcceptAuthorize());
                }
                if ($extensionAttributes->getDeliveryNote()) {
                    $quote->setData(self::DELIVERY_NOTE, $extensionAttributes->getDeliveryNote());
                }
                if ($extensionAttributes->getDeliveryTimeslot()) {
                    $quote->setData(self::DELIVERY_TIMESLOT, $extensionAttributes->getDeliveryTimeslot());
                }
                if ($extensionAttributes->getDeliveryStairs()) {
                    $quote->setData(self::DELIVERY_STAIRS, $extensionAttributes->getDeliveryStairs());
                }
                if ($extensionAttributes->getDeliveryDate()) {
                    $quote->setData(self::DELIVERY_DATE, str_replace("--", "-", $extensionAttributes->getDeliveryDate()));
                }
                if ($extensionAttributes->getBillingBuilding()) {
                    $quote->setData(self::BILLING_BUILDING, $extensionAttributes->getBillingBuilding());
                }
                if ($extensionAttributes->getBillingFloor()) {
                    $quote->setData(self::BILLING_FLOOR, $extensionAttributes->getBillingFloor());
                }
                if ($extensionAttributes->getShippingBuilding()) {
                    $quote->setData(self::SHIPPING_BUILDING, $extensionAttributes->getShippingBuilding());
                }
                if ($extensionAttributes->getShippingFloor()) {
                    $quote->setData(self::SHIPPING_FLOOR, $extensionAttributes->getShippingFloor());
                }

            } else {
                $shippingExtensionAttributes = $addressInformation->getShippingAddress()->getExtensionAttributes();
                if ($shippingExtensionAttributes->getGiftMessageFrom()) {
                    $quote->setData(self::GIFT_MESSAGE_FROM, $shippingExtensionAttributes->getGiftMessageFrom());
                }
                if ($shippingExtensionAttributes->getGiftMessageTo()) {
                    $quote->setData(self::GIFT_MESSAGE_TO, $shippingExtensionAttributes->getGiftMessageTo());
                }
                if ($shippingExtensionAttributes->getGiftMessage()) {
                    $quote->setData(self::GIFT_MESSAGE, $shippingExtensionAttributes->getGiftMessage());
                }
                if ($shippingExtensionAttributes->getBillingBuilding()) {
                    $quote->setData(self::BILLING_BUILDING, $shippingExtensionAttributes->getBillingBuilding());
                }
                if ($shippingExtensionAttributes->getBillingFloor()) {
                    $quote->setData(self::BILLING_FLOOR, $shippingExtensionAttributes->getBillingFloor());
                }
                if ($shippingExtensionAttributes->getPickupComments()) {
                    $quote->setData(self::PICKUP_NOTE, $shippingExtensionAttributes->getPickupComments());
                }
                if ($shippingExtensionAttributes->getPickupDate()) {
                    $quote->setData(self::PICKUP_DATE, $shippingExtensionAttributes->getPickupDate());
                }
                if ($shippingExtensionAttributes->getPickupTime()) {
                    $quote->setData(self::PICKUP_TIME, $shippingExtensionAttributes->getPickupTime());
                }
                if ($shippingExtensionAttributes->getPickupStoreName()) {
                    $quote->setData(self::PICKUP_STORE_NAME, $shippingExtensionAttributes->getPickupStoreName());
                }
                if ($shippingExtensionAttributes->getPickupStoreAddress()) {
                    $quote->setData(self::PICKUP_STORE_ADDRESS, $shippingExtensionAttributes->getPickupStoreAddress());
                }
                if ($shippingExtensionAttributes->getPickupStoreState()) {
                    $quote->setData(self::PICKUP_STORE_STATE, $shippingExtensionAttributes->getPickupStoreState());
                }
                if ($shippingExtensionAttributes->getPickupStoreZip()) {
                    $quote->setData(self::PICKUP_STORE_ZIP, $shippingExtensionAttributes->getPickupStoreZip());
                }
            }
        }

        $address = $addressInformation->getShippingAddress();
        if (!$address) {
            throw new StateException(__('The shipping address X is missing. Set the address and try again.'));
        }
        if (!$address->getCountryId()) {
            throw new StateException(__('The shipping address country ID is missing. Set the address and try again.'));
        }

        if (!$address->getCustomerAddressId()) {
            $address->setCustomerAddressId(null);
        }

        try {
            $billingAddress = $addressInformation->getBillingAddress();
            if ($billingAddress) {
                if (!$billingAddress->getCustomerAddressId()) {
                    $billingAddress->setCustomerAddressId(null);
                }
                $this->addressValidator->validateForCart($quote, $billingAddress);
                $quote->setBillingAddress($billingAddress);
            }

            $this->addressValidator->validateForCart($quote, $address);
            $carrierCode = $addressInformation->getShippingCarrierCode();
            $address->setLimitCarrier($carrierCode);
            $methodCode = $addressInformation->getShippingMethodCode();
            $quote = $this->prepareShippingAssignment($quote, $address, $carrierCode . '_' . $methodCode);

            $quote->setIsMultiShipping(false);

            $this->quoteRepository->save($quote);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new InputException(
                __('The shipping information was unable to be saved. Verify the input data and try again.')
            );
        }

        /** @var \Magento\Checkout\Api\Data\PaymentDetailsInterface $paymentDetails */
        $paymentDetails = $this->paymentDetailsFactory->create();
        $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
        $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));
        return $paymentDetails;
    }

    /**
     * Validate quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @throws InputException
     * @throws NoSuchEntityException
     * @return void
     */
    protected function validateQuote(\Magento\Quote\Model\Quote $quote)
    {
        if (0 == $quote->getItemsCount()) {
            throw new InputException(
                __("The shipping method can't be set for an empty cart. Add an item to cart and try again.")
            );
        }
    }

    /**
     * Prepare shipping assignment.
     *
     * @param CartInterface $quote
     * @param AddressInterface $address
     * @param string $method
     * @return CartInterface
     */
    private function prepareShippingAssignment(CartInterface $quote, AddressInterface $address, $method)
    {

        $cartExtension = $quote->getExtensionAttributes();
        if ($cartExtension === null) {
            $cartExtension = $this->cartExtensionFactory->create();
        }

        $shippingAssignments = $cartExtension->getShippingAssignments();
        if (empty($shippingAssignments)) {
            $shippingAssignment = $this->shippingAssignmentFactory->create();
        } else {
            $shippingAssignment = $shippingAssignments[0];
        }

        $shipping = $shippingAssignment->getShipping();
        if ($shipping === null) {
            $shipping = $this->shippingFactory->create();
        }

        $shipping->setAddress($address);
        $shipping->setMethod($method);
        $shippingAssignment->setShipping($shipping);
        $cartExtension->setShippingAssignments([$shippingAssignment]);
        return $quote->setExtensionAttributes($cartExtension);
    }
}
