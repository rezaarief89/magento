<?php

namespace Fef\CustomVoucherPoint\Helper;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerInterfaceFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Store\Model\StoreManagerInterface;
use Fef\CustomShipping\Helper\Data as CustomHelper;
use Fef\CustomShipping\Model\FefTokenFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const STR_VOUCHER_URL = 'vouchers/';

    private $request;
    private $helper;
    private $modelFefTokenFactory;
    public $storemanager;
    private $customerFactory;
    private $customerRepository;
    private $customerSession;
    private $resourceConnection;
    private $eavAttribute;
    private $indexerInterfaceFactory;
    public $encryptInterface;
    public $customerInterface;
    private $resultFactory;
    private $modelCart;
    private $checkoutSession;

    public function __construct(
        Http $request,
        CustomHelper $helper,
        FefTokenFactory $modelFefTokenFactory,
        StoreManagerInterface $storemanager,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        ResourceConnection $resourceConnection,
        Attribute $eavAttribute,
        IndexerInterfaceFactory $indexerInterfaceFactory,
        EncryptorInterface $encryptInterface,
        CustomerInterfaceFactory $customerInterface,
        ResultFactory $resultFactory,
        Cart $modelCart,
        CheckoutSession $checkoutSession

    ) {
        $this->request = $request;
        $this->helper = $helper;
        $this->modelFefTokenFactory = $modelFefTokenFactory;
        $this->storemanager = $storemanager;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->resourceConnection = $resourceConnection;
        $this->eavAttribute = $eavAttribute;
        $this->indexerInterfaceFactory = $indexerInterfaceFactory;
        $this->encryptInterface = $encryptInterface;
        $this->customerInterface = $customerInterface;
        $this->resultFactory = $resultFactory;
        $this->modelCart = $modelCart;
        $this->checkoutSession = $checkoutSession;
    }
    
    private function getDetailProduct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productObject = $objectManager->create('Magento\Catalog\Model\Product');
        $itemsVisible = $this->modelCart->getQuote()->getItems();

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        $details = [];
        $configurables = array();
        if(count($itemsVisible)>0){
            foreach($itemsVisible as $item){
                $productId = $item->getProduct()->getId();
                // $logger->info("productId : $productId, sku : ".$item->getProduct()->getSku());
    
                if ($item->getHasChildren() ) {
                    foreach ($item->getChildren() as $child) {
                        $item_s = $productObject->load($child->getProduct()->getId());
                        // $logger->info("childId : ".$child->getProduct()->getId().", sku : ".$child->getSku().", proseller ID : ".$item_s->getProsellerId());
                        $details[] = array(
                            "productId"=>$item_s->getProsellerId(),
                            "quantity"=>$item->getQty(),
                            "unitPrice"=>$child->getProduct()->getFinalPrice(),
                            "discount"=>$child->getProduct()->getDiscount() == NULL ? 0 : $child->getProduct()->getDiscount(),
                            "modifiers"=> array()
                        );
                        // $logger->info(print_r($details,true));
                    }
                }else{
                    $item_s = $productObject->load($productId);
                    $details[] = array(
                        "productId"=>$item_s->getProsellerId(),
                        "quantity"=>$item->getQty(),
                        "unitPrice"=>$item_s->getFinalPrice(),
                        "discount"=>$item_s->getDiscount() == NULL ? 0 : $item_s->getDiscount(),
                        "modifiers"=> array()
                    );    
                }
    
                            
            }
        }
        
        return $details;
    }


    public function applyVoucher($voucherId)
    {
        // $this->removeUsedVoucherFromList($this->customerSession->getId());
        $result = $this->calculateOrder($voucherId);
        $this->checkoutSession->getQuote()->collectTotals();
        return $result;
        
    }

    public function unapplyVoucher($voucherId)
    {
        $result = $this->calculateOrder($voucherId,0);
        $this->checkoutSession->getQuote()->collectTotals();
        return $result;
    }

    public function getCustomerVouchers()
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("getCustomerVouchers");

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $voucherPointFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointFactory');

        $voucherPoint = $voucherPointFactory->create()->load($this->customerSession->getId(), 'customer_id');

        return array(
            "vouchers"=> json_decode($voucherPoint->getMemberVoucherList(),true),
            "point"=> json_decode($voucherPoint->getMemberPoint(),true),
        );
    }

    public function calculateOrder($voucherId, $pointsParams = null, $details = array())
    {

        // app/code/Zoku/Rewards/Model/Calculation.php => for calculation refference 
        

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("calculateOrder : $voucherId || $pointsParams");

        // $baseUrl = $this->helper->getConfig("carriers/custom/base_url");
        $outletId = $this->helper->getConfig("carriers/custom/outlet_id");
        $calculateUrl = $this->helper->getUrl("cal");
        
        if($this->customerSession->getId()){
            $customer = $this->customerRepository->getById($this->customerSession->getId());
            $prosellerMemberId = $customer->getCustomAttribute('proseller_member_id')->getValue();
        }else{
            $prosellerMemberId = "3237c0f7-c31a-4f71-bdaf-8c9917772d03";
        }
        

        

        // $logger->info("outletId : $outletId || prosellerMemberId : ".$prosellerMemberId->getValue());
        if(empty($details)){
            $details = $this->getDetailProduct();
        }

        $voucherList = $this->getCustomerVouchers();

        // $logger->info("voucherList : ".print_r($voucherList,true));

        $vouchersParams = array();
        if(!empty($voucherList['vouchers'])){
            foreach ($voucherList['vouchers'] as $valVouc) {
                if($voucherId == $valVouc["id"]){
                    $vouchersParams[0] = array(
                        "id" => $valVouc["id"],
                        "serialNumber" => $valVouc["serialNumber"]
                    );
                }
            }
        }

        $points = 0;
        if(!empty($voucherList['point'])){
            // $logger->info(print_r($voucherList['point'],true));
            $points = $voucherList['point']["balance"];
        }

        if($pointsParams!=null || $pointsParams>=0){
            $points = (int)$pointsParams;
        }

        $loyalty = array();

        if(!empty($vouchersParams)){
            $loyalty["vouchers"] = $vouchersParams;
        }

        if($points >= 0){
            $loyalty["points"] = $points;
        }


        // $loyalty = array(
        //     "vouchers" => $vouchersParams,
        //     "points" => $points
        // );

        // $logger->info("loyalty : ".print_r($loyalty,true));

        $hitParams = array(
            "outletId" => $outletId,
            "customerId" => $prosellerMemberId,
            "details" => $details,
            "loyalty" => $loyalty
        );
        
        $applyResponse = $this->helper->setCurl($calculateUrl,"POST",$hitParams,1);

        

        $arrCalculateResp = json_decode($applyResponse,true);

        // $logger->info("url : ".$calculateUrl);
        // $logger->info("hitParams : ".json_encode($hitParams));
        // $logger->info("hitParams : ".print_r($hitParams,true));
        // $logger->info("applyResponse : ".$applyResponse);
        // $logger->info("arrCalculateResp : ".print_r($arrCalculateResp,true));
        

        if(isset($arrCalculateResp) && $arrCalculateResp["status"] == "success"){


            $this->saveToTempTable($arrCalculateResp["data"]);
            
            if(isset($arrCalculateResp["data"]["rejectedVouchers"])){
                return array(
                    "success"=>"false",
                    "message" => $arrCalculateResp["data"]["rejectedVouchers"][0]["reason"]
                );
            }else{
                $this->updateQuoteData($arrCalculateResp["data"]);
                return array(
                    "success"=>"true",
                    "message" => $arrCalculateResp["data"]
                );
            }
        }else{
            if(isset($arrCalculateResp["message"])){
                return array(
                    "success"=>"false",
                    "message" => $arrCalculateResp["message"]
                );
            }

            if(isset($arrCalculateResp["data"]["message"])){
                return array(
                    "success"=>"false",
                    "message" => $arrCalculateResp["data"]["message"]
                );
            }

            return array(
                "success"=>"false",
                "message" => "Fail use selected voucher"
            );
        }
    }

    public function updateQuoteData($data)
    {
        /**
         * QUOTE LEVEL
         * grand_total
         * base_grand_total
         * subtotal
         * base_subtotal
         * subtotal_with_discount
         * base_subtotal_with_discount
         * 
         * QUOTE ITEM LEVEL
         * discount_percent
         * discount_amount
         * base_discount_amount
         * row_total
         * base_row_total
         */

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        // $logger->info("applyResponse : ".print_r($data,true));

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // $checkoutSession = $objectManager->create('Magento\Checkout\Model\Session');

        // $quote = $checkoutSession->getQuote();
        // $quote = $this->checkoutSession->getQuote();
        // $quoteItems = $quote->getAllItems();

        $quoteRepository = $objectManager->get('\Magento\Quote\Api\CartRepositoryInterface');
        $totalRepository = $objectManager->get('\Magento\Quote\Model\Quote\Address\Total');
        
        $qid = $this->checkoutSession->getQuoteId();
        $quote = $quoteRepository->get($qid);
        $quoteItems = $quote->getAllItems();
        $countItems = count($quoteItems);

        $x = 0;
        $totalDiscPoint = 0;
        foreach ($data["details"] as $details) {
            if(isset($details["discountType"]) && $details["discountType"] == "POINTS"){
                $totalDiscPoint += $details["totalDiscAmount"];
            }
        }
        

        if(isset($data["details"][$x])){            
            $totalTax = 0;
            foreach ($quoteItems as $_quoteItem) {
                $quoteItem = $quote->getItemById($_quoteItem->getItemId());
                $totalTax += $quoteItem->getTaxAmount();

                if ($quoteItem->getHasChildren() ) {
                    // $quoteItem->setRowTotal($data["totalNettAmount"]);
                    $quoteItem->setRowTotal(($data["nettAmount"] - $quoteItem->getTaxAmount()) < 0 ? 0 : $data["nettAmount"] - $quoteItem->getTaxAmount());
                    $quoteItem->setRowTotalInclTax($data["totalNettAmount"]);
                    // $quoteItem->setBaseRowTotal($data["totalNettAmount"] - $quoteItem->getTaxAmount());
                    $quoteItem->setBaseRowTotal(($data["nettAmount"] - $quoteItem->getTaxAmount() ) < 0 ? 0 : $data["nettAmount"] - $quoteItem->getTaxAmount());
                    $quoteItem->setBaseRowTotalInclTax($data["totalNettAmount"]);
                    if(isset($data["pointsDiscount"])){
                        $quoteItem->setDiscountAmount($data["totalDiscAmount"] + $data["pointsDiscount"]["nettAmount"]);
                        $quoteItem->setBaseDiscountAmount($data["totalDiscAmount"] + $data["pointsDiscount"]["nettAmount"]);
                    }else{
                        $quoteItem->setDiscountAmount($data["totalDiscountAmount"]);
                        $quoteItem->setBaseDiscountAmount($data["totalDiscountAmount"]);
                    }

                    // $quoteItem->setRowTotalWithDiscount($data["totalGrossAmount"]);
                    $quoteItem->setRowTotalWithDiscount($data["nettAmount"] + $data["totalDiscAmount"]);

                }else{
                    // $logger->info("_quoteItem->getItemId : ".$_quoteItem->getItemId());

                    // $quoteItem->setRowTotal($data["details"][$x]["grossAmount"] - $data["details"][$x]["lineDiscAmount"] - $quoteItem->getTaxAmount());
                    $quoteItem->setRowTotal(($data["details"][$x]["nettAmount"] - $quoteItem->getTaxAmount()) < 0 ? 0 : $data["details"][$x]["nettAmount"] - $quoteItem->getTaxAmount());

                    // $quoteItem->setRowTotalInclTax($data["details"][$x]["grossAmount"] - $data["details"][$x]["lineDiscAmount"]);
                    $quoteItem->setRowTotalInclTax($data["details"][$x]["nettAmount"]);

                    // $quoteItem->setBaseRowTotal($data["details"][$x]["grossAmount"] - $data["details"][$x]["lineDiscAmount"] - $quoteItem->getTaxAmount());
                    $quoteItem->setBaseRowTotal(($data["details"][$x]["nettAmount"] - $quoteItem->getTaxAmount() ) < 0 ? 0 : $data["details"][$x]["nettAmount"] - $quoteItem->getTaxAmount());

                    // $quoteItem->setBaseRowTotalInclTax($data["details"][$x]["grossAmount"] - $data["details"][$x]["lineDiscAmount"]);
                    $quoteItem->setBaseRowTotalInclTax($data["details"][$x]["nettAmount"]);

                    if(isset($data["details"][$x]["pointsDiscount"])){
                        $quoteItem->setDiscountAmount($data["details"][$x]["totalDiscAmount"] + $data["details"][$x]["pointsDiscount"]["nettAmount"]);
                        $quoteItem->setBaseDiscountAmount($data["details"][$x]["totalDiscAmount"]  + $data["details"][$x]["pointsDiscount"]["nettAmount"]);
                    }else{
                        $quoteItem->setDiscountAmount($data["details"][$x]["totalDiscAmount"]);
                        $quoteItem->setBaseDiscountAmount($data["details"][$x]["totalDiscAmount"]);
                    }

                    
                    // $quoteItem->setDiscountAmount($data["details"][$x]["totalDiscAmount"] + $totalDiscPoint);

                    
                    // $quoteItem->setBaseDiscountAmount($data["details"][$x]["totalDiscAmount"] + $totalDiscPoint);

                    // TEMPORARY
                    // $quoteItem->setDiscountAmount($quoteItem->getDiscountAmount() + $data["details"][$x]["totalDiscAmount"]);
                    // $quoteItem->setBaseDiscountAmount($quoteItem->getDiscountAmount() + $data["details"][$x]["totalDiscAmount"]);

                    // $quoteItem->setRowTotalWithDiscount($data["details"][$x]["nettAmount"] + $data["details"][$x]["lineDiscAmount"]);
                    $quoteItem->setRowTotalWithDiscount($data["details"][$x]["nettAmount"] + $data["details"][$x]["totalDiscAmount"]);
                    $quoteItem->save();
                    $x++;
                }
                
                
            }
        }


        
        $shippingAddress = $quote->getShippingAddress();

        $logger->info("after update quote data : $qid : ".$shippingAddress->getDiscountDescription());

        $shippingAmount = $shippingAddress->getShippingInclTax();
        $shippingAddress->setBaseSubtotal($data["totalNettAmount"] - $totalTax);
        $shippingAddress->setBaseSubtotalTotalInclTax($data["totalNettAmount"] - $totalTax);
        $shippingAddress->setSubtotal($data["totalNettAmount"] - $totalTax);
        $shippingAddress->setSubtotalInclTax($data["totalNettAmount"]);
        $shippingAddress->setBaseSubtotalInclTax($data["totalNettAmount"]);
        $shippingAddress->setSubtotalWithDiscount($data["totalNettAmount"]);
        $shippingAddress->setBaseSubtotalWithDiscount($data["totalNettAmount"] - $data["totalDiscountAmount"]);
        $shippingAddress->setDiscountAmount($data["totalDiscountAmount"]);
        $shippingAddress->setBaseDiscountAmount($data["totalDiscountAmount"]);
        $shippingAddress->setBaseGrandTotal($data["totalNettAmount"] + $shippingAmount);
        $shippingAddress->setGrandTotal($data["totalNettAmount"] + $shippingAmount);

        $shippingAddress->setDiscountTaxCompensationAmount(0);
        $shippingAddress->setBaseDiscountTaxCompensationAmount(0);
        $shippingAddress->setShippingDiscountTaxCompensationAmount(0);
        $shippingAddress->setBaseShippingDiscountTaxCompensationAmnt(0);

        // $shippingAddress->setDiscountDescription("Test");
        $shippingAddress->save();

        

    }

    private function saveToTempTable($respData)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $CalculateTempFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\CalculateTempFactory');
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');

        $CalculateTemp = $CalculateTempFactory->create();
        $quoteId = $this->checkoutSession->getQuote()->getId();
        $customerId = $this->customerSession->getId();
        
        // $CalculateTempCollection = $CalculateTemp
        //     ->getCollection()
        //     ->addFieldToFilter('customer_id', $customerId)
        //     ->addFieldToFilter('quote_id', $quoteId);
        // $dataCollection = $CalculateTempCollection->getData();

        $dataCollection = $this->getTempTableDataByCustomerAndQuote($customerId, $quoteId);
        

        if(count($dataCollection) > 0){
            foreach ($dataCollection as $key => $collection) {
                $id = $collection["id"];
                $postUpdate = $CalculateTemp->load($id);
                $this->saveData($respData,$postUpdate);
            }
        }else{
            $this->saveData($respData,$CalculateTemp);
        }

        $voucherPointUsed = $voucherPointUsedFactory->create();
        // $voucherPointUsedCollection = $voucherPointUsed->getCollection()
        // ->addFieldToFilter('customer_id', $customerId)
        // ->addFieldToFilter('quote_id', $quoteId);
        // $datavoucherPointUsedCollection = $voucherPointUsedCollection->getData();

        $datavoucherPointUsedCollection = $this->getUsedVoucherPointData($customerId, $quoteId);
        
        
        if(count($dataCollection) > 0){
            foreach ($datavoucherPointUsedCollection as $key => $voucherPointUsedCollection) {
                $id = $voucherPointUsedCollection["id"];
                $postUpdate = $voucherPointUsed->load($id);
                if(isset($respData["rejectedVouchers"])){
                    $postUpdate->setVoucherValidity($respData["rejectedVouchers"][0]["reason"]);
                }else{
                    $postUpdate->setVoucherValidity("Valid Voucher");
                }
                $postUpdate->save();
            }
        }
    }

    private function saveData($respData,$CalculateTemp){
        $CalculateTemp->setCustomerId($this->customerSession->getId());
        $CalculateTemp->setQuoteId($this->checkoutSession->getQuote()->getId());
        $CalculateTemp->setCalculateResult(json_encode($respData));
        $CalculateTemp->save();
    }

    public function removeUsedVoucherFromList($customerId,$qid=null)
    {
        
        if($qid==null){
            $qid = $this->checkoutSession->getQuoteId();
        }

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $voucherPointFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointFactory');
        // $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');

        $voucherPoint = $voucherPointFactory->create();
        
        $voucherPointCollection = $voucherPoint
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId);
        $dataCollection = $voucherPointCollection->getData();

        
        // $voucherPointUsed = $voucherPointUsedFactory->create();
        // $voucherPointUsedCollection = $voucherPointUsed
        //     ->getCollection()
        //     ->addFieldToFilter('customer_id',array('eq' => $customerId))
        //     ->addFieldToFilter('quote_id',array('eq' => $qid));
        // $dataUsedCollection = $voucherPointUsedCollection->getData();

        $dataUsedCollection = $this->getUsedVoucherPointData($customerId,$qid);

        $usedVoucher = "";
        if(count($dataUsedCollection)>0){
            $usedVoucher = $dataUsedCollection[0]["used_voucher"];
        }
        if(count($dataCollection) > 0){
            $idxRemove = 0;
            foreach ($dataCollection as $key => $collection) {
                $voucherList = json_decode($collection["member_voucher_list"],true);
                foreach ($voucherList as $keys => $value) {
                    if($voucherList[$keys]["id"] == $usedVoucher){
                        $idxRemove = $keys;
                    }
                }
                unset($voucherList[$idxRemove]);
                $dataCollection[$key]["member_voucher_list"] = json_encode($voucherList);

                $id = $dataCollection[$key]["id"];
                $postUpdate = $voucherPoint->load($id);
                $postUpdate->setMemberVoucherList($dataCollection[$key]["member_voucher_list"]);
                $postUpdate->save();
            }
        }        
        // $logger->info("dataCollection end  : ".print_r($dataCollection,true));
    }

    public function clearUsedVoucherCustomer($customerId){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');
        $voucherPointUsed = $voucherPointUsedFactory->create();
        $voucherPointUsedCollection = $voucherPointUsed
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId);
        $dataCollection = $voucherPointUsedCollection->getData();
        if(count($dataCollection) > 0){
            foreach ($dataCollection as $key => $collection) {
                $id = $collection["id"];
                $postUpdate = $voucherPointUsed->load($id);
                $postUpdate->setUsedVoucher("");
                $postUpdate->save();
            }
        }
    }

    public function addZokuPoint($customerId, $pointAmount)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/customer-login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $zokuRewardsProvider = $objectManager->get('\Zoku\Rewards\Api\RewardsProviderInterface');
        $expirationArgFactory = $objectManager->get('\Zoku\Rewards\Api\Data\ExpirationArgumentsInterfaceFactory');
        $resourceConnection  = $objectManager->get('\Magento\Framework\App\ResourceConnection');

        $connection = $resourceConnection->getConnection();

        $table = $connection->getTableName('zoku_rewards_rewards');

        $querySelect = "SELECT * FROM `" . $table . "` WHERE customer_id = $customerId order by id DESC LIMIT 1";

        // $logger->info("querySelect : $querySelect");

        $resultData = $connection->fetchAll($querySelect);

        // $logger->info(print_r($resultData,true));

        if(count($resultData) == 0){
            $comment = "initial point from proseller";
            $expire = $expirationArgFactory->create();
            $expire->setIsExpire(0);
            $zokuRewardsProvider->addPoints($pointAmount, $customerId, 'System Point Change', $comment, $expire);
        } else{
            
            $savedAmount = $pointAmount;
            $comment = "updated point from proseller";

            // $logger->info("comment : ".$comment." || $savedAmount");

            if($savedAmount != NULL && $resultData[0]['points_left'] != NULL){
                try {
                    if($pointAmount > $resultData[0]['points_left']){
                        $expire = $expirationArgFactory->create();
                        $expire->setIsExpire(0);
                        $savedAmount = $pointAmount - (int) $resultData[0]['points_left'];
                        $zokuRewardsProvider->addPoints($savedAmount, $customerId, 'System Point Change', $comment, $expire);
                    }
                    if($pointAmount < $resultData[0]['points_left']){
                        $savedAmount = (int)$resultData[0]['points_left'] - $pointAmount;
                        $zokuRewardsProvider->deductPoints($savedAmount, $customerId, 'System Point Change', $comment);
                    }
                } catch (\Exception $ex) {
                    $logger->info("Exception : ".$ex->getMessage());
                }
                $logger->info($comment." to $customerId with amount $savedAmount");
            }
        }
    }

    public function getTempTableDataByCustomerAndQuote($customerId, $quoteId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $CalculateTempFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\CalculateTempFactory');
        $CalculateTempCollection = $CalculateTempFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('quote_id', $quoteId);
        $dataCollection = $CalculateTempCollection->getData();
        return $dataCollection;
    }

    public function getTempTableDataByCustomer($customerId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $CalculateTempFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\CalculateTempFactory');
        $CalculateTempCollection = $CalculateTempFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)->getLastItem();
        $dataCollection = $CalculateTempCollection->getData();
        return $dataCollection;
    }

    public function getUsedVoucherPointData($customerId, $quoteId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');
        $voucherPointUsedCollection = $voucherPointUsedFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('quote_id', $quoteId);
        $datavoucherPointUsedCollection = $voucherPointUsedCollection->getData();
        return $datavoucherPointUsedCollection;
    }


}
