<?php
/**
 * Copyright © ktech All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace KTech\Checkout\Observer;

use KTech\Checkout\Logger\Logger;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

class OrderSaveAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Order Model
     *
     * @var \Magento\Sales\Model\Order $order
     */
    protected $jsonHelper;
    protected $orderRepository;
    protected $ktechHelper;
    protected $customerRepository;
    protected $resource;


    public function __construct(
        \KTech\Checkout\Helper\Data $ktechHelper,
        LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        CustomerRepositoryInterface $customerRepository,
        ResourceConnection $resource
    ) {
        $this->ktechHelper = $ktechHelper;
        $this->logger = $logger;
        $this->orderRepository = $orderRepositoryInterface;
        $this->jsonHelper = $jsonHelper;
        $this->customerRepository = $customerRepository;
        $this->resource = $resource;
    }


    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/ktech.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->get('\Fef\CustomVoucherPoint\Helper\Data');
        $order = $observer->getEvent()->getOrder();

        $dataOrder = [];
        $order = $observer->getEvent()->getOrder();
        $status = $order->getStatus();
        if ($status != "processing") {
            $logger->debug('Order: ' . $order->getId() . ' not processing.');
            $logger->debug('Status: ' . $status);
            return $this;
        }
        $order = $observer->getEvent()->getOrder();
        //outlet ID
        $outletId = 'bc1fe937-8a63-4002-bfc7-727d2e78a2db';
        $dataOrder['outletId'] = $outletId;

        $order = $this->orderRepository->get($order->getId());
        //json order
        $totalDiscountAmount = $order->getBaseDiscountAmount();
        $totalGrossAmount = $order->getBaseGrandTotal() + ($totalDiscountAmount);
        $totalTaxAmount = $order->getBaseTaxAmount();
        
        $totalNetAmount = $order->getBaseGrandTotal();
        // $totalNetAmount = $order->getBaseGrandTotal() - $order->getShippingInclTax();

        $transRefNo = $order->getIncrementId();
        $shippingMethod = $order->getShippingMethod();
        //RAW CHANGES START
        if($shippingMethod=="in_store_pickup_in_store_pickup"){
            $shippingMethod = "STOREPICKUP";
        } else {
            $shippingMethod = "DELIVERY";
        }
        //RAW CHANGES END

        // $dataOrder['totalNettAmount']   = $totalNetAmount;
        $dataOrder['totalGrossAmount']   = $totalGrossAmount;
        $dataOrder['totalDiscountAmount']   = $totalDiscountAmount;
        $dataOrder['totalTaxAmount']   = $totalTaxAmount;
        $dataOrder['transactionRefNo']   = $transRefNo;
        $dataOrder['orderingMode'] = $shippingMethod;
        

        $shippingAddress = $order->getShippingAddress();
        $city = $shippingAddress->getCity();
        $postCode = (int)$shippingAddress->getPostcode();//RAW CHANGES 
        $street = $shippingAddress->getStreet();
        $phoneNumber = $shippingAddress->getTelephone();
        $name = $order->getCustomerFirstname()." ".$order->getCustomerLastname();

        // $logger->info(print_r($street,true));

        $address = $street[0].", ".$city.", ".$postCode;
        $unitNo = $order->getShippingBuilding()." ".$order->getShippingFloor();

        //RAW CHANGES START
        if($shippingMethod=="STOREPICKUP"){
            $deliveryDate = $order->getPickupDate();
            $deliveryTime = $order->getPickupTime();
        } else {
            $deliveryDate = $order->getDeliveryDate();
            $deliveryTime = $order->getDeliveryTimeslot();
        }
        //RAW CHANGES END


        $logger->debug('dateTime: ' . $deliveryDate." - ".$deliveryTime);
        $logger->debug('city: ' . $city);
        $logger->debug('postCode: ' . $postCode);
        $logger->debug('street: ' . $street[0]);
        $logger->debug('address: ' . $address);
        $logger->debug('unitNo: ' . $unitNo);
        $logger->debug('orderId: ' . $order->getId());
        $logger->debug('baseGrandTotal: ' . $order->getBaseGrandTotal());
        $logger->debug('GrandTotal: ' . $order->getGrandTotal());
        $logger->debug('totalTaxAmount: ' . $order->getBaseTaxAmount());
        $logger->debug('getBaseDiscountAmount: ' . $order->getBaseDiscountAmount());

        

        $dataOrder['delivery'] = [
            "address" => [
                "unitNo" =>$unitNo,
                "postalCode" => '"'.$postCode.'"',
                "city" =>$city,
                "address" => $address,
                "street" => $street[0]
            ],
            "cost" => $order->getShippingInclTax(),
            "recipient" => [
                "name" => $name,
                "phoneNumber" => $phoneNumber
            ]
        ];
        $dataOrder["date"] = $deliveryDate;
        $dataOrder["time"] = $deliveryTime;

        //Payment
        $payment = $order->getPayment();
        $paymentType = $payment->getMethodInstance()->getTitle();
        $paymentAmount = $payment->getAmountPaid();
        $dataOrder['payments'][0]['paymentAmount']     = $totalNetAmount;
        $dataOrder['payments'][0]['paymentType']       = $paymentType;


        //data customer
        if($order->getCustomerIsGuest()){
            $prosellerMemberId = "3237c0f7-c31a-4f71-bdaf-8c9917772d03";
        }else{
            $customerId = $order->getCustomerId();
            $customer = $this->customerRepository->getById($customerId);
            $prosellerMemberId = $customer->getCustomAttribute('proseller_member_id')->getValue();
        }
        $dataOrder['customerId']    = $prosellerMemberId;
        


        //item
        $items = $order->getAllItems();

        $productOrder = [];
        $lineItems = [];

        $productPriceAndAmount = $this->getListProductPriceAmount($order);
        $voucherAndPointList = $this->getVoucher($order);

        $nettAmountAll = 0;

        if(isset($productPriceAndAmount["totalMembershipDiscountAmount"])){
            $dataOrder['totalMembershipDiscountAmount'] = $productPriceAndAmount["totalMembershipDiscountAmount"];    
        }else{
            $dataOrder['totalMembershipDiscountAmount'] = 0;
        }        

        // $logger->info("productPriceAndAmount : ".print_r($productPriceAndAmount,true));

        $lineItems = $productPriceAndAmount["details"];

        // foreach ($lineItems as $keyItem => $lineItem) {
        //     foreach ($items as $item) {
        //         if (!$item->getHasChildren() ) {
        //             $productName = $item->getName();
        //             $prosellerId = $item->getProduct()->getCustomAttribute('proseller_id')->getValue();

        //             if(isset($lineItems[$keyItem]["productId"]) && $lineItems[$keyItem]["productId"] == $prosellerId){
                        
        //                 $logger->info("productName : $productName, prosellerId : $prosellerId");

        //                 $lineItems[$keyItem]["productName"] = $productName;
        //                 $lineItems[$keyItem]["product"]["name"] = $productName;
        //             }
        //         }
        //     }
        // }
      
        $dataOrder['totalNettAmount'] = $dataOrder['totalGrossAmount'] - $dataOrder['totalDiscountAmount'];
        $dataOrder['details'] = $lineItems;

