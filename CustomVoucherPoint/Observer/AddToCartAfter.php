<?php

namespace Fef\CustomVoucherPoint\Observer;

use Psr\Log\LoggerInterface;
use Magento\Customer\Model\AddressFactory;
use Fef\CustomShipping\Model\ShippingInformationManagement;

/**
 * Class SavePickUpInOrder
 * @package Smartosc\Checkout\Observer
 */
class AddToCartAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
        $customHelper = $objectManager->get('\Fef\CustomVoucherPoint\Helper\Data');
        $helperData = $objectManager->get('\Fef\CustomShipping\Helper\Data');
        $voucherPointFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointFactory');
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');
        $zokuRewardQuoteFactory = $objectManager->get('\Zoku\Rewards\Model\ResourceModel\Quote');
        $modelCart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');
        $productObject = $objectManager->create('Magento\Catalog\Model\Product');

        


        $redeemUrl = $helperData->getConfig("carriers/custom/base_url")."loyalty/redeem";

        $items = $modelCart->getQuote()->getAllItems();

        $detailsProduct = [];

        foreach($items as $item) {

            $productId = $item->getProduct()->getId();

            if ($item->getHasChildren() ) {
                foreach ($item->getChildren() as $child) {
                    $item_s = $productObject->load($child->getProduct()->getId());
                    $detailsProduct[] = array(
                        "productId"=>$item_s->getProsellerId(),
                        "quantity"=>$item->getQty(),
                        "unitPrice"=>$child->getProduct()->getFinalPrice(),
                        "discount"=>$child->getProduct()->getDiscount() == NULL ? 0 : $child->getProduct()->getDiscount(),
                        "modifiers"=> array()
                    );
                }
            }else{
                $item_s = $productObject->load($productId);
                $detailsProduct[] = array(
                    "productId"=>$item_s->getProsellerId(),
                    "quantity"=>$item->getQty(),
                    "unitPrice"=>$item_s->getFinalPrice(),
                    "discount"=>$item_s->getDiscount() == NULL ? 0 : $item_s->getDiscount(),
                    "modifiers"=> array()
                );    
            }

            // $logger->info("ID : ".$item->getProductId());
            // $logger->info("Name : ".$item->getName());
            // $logger->info("Sku : ".$item->getSku());
            // $logger->info("Quantity : ".$item->getQty());
            // $logger->info("Price : ".$item->getPrice());
        }

        // $logger->info("details : ".print_r($details,true));

        $quote = $checkoutSession->getQuote();
        
        try {
                
            if($customerSession->getId() && $quote->getId()){
                $logger->info("masuk ".$customerSession->getId()." || ".$quote->getId());
                $customerId = $customerSession->getId();
                $quoteId = $quote->getId();
    
                $pointUsed = $voucherPointUsedFactory->create();
                $pointUsedCollection = $pointUsed
                ->getCollection()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('quote_id', $quoteId);
                $dataCollection = $pointUsedCollection->getData();
                if(count($dataCollection) > 0){
                    if((int)$quote->getData('zokurewards_point') > 0){
                        $customHelper->calculateOrder($dataCollection[0]["used_voucher"],$quote->getData('zokurewards_point'),$detailsProduct);
                    }else{
                        $customHelper->calculateOrder($dataCollection[0]["used_voucher"],0,$detailsProduct);
                    }
                }else{
                    if((int)$quote->getData('zokurewards_point') > 0){
                        $customHelper->calculateOrder("",$quote->getData('zokurewards_point'),$detailsProduct);
                    }else{
                        $customHelper->calculateOrder("",0,$detailsProduct);
                    }
                }
    
            }

        } catch (\Exception $ex) {
            $logger->info($ex->getMessage());
        }
    

        return $this;
    }
}
