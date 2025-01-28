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
namespace Fef\CustomVoucherPoint\Controller\Customer;

use Magento\Framework\Controller\ResultFactory;
use Fef\CustomerSso\Helper\Data as CustomerHelper;
use Fef\CustomShipping\Helper\Data;
use Fef\CustomShipping\Model\FefTokenFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;

class SyncEmail extends \Magento\Framework\App\Action\Action
{

    const STR_MEMBERSHIP_URL = 'memberships/';

    /**
     * @var \Magento\Framework\App\Action\Contex
     */
    private $context;
    private $request;
    private $helper;
    private $modelFefTokenFactory;
    private $customerFactory;
    private $customerRepository;
    private $customerSession;
    private $customerHelper;


    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;

    private $cookieMetadataFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        Context $context,
        Http $request,
        Data $helper,
        FefTokenFactory $modelFefTokenFactory,
        CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        CustomerHelper $customerHelper
    ) {
        parent::__construct($context);
        $this->context = $context;
        $this->request = $request;
        $this->helper = $helper;
        $this->modelFefTokenFactory = $modelFefTokenFactory;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->customerHelper = $customerHelper;
    }
    
    /**
     * @return json
     */
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/customer-login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $modelFefToken = $this->modelFefTokenFactory->create();

        try {

            $email = $this->request->getParam('email');
            $otp = $this->request->getParam('otp');

            if($otp != "0"){
                /**
                 * LAST STEP, VALIDATE OTP THAT HAS INPUT IN FRONTEND
                 */
                $respOtpArray = $this->customerHelper->validateOtp($email,$otp);
                // $logger->info(print_r($respOtpArray,true));
                $logger->info("===================== validateOtp =======================");

                if(isset($respOtpArray["status"]) && $respOtpArray["status"]=="success"){
                    $customerRepo = $this->customerRepository->get($email); 
                    $customer = $this->customerFactory->create()->load($customerRepo->getId());
                    $this->customerSession->setCustomerAsLoggedIn($customer);
                    $this->customerSession->setUsername($email);

                    if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                        $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                        $metadata->setPath('/');
                        $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
                    }   

                    $resultJson->setData([
                        "message" => "OTP has been successfully validated", 
                        "success" => true
                    ]);
                    
                } else {
                    if(isset($respOtpArray["status"]) && $respOtpArray["status"]=="fail"){
                        if(isset($respOtpArray["data"])){
                            foreach ($respOtpArray["data"] as $key => $value) {
                                $arrResult = [
                                    "message" => $respOtpArray["data"][$key],
                                    "success" => false
                                ];
                            }
                        }
                    } else {
                        $resultJson->setData([
                            "message" => $respOtpArray["data"]["message"], 
                            "success" => true
                        ]);
                    }
                }
            }else{

                /**
                 * FIRST CHECK IF EMAIL IS EXIST OR NOT IN MAGENTO 
                 * IF EMAIL EXIST IN MAGENTO THEN ADD PROSELLER MEMBERSHIP AND DO REQUEST OTP PROCESS
                 * IF EMAIL NOT EXIST IN MAGENTO THEN CHECK EMAIL IN PROSELLER
                 */

                $customer = $this->customerHelper->getCustomerByEmail($email);

                if($customer->getId() != null){

                    $arrCheckProseller = $this->customerHelper->checkProsellerByEmail($email);

                    $logger->info("===================== checkProsellerByEmail override =======================");
                    $logger->info(print_r($arrCheckProseller,true));

                    
                    if(isset($arrCheckProseller["status"]) && $arrCheckProseller["status"]=="fail"){

                        $arrResult = $this->createMembershipFromLogin($email);
                        $logger->info("===================== createMembershipFromLogin override =======================");
                        $logger->info(print_r($arrResult,true));

                        if(isset($arrResult["success"]) && $arrResult["success"]==true){
                            if(isset($arrResult["data"])){
                                $this->setVoucherAndPoint($arrResult["data"],$customer->getId());
                            }
                            $resultJson = $this->customerHelper->sendOtp($email);
                            $logger->info("===================== sendOtp override=======================");
                        } else {
                            $resultJson->setData([
                                "message" => $arrResult["message"],
                                "success" => $arrResult["success"]
                            ]);
                        }
                    }else{
                        if(isset($arrCheckProseller["status"]) && $arrCheckProseller["status"]=="success"){
                            $this->setVoucherAndPoint($arrCheckProseller["data"],$customer->getId());
                            $logger->info("===================== setVoucherAndPoint =======================");
                            $arrAttribute = $this->customerHelper->getAttributeList($arrCheckProseller["data"]);
                            $this->customerHelper->updateCustomerAttribute($arrAttribute,$email);
                            $logger->info("===================== updateCustomerAttribute =======================");
                            $resultJson = $this->customerHelper->sendOtp($email);
                            $logger->info("===================== sendOtpResponse =======================");
                            // $logger->info(print_r($resultJson,true));
                            $logger->info("===================== resultJson OTP =======================");
                            // $resultJson->setData([
                            //     "message" => "success",
                            //     "success" => true
                            // ]);
                        }else{
                            foreach ($arrCheckProseller as $arrCheckProsellerKey => $arrCheckProsellerValue) {
                                $resultJson->setData([
                                    "message" => $arrCheckProsellerValue,
                                    "success" => false
                                ]);
                            }
                            
                        }
                    }
                } else {

                    /**
                     * EMAIL IS EXIST OR NOT IN PROSELLER
                     * IF EMAIL EXIST IN PROSELLER THEN DO REQUEST OTP PROCESS
                     */

                    $respArray = $this->customerHelper->checkProsellerByEmail($email);
                    $logger->info("===================== checkProsellerByEmail ELSE =======================");
                    $logger->info(print_r($respArray,true));
                    
                    if(isset($respArray["status"]) && $respArray["status"]=="success"){
                        
                        /**
                         * IF EMAIL EXIST IN PROSELLER, THEN :
                         * - CREATE MAGENTO CUSTOMER
                         * - SEND OTP TO EMAIL
                         */

                        // CREATE CUSTOMER IN MAGENTO
                        $this->customerHelper->createCustomer($respArray["data"]);

                        $logger->info("===================== CREATE CUSTOMER IN MAGENTO =======================");
                                      

                        $resultJson = $this->customerHelper->sendOtp($email);

                    }else{
                        /**
                         * IF EMAIL NOT EXIST IN PROSELLER, THEN :
                         * Return error email not exists
                         */
                        if(isset($respArray["data"]["message"])){
                            $resultJson->setData([
                                "message" => $respArray["data"]["message"],
                                "success" => false
                            ]);
                        }else{
                            foreach ($respArray as $respArrayKey => $respArrayValue) {
                                $resultJson->setData([
                                    "message" => $respArrayValue,
                                    "success" => false
                                ]);
                            }
                        }
                    }
                }
            }
            
        } catch (\Exception $ex) {
            $resultJson->setData([
                "message" => ($ex->getMessage()), 
                "success" => false
            ]);
        }
        

        return $resultJson;
    }

    public function createMembershipFromLogin($postEmail)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/customer-login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        $websiteID = $this->customerHelper->storemanager->getStore()->getWebsiteId();
        $customer = $this->customerFactory->create()->setWebsiteId($websiteID)->loadByEmail($postEmail);
        
        $customerModel = $this->customerRepository->getById($customer->getId());

        $cattrPhonenumberValue = $customerModel->getCustomAttribute('phone_number');
        

        

        if($cattrPhonenumberValue){
            $logger->info("cattrPhonenumberValue : ".$cattrPhonenumberValue->getValue());
            $logger->info("strpos : ".strpos($cattrPhonenumberValue->getValue(),"+"));
            if(strpos($cattrPhonenumberValue->getValue(),"+")=== TRUE){
                $phoneNumber = $cattrPhonenumberValue->getValue();
            }else{
                $phoneNumber = "+65".$cattrPhonenumberValue->getValue();
                $logger->info("cattrPhonenumberValue : ".$cattrPhonenumberValue->getValue());
            }
        }else{
            $phoneNumber = "+6500000000";
        }

        $logger->info("phoneNumber : ".$phoneNumber);
        // $phoneNumber = $cattrPhonenumberValue ? "+65".$cattrPhonenumberValue->getValue() : null;
        
        $customerName = $customer->getFirstname();
        if($customer->getMiddlename()!=NULL){
            $customerName.=" ".$customer->getMiddlename();
        }
        if($customer->getLastname()!=NULL){
            $customerName.=" ".$customer->getLastname();
        }

        $baseUrl = $this->helper->getConfig("carriers/custom/base_url");
        $membershipUrl = $baseUrl.self::STR_MEMBERSHIP_URL;
        $creatdParams = array(
            "name" => $customerName,
            "email" => $postEmail,
            "phoneNumber" => $phoneNumber
        );
        $logger->info(print_r($creatdParams,true));
        $createResponse = $this->helper->setCurl(
            $membershipUrl,
            "POST",
            $creatdParams,
            1
        );
        $respCreate = json_decode($createResponse,1);
        $logger->info("===================== createMembershipFromLogin =======================");
        // $logger->info(print_r($respCreate,true));

        $arrResult = [];
        if(isset($respCreate["status"]) && $respCreate["status"]=="success"){
            
            $arrAttribute = $this->customerHelper->getAttributeList($respCreate["data"]);
            $this->customerHelper->updateCustomerAttribute($arrAttribute,$postEmail);

            $logger->info("===================== updateCustomerAttribute =======================");
            $arrResult = [
                "message" => "Customer created successfully to proseller",
                "success" => true
            ];
        } else {
            if(isset($respCreate["status"]) && $respCreate["status"]=="fail"){
                if(isset($respCreate["data"])){
                    foreach ($respCreate["data"] as $key => $value) {
                        $arrResult = [
                            "message" => $respCreate["data"][$key],
                            "success" => false
                        ];
                    }
                }
            }else{
                $arrResult = [
                    "message" => $respCreate["message"],
                    "success" => false
                ];
            }
        }
        return $arrResult;
    }
    
    private function setVoucherAndPoint($respData,$customerId)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/customer-login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $voucherPointFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointFactory');

        $voucherPoint = $voucherPointFactory->create();
        
        $voucherPointCollection = $voucherPoint
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId);
            $dataCollection = $voucherPointCollection->getData();

        if(count($dataCollection) > 0){
            foreach ($dataCollection as $key => $collection) {
                $id = $collection["id"];
                $postUpdate = $voucherPoint->load($id);
                $this->saveData($respData,$postUpdate,$customerId);
            }
        }else{
            $this->saveData($respData,$voucherPoint,$customerId);
        }
        $this->clearUsedVoucher($customerId);
    }

    private function saveData($respData,$voucherPoint,$customerId)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/customer-login.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $voucherResp = [];
        if(isset($respData["vouchers"])){
            $logger->info(print_r($respData["vouchers"],true));
            foreach ($respData["vouchers"] as $keyV => $valV) {
                if(isset($valV["id"])){
                    // $logger->info($valV["id"]." || ".$valV["voucherCategory"]);
                    if($valV["voucherCategory"]=="DISCOUNT"){
                        $voucherDetail = [
                            "id" => $valV["id"],
                            "name" => $valV["name"],
                            "type" => $valV["type"],
                            "value" => isset($valV["value"]) ? $valV["value"] : 0,
                            "serialNumber" => $valV["serialNumber"]
                        ];
                        array_push($voucherResp,$voucherDetail);
                    }   
                }
            }
        }
        // $logger->info("voucherResp : ".print_r($voucherResp,true));
        $voucherPoint->setCustomerId($customerId);
        $voucherPoint->setProsellerMemberId(isset($respData["id"]) ? $respData["id"] : "");
        $voucherPoint->setMemberVoucherList(json_encode($voucherResp));
        $voucherPoint->setMemberPoint(isset($respData["points"]) ? json_encode($respData["points"]) : 0);
        $voucherPoint->save();


        if(isset($respData["points"]["balance"]) && $respData["points"]["balance"] != NULL && $respData["points"]["balance"] != ""){
            $this->addZokuPoint($customerId, $respData["points"]["balance"]);
        }
    }

    private function addZokuPoint($customerId, $pointAmount)
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
                    }if($pointAmount < $resultData[0]['points_left']){
                        $savedAmount = (int)$resultData[0]['points_left'] - $pointAmount;
                        $zokuRewardsProvider->deductPoints($savedAmount, $customerId, 'System Point Change', $comment);
                    }
                } catch (\Exception $ex) {
                    $logger->info("Exception : ".$ex->getMessage());
                }
                // $logger->info($comment." to $customerId with amount $savedAmount");
            }
        }
    }

    private function clearUsedVoucher($customerId){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helperData = $objectManager->get('\Fef\CustomVoucherPoint\Helper\Data');
        $helperData->clearUsedVoucherCustomer($customerId);

    }

    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    
    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated 100.1.0
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }

}