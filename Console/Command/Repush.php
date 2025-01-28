<?php

namespace KTech\Checkout\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;


class Repush extends Command
{
    const NUMBER = 'number';
    private $state;
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
        ResourceConnection $resource,
        \Magento\Framework\App\State $state,
        $name = null
    ){ 
        $this->state = $state;
        $this->ktechHelper = $ktechHelper;
        $this->logger = $logger;
        $this->orderRepository = $orderRepositoryInterface;
        $this->jsonHelper = $jsonHelper;
        $this->customerRepository = $customerRepository;
        $this->resource = $resource;
        parent::__construct($name);
	}

    protected function configure()
    {
        $options = [
            new InputOption(
				self::NUMBER,
				null,
				InputOption::VALUE_OPTIONAL,
				'Order increment Number'
			)
		];
        $this->setName('order:proseller:repush');
        $this->setDescription('Repush Order to Proseller');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        
        $incNumber = $input->getOption(self::NUMBER);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $ordInterface = $objectManager->get('\Magento\Sales\Api\Data\OrderInterface');

        $ordCollection = $ordInterface->getCollection();
        $ordCollection->addAttributeToFilter('increment_id',$incNumber);

        foreach ($ordCollection as $order) {
            $result = $this->arrangeAndSend($order);
            $output->writeln("result : ".$result);
        }

        return 1;
    }

    private function arrangeAndSend($order){
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/ktech.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);


        $resultArr = [];

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerId = $order->getCustomerId();
        
        if($customerId == 41675){
            return $this;
        }

        $dataOrder = [];
        $status = $order->getStatus();
        if ($status != "processing") {
            return $this;
        }
        $outletId = '8f6dd4f9-b5cc-4031-a799-6af53bb92e35';
        $dataOrder['outletId'] = $outletId;

        $order = $this->orderRepository->get($order->getId());
        //json order
        $totalDiscountAmount = $order->getBaseDiscountAmount();
        $totalGrossAmount = $order->getBaseGrandTotal() + ($totalDiscountAmount);
        $totalTaxAmount = $order->getBaseTaxAmount();
        $totalNetAmount = $order->getBaseGrandTotal();
        $transRefNo = $order->getIncrementId();
        $shippingMethod = $order->getShippingMethod();
        
        if($shippingMethod=="in_store_pickup_in_store_pickup"){
            $shippingMethod = "STOREPICKUP";
        } else {
            $shippingMethod = "DELIVERY";
        }
        //RAW CHANGES END

        $dataOrder['totalGrossAmount']   = $totalGrossAmount;
        $dataOrder['totalDiscountAmount']   = (float)$totalDiscountAmount;
        $dataOrder['totalTaxAmount']   = (float)$totalTaxAmount;
        $dataOrder['transactionRefNo']   = $transRefNo;
        $dataOrder['orderingMode'] = $shippingMethod;
        

        $shippingAddress = $order->getShippingAddress();
        
        $city = "";
        $postCode = "";
        $street = [];
        $phoneNumber = "";
        
        if($shippingAddress != NULL){
            $city = $shippingAddress->getCity();    
            $postCode = (int)$shippingAddress->getPostcode();//RAW CHANGES 
            $street = $shippingAddress->getStreet();
            $phoneNumber = $shippingAddress->getTelephone();
        }
        
        
        $name = $order->getCustomerFirstname()." ".$order->getCustomerLastname();

        $address = "";
        if(!empty($street)){
            $address = $street[0].", ".$city.", ".$postCode;    
        }
        
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
        // $logger->debug('street: ' . $street[0]);
        $logger->debug('address: ' . $address);
        $logger->debug('unitNo: ' . $unitNo);
        $logger->debug('orderId: ' . $order->getId());
        $logger->debug('baseGrandTotal: ' . $order->getBaseGrandTotal());
        $logger->debug('GrandTotal: ' . $order->getGrandTotal());
        $logger->debug('totalTaxAmount: ' . $order->getBaseTaxAmount());
        $logger->debug('getBaseDiscountAmount: ' . $order->getBaseDiscountAmount());


        $streetVar = "";
        if(!empty($street)){
            $streetVar = $street[0];
        }
        
        $dataOrder['delivery'] = [
            "address" => [
                "unitNo" =>$unitNo,
                "postalCode" => '"'.$postCode.'"',
                "city" =>$city,
                "address" => $address,
                "street" => $streetVar
            ],
            "cost" => (float)$order->getShippingInclTax(),
            "recipient" => [
                "name" => $name,
                "phoneNumber" => $phoneNumber
            ]
        ];
        $dataOrder["date"] = $deliveryDate;
        $dataOrder["time"] = $deliveryTime;
        

        //Payment
        $payment = $order->getPayment();
        $paymentType = "paypal express checkout";
        if($payment != NULL){
            $paymentType = $payment->getMethodInstance()->getTitle();
        }
        
        $dataOrder['payments'][0]['paymentAmount']     = (float)$totalNetAmount;
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
        $lineItems = $productPriceAndAmount["details"];

        $dataOrder['totalNettAmount'] = $dataOrder['totalGrossAmount'] - $dataOrder['totalDiscountAmount'];
        $dataOrder['details'] = $lineItems;

        $logger->info(json_encode($dataOrder));
        $logger->info(print_r($dataOrder,true));
        

        if ($status == "processing") {
            $sendOrder = $this->ktechHelper->setCurl(
                $this->ktechHelper->getBaseUrl().'transactions',
                "POST",
                $dataOrder,
                1
            );
            $resultArr["transactions"] = $sendOrder;
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
            $resultArr["transactions_custom"] = $updateOrder;

            $helper->removeUsedVoucherFromList($order->getCustomerId(), $order->getQuoteId());
            $helper->clearUsedVoucherCustomer($order->getCustomerId());

            // $logger->debug('resultUpdate:');
            // $logger->info(print_r($resultUpdate,true));
        }

        
        return json_encode($resultArr);
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
