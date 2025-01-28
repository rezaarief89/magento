<?php
 
namespace Fef\CustomShipping\Model\Rewrite\Quote\Address;

use \Magento\Checkout\Model\Session as CheckoutSession;

class Rate extends \Magento\Quote\Model\Quote\Address\Rate
{
    private $_url;
    private $_responseFactory;
    
    // public function __construct(
    //     \Magento\Framework\UrlInterface $url, 
    //     \Magento\Framework\App\ResponseFactory $responseFactory
    // ) {
    //     $this->_url = $url;
    //     $this->_responseFactory = $responseFactory;
    // }

    public function importShippingRate(\Magento\Quote\Model\Quote\Address\RateResult\AbstractResult $rate)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/shipping-rate.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("==================================================================");
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $urlInterface = $objectManager->get('\Magento\Framework\UrlInterface');
        $helperData = $objectManager->get('\Fef\CustomShipping\Helper\Data');
        $request = $objectManager->get('\Magento\Framework\App\RequestInterface');

        $token = $helperData->generateToken();
        if($token==""){
            if($helperData->getDebugMode()==1){
                $logger->info("Failed get shipping rate. Generate auth token was failed");
            }
        }

        $costData = $this->callApi($rate);

        $finalPrice = 0;
        $isFailed = 0;
        $failedMessage = "";
        $methodDescription = "<br/>";

        if(!empty($costData)){
            $finalPrice = $costData["totalDeliveryCost"];
            if(isset($costData["deliveryCostBreakdown"])){
                $deliveryCostBreakdown = $costData["deliveryCostBreakdown"];
                foreach ($deliveryCostBreakdown as $key => $value) {
                    $methodDescription.="$key:$value <br/>";
                }
            }
            
            if ($rate instanceof \Magento\Quote\Model\Quote\Address\RateResult\Error) {
                $this->setCode(
                    $rate->getCarrier() . '_error'
                )->setCarrier(
                    $rate->getCarrier()
                )->setCarrierTitle(
                    $rate->getCarrierTitle()
                )->setErrorMessage(
                    $rate->getErrorMessage()
                );
            } elseif ($rate instanceof \Magento\Quote\Model\Quote\Address\RateResult\Method) {
                $this->setCode(
                    $rate->getCarrier() . '_' . $rate->getMethod()
                )->setCarrier(
                    $rate->getCarrier()
                )->setCarrierTitle(
                    $rate->getCarrierTitle()
                )->setMethod(
                    $rate->getMethod()
                )->setMethodTitle(
                    $rate->getMethodTitle()
                )->setMethodDescription(
                    $rate->getMethodDescription()
                )->setPrice(
                    $finalPrice
                );
            }
        }
        
        return $this;
    }

    private function callApi($rate)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/shipping-rate.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');
        $helperData = $objectManager->get('\Fef\CustomShipping\Helper\Data');

        $checkoutQuote = $checkoutSession->getQuote();
        $checkoutQuoteId = $checkoutQuote->getId();
        $checkoutQuoteAddress = $checkoutQuote->getShippingAddress();
        $checkoutQuoteAddressShipping = str_replace("custom_","",$checkoutQuoteAddress->getShippingMethod());
        if($checkoutQuoteAddressShipping == "standart"){
            $checkoutQuoteAddressShipping = "standard";
        }
        $deliveryStairs = $checkoutQuote->getData('delivery_stairs');

        $logger->info("deliveryStairs : $deliveryStairs");
        
        $deliverySlot = $checkoutQuote->getData('delivery_timeslot');
        $deliveryDate = $checkoutQuote->getData('delivery_date');

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
                $logger->info("apiParams : ".json_encode($apiParams));
            }
            $resGetRateResult = $helperData->setCurl(
                $helperData->getUrl("rate"),
                "POST",
                $apiParams,
                1
            );

            if($helperData->getDebugMode()==1){
                $logger->info($resGetRateResult);
            }
            
            $resGetRateResultArray = json_decode($resGetRateResult,true);
            // $logger->info("resGetRateResultArray : ".print_r($resGetRateResultArray,true));
            if($resGetRateResultArray["status"]=="success"){
                $rateData = $resGetRateResultArray["data"];
                $this->setAdditionalCost($checkoutQuote,$rateData["deliveryCostBreakdown"]);
                // return $rateData["totalDeliveryCost"];
                return $rateData;
            } else {
                $message = "failed";
                if(isset($resGetRateResultArray["data"]["message"])){
                    $message = $resGetRateResultArray["data"]["message"];
                }elseif(isset($resGetRateResultArray["data"]["deliveryType"])){
                    $message = $resGetRateResultArray["data"]["deliveryType"];
                }
                return [
                    "totalDeliveryCost" => 0,
                    "message" => $message,
                    "error" => true
                ];
            }
        }
    }

    private function setAdditionalCost($quote,$apiResult)
    {
        if(isset($apiResult["weight"])){
            $quote->setData("cost_weight", $apiResult["weight"]);
        }else{
            $quote->setData("cost_weight", 0);
        }
        if(isset($apiResult["location"])){
            $quote->setData("cost_location", $apiResult["location"]);
        }else{
            $quote->setData("cost_location", 0);
        }
        if(isset($apiResult["itemSpecific"])){
            $quote->setData("cost_item_spesific", $apiResult["itemSpecific"]);
        }else{
            $quote->setData("cost_item_spesific", 0);
        }
        if(isset($apiResult["period"])){
            $quote->setData("cost_period", $apiResult["period"]);
        }else{
            $quote->setData("cost_period", 0);
        }
        if(isset($apiResult["staircase"])){
            $quote->setData("cost_staircase", $apiResult["staircase"]);
        }else{
            $quote->setData("cost_staircase", 0);
        }
        if(isset($apiResult["deliveryType"])){
            $quote->setData("cost_delivery_type", $apiResult["deliveryType"]);
        }else{
            $quote->setData("cost_delivery_type", 0);
        }
        $quote->save();
    }

}