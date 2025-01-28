<?php

namespace Fef\CustomVoucherPoint\Observer;

use Psr\Log\LoggerInterface;
use Magento\Customer\Model\AddressFactory;
use Fef\CustomShipping\Model\ShippingInformationManagement;



/**
 * Class SavePickUpInOrder
 * @package Smartosc\Checkout\Observer
 */
class QuoteCollectAfter implements \Magento\Framework\Event\ObserverInterface
{
    private $_countLoop = 0;
    
    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * dipakai saat perubahan data quote / cart
         * di checkout/cart pun dipanggl
         */

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("QuoteCollectAfter");

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
        $customHelper = $objectManager->get('\Fef\CustomVoucherPoint\Helper\Data');
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');
        $CalculateTempFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\CalculateTempFactory');
        $urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');

        $totalF = $objectManager->get('\Magento\Quote\Model\Quote\Address\Total');

        $quote = $observer->getEvent()->getQuote();
        
        
        // $logger->info($urlInterface->getCurrentUrl()." || ".$customerSession->getId()." || ".$quote->getId());

        if(strpos($urlInterface->getCurrentUrl(),"updatePost") != FALSE || strpos($urlInterface->getCurrentUrl(),"updateItemQty") != FALSE
        || strpos($urlInterface->getCurrentUrl(),"totals-information") != FALSE || strpos($urlInterface->getCurrentUrl(),"shipping-information") != FALSE
        || strpos($urlInterface->getCurrentUrl(),"syncemail") != FALSE || strpos($urlInterface->getCurrentUrl(),"points") != FALSE
        || strpos($urlInterface->getCurrentUrl(),"couponPost") != FALSE || strpos($urlInterface->getCurrentUrl(),"totals?") != FALSE){
            
            try {
                
                // if(strpos($urlInterface->getCurrentUrl(),"totals-information") != FALSE){
                //     $logger->info("START");
                //     $itemsVisible = $quote->getAllVisibleItems();
                //     foreach($itemsVisible as $item) {
                //         $logger->info('ID: '.$item->getProductId());
                //         $logger->info('Name: '.$item->getName());
                //         $logger->info('Discount: '.$item->getDiscountAmount());
                //    }
                // }

                if($customerSession->getId() && $quote->getId()){
    
                    $customerId = $customerSession->getId();
                    $quoteId = $quote->getId();
        
                    $pointUsed = $voucherPointUsedFactory->create();
                    $pointUsedCollection = $pointUsed
                    ->getCollection()
                    ->addFieldToFilter('customer_id', $customerId)
                    ->addFieldToFilter('quote_id', $quoteId);
                    $dataCollection = $pointUsedCollection->getData();
                    if(count($dataCollection) > 0){
                        // $logger->info("masuk : ".$urlInterface->getCurrentUrl()." || ".$customerSession->getId()." || ".$quote->getId());
                        if((int)$quote->getData('zokurewards_point') > 0){
                            $customHelper->calculateOrder($dataCollection[0]["used_voucher"],$quote->getData('zokurewards_point'));
                        }else{
                            $customHelper->calculateOrder($dataCollection[0]["used_voucher"],0);
                        }
                    }else{
                        if((int)$quote->getData('zokurewards_point') > 0){
                            $customHelper->calculateOrder("",$quote->getData('zokurewards_point'));
                        }else{
                            $customHelper->calculateOrder("",0);
                        }
                    }
                } else{
                    $customHelper->calculateOrder("",0);
                }

            } catch (\Exception $ex) {
                $logger->info($ex->getMessage());
            }
        }

        // $quote->collectTotals()->save();

        return $this;
    }
}