//        $this->ktechHelper->generateToken();
        // $logger->info('order',$dataOrder);
        $logger->info(json_encode($dataOrder));
        $logger->info(print_r($dataOrder,true));
        // $logger2->info(print_r($dataOrder,true));
        

        if ($status == "processing") {
            $sendOrder = $this->ktechHelper->setCurl(
                $this->ktechHelper->getBaseUrl().'transactions',
                "POST",
                $dataOrder,
                1
            );
            $result = json_decode($sendOrder, true);
            $logger->info(json_encode($result));

        }
        $logger->info(print_r($result,true));

        if(isset($result["status"]) && $result["status"]=="success"){
            $connection = $this->resource->getConnection();
            $query = "UPDATE `sales_order` SET `preseller_order_id`= '".$result["data"]["id"]."' WHERE entity_id = ".$order->getId();
            $connection->query($query);

            $dataUpdate['custom'] = [
                "magentoid" => $order->getId()
            ];
            $updateOrder = $this->ktechHelper->setCurl(
                $this->ktechHelper->getBaseUrl().'transactions/'.$result["data"]["id"].'/custom',
                "PATCH",
                $dataUpdate,
                1
            );
            $resultUpdate = json_decode($updateOrder, true);

            $helper->removeUsedVoucherFromList($order->getCustomerId(), $order->getQuoteId());
            $helper->clearUsedVoucherCustomer($order->getCustomerId());

            // $logger->debug('resultUpdate:');
            // $logger->info(print_r($resultUpdate,true));
        }

        
        // $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        // $logger = new \Zend_Log();
        // $logger->addWriter($writer);
        // $logger->info("order save after ktech");
        return $this;
    }

    private function getVoucher($order)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
        $customHelper = $objectManager->get('\Fef\CustomVoucherPoint\Helper\Data');
        $helperData = $objectManager->get('\Fef\CustomShipping\Helper\Data');
        $voucherPointFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointFactory');
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');
        $zokuRewardQuoteFactory = $objectManager->get('\Zoku\Rewards\Model\ResourceModel\Quote');

        $customerId = $order->getCustomerId();
        $quoteId = $order->getQuoteId();

        $logger->info("customerId & quoteId : $customerId || $quoteId");

        
        $voucherPointUsedCollection = $voucherPointUsedFactory->create()
        ->getCollection()
        ->addFieldToFilter('customer_id', $customerId)
        ->addFieldToFilter('quote_id', $quoteId);
        $voucherUsedData = $voucherPointUsedCollection->getData();

        $usedVoucher = "";
        if(count($voucherUsedData) > 0 ){
            $usedVoucher = $voucherUsedData[0]["used_voucher"];
        }

        $logger->info("usedVoucher : $usedVoucher");

        $voucherPoint = $voucherPointFactory->create()
            ->getCollection()
            ->addFieldToSelect(array("proseller_member_id","member_voucher_list"))
            ->addFieldToFilter('customer_id',array('eq' => $customerId));

        $listVoucherArr = $voucherPoint->getData();

        $memberId = "";
        $availVouchers = array();
        foreach ($listVoucherArr as $listVoucher) {
            $availVouchers = json_decode($listVoucher["member_voucher_list"],true);
            $memberId = $listVoucher["proseller_member_id"];
        }
        $logger->info("memberId : $memberId");

        $serialNumberVoucher = "";
        $voucherName = "";

        // $logger->info("availVouchers : ".print_r($availVouchers,true));

        if($usedVoucher!=""){
            foreach ($availVouchers as $availVoucher){
                if($availVoucher["id"]==$usedVoucher){
                    $serialNumberVoucher = $availVoucher["serialNumber"];
                    $voucherName = $availVoucher["name"];
                }
            }
        }
        // $logger->info("serialNumberVoucher : $serialNumberVoucher || $voucherName");

        $zokuRewardQuoteCollection = $zokuRewardQuoteFactory->loadByQuoteId($quoteId);

        $usedPoints = 0;
        if(!empty($zokuRewardQuoteCollection)){
            $usedPoints = $zokuRewardQuoteCollection["reward_points"];
        }

        $voucherParams = array(
            "id" => $usedVoucher,
            "name" => $voucherName,
            "serialNumber" => $serialNumberVoucher
        );

        $pointParams = array(
            "used" => $usedPoints
        );

        return array(
            "vouchersDiscount" => $voucherParams,
            "pointsDiscount" => $pointParams
        );
    }

    private function getListProductPriceAmount($order)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
        $customHelper = $objectManager->get('\Fef\CustomVoucherPoint\Helper\Data');
        $helperData = $objectManager->get('\Fef\CustomShipping\Helper\Data');

        $voucherPointFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointFactory');
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');

        $voucherCalculateTempFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\CalculateTempFactory');
        $zokuRewardQuoteFactory = $objectManager->get('\Zoku\Rewards\Model\ResourceModel\Quote');
        

        if($order->getCustomerIsGuest()){
            $customerId = 0;
        }else{
            $customerId = $order->getCustomerId();
        }
        
        $quoteId = $order->getQuoteId();

        $logger->info("customerId & quoteId : $customerId || $quoteId");

        
        $voucherCalculateTempCollection = $voucherCalculateTempFactory->create()
        ->getCollection()
        ->addFieldToSelect(array("calculate_result"))
        ->addFieldToFilter('customer_id', $customerId)
        ->addFieldToFilter('quote_id', $quoteId);
        $voucherCalculateTempData = $voucherCalculateTempCollection->getData();


        $lineItems = [];

        $dataCalculateTemp = [];
        $dataCalculateTempItem = [];
        $dataCalculateTempLineItem = [];

        foreach ($voucherCalculateTempData as $voucherCalculateTemp) {
            $tempResultArr = json_decode($voucherCalculateTemp["calculate_result"],true);
            $productDetails = $tempResultArr['details'];
            foreach ($productDetails as $detail) {
                $dataCalculateTempItem = $detail;
            }
            $dataCalculateTempLineItem = $productDetails;
            
            $dataCalculateTemp["totalMembershipDiscountAmount"] = $tempResultArr["totalMembershipDiscountAmount"];
            $dataCalculateTemp["details"] = $dataCalculateTempLineItem;
        }
        
        // $logger->info("dataCalculateTemp : ".print_r($dataCalculateTemp,true));

        return $dataCalculateTemp;
    }

    private function getListProductPoint($order)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $voucherCalculateTempFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\CalculateTempFactory');

        $customerId = $order->getCustomerId();
        $quoteId = $order->getQuoteId();
        
        $voucherCalculateTempCollection = $voucherCalculateTempFactory->create()
        ->getCollection()
        ->addFieldToSelect(array("calculate_result"))
        ->addFieldToFilter('customer_id', $customerId)
        ->addFieldToFilter('quote_id', $quoteId);
        $voucherCalculateTempData = $voucherCalculateTempCollection->getData();

        $lineItems = [];

        foreach ($voucherCalculateTempData as $voucherCalculateTemp) {
            $tempResultArr = json_decode($voucherCalculateTemp["calculate_result"],true);
            $productDetails = $tempResultArr['details'];
            foreach ($productDetails as $detail) {
                if(!isset($detail["productId"])){
                    $lineItems[] = $detail;
                }                
            }
        }
        // $logger->info(print_r($lineItems,true));

        return $lineItems;
    }
}

