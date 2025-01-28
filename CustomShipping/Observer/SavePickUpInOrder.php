<?php

namespace Fef\CustomShipping\Observer;

use Psr\Log\LoggerInterface;
use Magento\Customer\Model\AddressFactory;
use Fef\CustomShipping\Model\ShippingInformationManagement;

/**
 * Class SavePickUpInOrder
 * @package Smartosc\Checkout\Observer
 */
class SavePickUpInOrder implements \Magento\Framework\Event\ObserverInterface
{
    const STORE_PICKUP = 'in_store_pickup';
    const DELIVERY     = 'delivery';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var AddressFactory
     */
    protected $addressFactory;

    /**
     * @param LoggerInterface $logger
     * @param AddressFactory $addressFactory
     */
    public function __construct(
        LoggerInterface $logger,
        AddressFactory $addressFactory
    ) {
        $this->logger = $logger;
        $this->addressFactory = $addressFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $observer->getEvent()->getOrder();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /**
         * @var string $shippingType
         * shipping type is either delivery of store pickup
         */
        $shippingType = $quote->getShippingType();

        if ($shippingType == self::STORE_PICKUP) {
            $order->setData('pickup_comments', $quote->getPickupComments())
                  ->setData('shipping_type', $shippingType)
                  ->setData('pickup_date', $quote->getPickupDate())
                  ->setData('pickup_time', $quote->getPickupTime())
                ->setData(ShippingInformationManagement::PICKUP_STORE_NAME, $quote->getPickupStoreName())
                ->setData(ShippingInformationManagement::PICKUP_STORE_ADDRESS, $quote->getPickupStoreAddress())
                ->setData(ShippingInformationManagement::PICKUP_STORE_STATE, $quote->getPickupStoreState())
                ->setData(ShippingInformationManagement::PICKUP_STORE_ZIP, $quote->getPickupStoreZip());

            if ($shippingBuilding = $quote->getShippingBuilding()) {
                $order->setData('shipping_building', "");
            }

            if ($shippingFloor = $quote->getShippingFloor()) {
                $order->setData('shipping_floor', "");
            }

            $order->getShippingAddress()->setData("prefix", "");
        } elseif ($shippingType == self::DELIVERY) {
            $order->setData('delivery_date', $quote->getDeliveryDate())
                  ->setData('shipping_type', $shippingType)
                  ->setData('delivery_note', $quote->getDeliveryNote())
                  ->setData('delivery_timeslot', $quote->getDeliveryTimeslot())
                  ->setData('delivery_stairs', $quote->getDeliveryStairs())
                  ->setData('cost_weight', $quote->getDeliveryStairs())
                  ->setData('cost_location', $quote->getDeliveryStairs())
                  ->setData('cost_item_spesific', $quote->getDeliveryStairs())
                  ->setData('cost_staircase', $quote->getDeliveryStairs())
                  ->setData('cost_period', $quote->getDeliveryStairs())
                  ->setData('cost_delivery_type', $quote->getDeliveryStairs());

            if ($shippingBuilding = $quote->getShippingBuilding()) {
                $order->setData('shipping_building', $shippingBuilding);
            }
            if ($shippingFloor = $quote->getShippingFloor()) {
                $order->setData('shipping_floor', $shippingFloor);
            }
            if ($acceptAuthorize = $quote->getAcceptAuthorize()) {
                $order->setData('accept_authorize', $acceptAuthorize);
            }
        }

        // saving billing/shipping custom attributes: building, floor
        if ($billingBuilding = $quote->getBillingBuilding()) {
            $order->setData('billing_building', $billingBuilding);
        }
        if ($billingFloor = $quote->getBillingFloor()) {
            $order->setData('billing_floor', $billingFloor);
        }

        if ($quote->getGiftMessage()) {
            $order->setData('gift_message', $quote->getGiftMessage());
        }
        if ($quote->getGiftMessageFrom()) {
            $order->setData('gift_message_from', $quote->getGiftMessageFrom());
        }
        if ($quote->getGiftMessageTo()) {
            $order->setData('gift_message_to', $quote->getGiftMessageTo());
        }

        return $this;
    }
}
