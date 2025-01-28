<?php

namespace Fef\CustomShipping\Model\Custom;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Smartosc\Checkout\Helper\Data;

/**
 * Class Extends from SmartOsc
 */
class MoreAboutShippingDescription extends \Smartosc\Sales\Model\Custom\MoreAboutShippingDescription
{
    /**
     * @var \Smartosc\Checkout\Helper\Order\Data
     */
    protected $helper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Data
     */
    protected $checkoutDataHelper;

    /**
     * MoreAboutShippingDescription constructor.
     * @param $helper
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $checkoutDataHelper
     */
    public function __construct(
        \Smartosc\Checkout\Helper\Order\Data $helper,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        Data $checkoutDataHelper
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutDataHelper = $checkoutDataHelper;
    }

    /**
     * @param $order
     * @return string
     */
    public function info($order)
    {
        $newDescription = "";
        $shippingType   = $order->getShippingType(); // in_store_pickup | delivery

        if ($shippingType == 'in_store_pickup') {
            $pickupTime  = $order->getPickupTime();
            $pickupDate  = $order->getPickupDate();
            $pickupDate  = $this->helper->getDate($pickupDate);
            $pickupNote  = $order->getPickupComments();

            if ($pickupDate) {
                $newDescription .= __("Store Pickup Date: %1", $pickupDate) . "<br>";
            }
            if ($pickupTime) {
                $newDescription .= __("Store Pickup Timing: %1", $pickupTime) . "<br>";
            }
            if ($pickupNote) {
                $newDescription .= __("Pickup Note: %1", $pickupNote) . "<br>";
            }
        } elseif ($shippingType == 'delivery') {
            $deliveryDate = str_replace("--", "-", $order->getDeliveryDate());
            $deliveryDate = $this->helper->getDate($deliveryDate);
            $deliveryNote = $order->getDeliveryNote();
            $deliveryStairs = $order->getDeliveryStairs();

            if ($deliveryDate) {
                $newDescription .= __("Delivery Date: %1", $deliveryDate) . "<br>";
                $newDescription .= __("Delivery Timing: %1", $order->getDeliveryTimeslot()) . "<br>";
            }
            if ($deliveryStairs) {
                $newDescription .= __("Delivery Stairs: %1", $deliveryStairs. "<br>");
            }
            if ($deliveryNote) {
                $newDescription .= __("Delivery Note: %1", $deliveryNote);
            }
        }

        if ($order->getAcceptAuthorize()) {
            $storeId = $this->storeManager->getStore()->getId();
            $authorizeMessage = $this->scopeConfig->getValue('smartosc_authorize/authorize_settings/authorize_message', ScopeInterface::SCOPE_STORE, $storeId);
            $newDescription .= "<br>" . __("Authorize Message: %1", $authorizeMessage);
        }

        return $newDescription;
    }
}
