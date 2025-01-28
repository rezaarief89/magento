<?php
/**
 *
 */
namespace Fef\CustomShipping\Plugin;

use Magento\Checkout\Controller\Cart;
use Magento\Shipping\Model\Config as ShippingConfig;

class CartPlugin
{
    /**
     *
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\ShippingMethodManagement $shippingMethodManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->scopeConfig = $scopeConfig;
    }
    
    /**
     *
     */
    public function beforeExecute(Cart $subject)
    {
        $quote = $this->checkoutSession->getQuote();

        if (!$quote->getId()) {
            return [];
        }

        try {
            $shippingAddress = $quote->getShippingAddress();
        
            if ($shippingAddress->getShippingMethod()) {
                return [];
            }
            
            $shippingAddress->setCollectShippingRates(true);
            
            $shippingMethods = $this->shippingMethodManager->getList($quote->getId());
            if ($shippingMethods && count($shippingMethods) > 0) {
                $shippingMethod = array_shift($shippingMethods);

                $this->shippingMethodManager->set(
                    $quote->getId(),
                    $shippingMethod->getCarrierCode(),
                    $shippingMethod->getMethodCode()
                );
                
                $quote->save();
            }

        } catch (\Exception $ex) {
            //throw $th;
        }
        
        return [];
    }
    
    /**
     * This is taken from the origin so change this if you don't want to use the origin
     * If this returns false then the auto shipping method is not set
     *
     * @return string
     */
    private function getDefaultCountryId()
    {
        return $this->scopeConfig->getValue(
            ShippingConfig::XML_PATH_ORIGIN_COUNTRY_ID
        ); 
    }
}