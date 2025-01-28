<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_<modulename>
 * @author    Webkul Software Private Limited
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Fef\CustomShipping\Controller\Ajax;

use Magento\Framework\Controller\ResultFactory;

class GetDelivery extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Action\Contex
     */
    private $context;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->context = $context;
    }
    
    /**
     * @return json
     */
    public function execute()
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');
        $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $helperData = $objectManager->get('\Fef\CustomShipping\Helper\Data');

        $checkoutQuote = $checkoutSession->getQuote();
        $checkoutQuoteId = $checkoutQuote->getId();
        $checkoutQuoteAddress = $checkoutQuote->getShippingAddress();
        $checkoutQuoteAddressShipping = str_replace("custom_","",$checkoutQuoteAddress->getShippingMethod());
        if($checkoutQuoteAddressShipping == "standart"){
            $checkoutQuoteAddressShipping = "standard";
        }
        $deliveryStairs = $checkoutQuote->getData('delivery_stairs');
        
        $deliverySlot = $checkoutQuote->getData('delivery_timeslot');
        $deliveryDate = $checkoutQuote->getData('delivery_date');

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $resultJson->setData([
            "message" => "failed", 
            "success" => false
        ]);

        if($checkoutQuoteId!=0){
            // //CALL SHIPPING RATE API
            $allItems = $checkoutQuote->getAllVisibleItems();
            $paramItems = [];
            $k = 0;
            foreach ($allItems as $item) {
                $productObject = $objectManager->create('Magento\Catalog\Model\Product');
                $item_s = $productObject->load($item->getProductId());
                $paramItems[$k] = array(
                    "id" => $item_s->getProsellerId(),
                    "weight" => (float)$item->getWeight() * $item->getQty(),
                    "quantity" => $item->getQty()
                );
                $k++;
            }

            $deliveryTime = "";
            if($deliverySlot){
                $deliverySlotArr = explode(" - ",$deliverySlot);
                $deliveryTime = $deliverySlotArr[0];
            }

            $apiParams = array(
                "postalCode" => $checkoutQuoteAddress->getPostcode(),
                "datetime" => $newDate = date("Y-m-d", strtotime($deliveryDate))." ".$deliveryTime,
                "others" => array(
                    "staircase" => $deliveryStairs,
                    "deliveryType" => $checkoutQuoteAddressShipping
                ),
                "items" => $paramItems,
            );
            if($helperData->getDebugMode()==1){
                // $logger->info("url : ".$helperData->getUrl("rate"));
                // $logger->info("apiParams : ".json_encode($apiParams));
            }
            $resGetRateResult = $helperData->setCurl(
                $helperData->getUrl("rate"),
                "POST",
                $apiParams,
                1
            );

            if($helperData->getDebugMode()==1){
                // $logger->info($resGetRateResult);
            }
            
            $resGetRateResultArray = json_decode($resGetRateResult,true);
            // $logger->info("resGetRateResultArray : ".print_r($resGetRateResultArray,true));
            if($resGetRateResultArray["status"]=="success"){
                $resultJson->setData([
                    "message" => "success", 
                    "success" => true
                ]);        
            } else {
                $message = "Failed to calculate delivery fee";
                $messagePrefix = "Failed to calculate delivery fee : ";

                if(isset($resGetRateResultArray["data"]["message"])){
                    $message = $messagePrefix.$resGetRateResultArray["data"]["message"];
                }elseif(isset($resGetRateResultArray["data"]["deliveryType"])){
                    $message = $messagePrefix.$resGetRateResultArray["data"]["deliveryType"];
                }
                
                $resultJson->setData([
                    "message" => $message, 
                    "success" => false
                ]);
            }
        }


        return $resultJson;
    }
}