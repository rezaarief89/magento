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
namespace Fef\CustomerSso\Controller\Customer;

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
                    /**
                     * IF TOKEN IS VALID, THEN : 
                     *  - UPDATE EXISTING TOKEN
                     *  - SET EMAIL AS LOGGED IN CUSTOMER
                     *  - REDIRECT TO MY ACCOUNT PAGE
                     */

                    /*

                    // DIsable check and update token because of use long live token

                    $tokenCollection = $this->helper->getTokenCollection();
                    $dataCollection = $tokenCollection->getData();
                    foreach ($dataCollection as $key => $collection) {
                        $id = $collection["id"];
                        // $postUpdate = $modelFefToken->load($id);
                        // $postUpdate->setToken($respOtpArray["data"]["token"]);
                        // $postUpdate->setRefreshToken($respOtpArray["data"]["refreshToken"]);
                        // $postUpdate->save();

                        $id = $collection["id"];
                        $expiry = $collection["expiry"];
                        $expiry = str_replace("T"," ",$expiry);
                        $expiry = str_replace("Z","",$expiry);
                        $expiryStrTime = strtotime($expiry);

                        if(time() > $expiryStrTime){
                            //  call API refresh token
                            $paramToken = array(
                                "refreshToken" => $collection["refresh_token"]
                            );
                            $authReq = $this->callAuthApi("refresh",$paramToken);
                            $authReqArray = json_decode($authReq,true);
                            $logger->info(print_r($authReqArray,true));

                            if(isset($authReqArray["status"]) && $authReqArray["status"]=="success"){
                                $expiryData = $authReqArray["data"]["expiry"];
                                if(!empty($expiryData)){
                                    $token = $authReqArray["data"]["token"];
                                    $postUpdate = $modelFefToken->load($id);
                                    $postUpdate->setToken($token);
                                    $postUpdate->setRefreshToken($authReqArray["data"]["refreshToken"]);
                                    $postUpdate->setExpiry($authReqArray["data"]["expiry"]);
                                    $postUpdate->save();
                                }
                            }
                        }
                    }
                    */
                    $customerRepo = $this->customerRepository->get($email); 
                    $customer = $this->customerFactory->create()->load($customerRepo->getId());
                    $this->customerSession->setCustomerAsLoggedIn($customer);

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
                        // $resultJson->setData([
                        //     "message" => "Validation process failed", 
                        //     "success" => false
                        // ]);
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

                    $logger->info("===================== checkProsellerByEmail =======================");
                    $logger->info(print_r($arrCheckProseller,true));

                    
                    if(isset($arrCheckProseller["status"]) && $arrCheckProseller["status"]=="fail"){

                        $arrResult = $this->createMembershipFromLogin($email);
                        $logger->info("===================== createMembershipFromLogin =======================");
                        $logger->info(print_r($arrResult,true));

                        if(isset($arrResult["success"]) && $arrResult["success"]==true){
                            $resultJson = $this->customerHelper->sendOtp($email);
                            $logger->info("===================== sendOtp =======================");
                        } else {
                            $resultJson->setData([
                                "message" => $arrResult["message"],
                                "success" => $arrResult["success"]
                            ]);
                        }
                    }else{
                        if(isset($arrCheckProseller["status"]) && $arrCheckProseller["status"]=="success"){
                            $arrAttribute = $this->customerHelper->getAttributeList($arrCheckProseller["data"]);
                            $this->customerHelper->updateCustomerAttribute($arrAttribute,$email);

                            $resultJson = $this->customerHelper->sendOtp($email);
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
                    // $logger->info(print_r($respArray,true));
                    
                    if(isset($respArray["status"]) && $respArray["status"]=="success"){
                        
                        /**
                         * IF EMAIL EXIST IN PROSELLER, THEN :
                         * - CREATE MAGENTO CUSTOMER
                         * - SEND OTP TO EMAIL
                         */

                        // CREATE CUSTOMER IN MAGENTO
                        $this->customerHelper->createCustomer($respArray["data"]);                      

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
            $logger->info("cattrPhonenumberValue : ".strpos($cattrPhonenumberValue->getValue(),"+"));

            if(strpos($cattrPhonenumberValue->getValue(),"+")==0){
                $logger->info("strpos");
                $phoneNumber = $cattrPhonenumberValue->getValue();
            }else{
                $logger->info("!strpos");
                $phoneNumber = "+65".$cattrPhonenumberValue->getValue();
            }
        }else{
            $phoneNumber = "+6500000000";
        }
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
        $logger->info(print_r($respCreate,true));

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
    
}