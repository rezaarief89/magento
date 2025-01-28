<?php

namespace Wow\Einvoice\Helper;

use Wow\Einvoice\Model\EinvoiceTokenFactory;
use Wow\Einvoice\Model\EinvoiceOauthFactory;
use Wow\Einvoice\Model\EinvoiceStatusFactory;
use Wow\Einvoice\Model\EinvoiceFactory;
use Wow\Einvoice\Helper\Configuration as ConfigHelper;
use Wow\Einvoice\Helper\Email as EmailHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Store\Model\ScopeInterface;

class Api extends \Magento\Framework\App\Helper\AbstractHelper
{

    const STORE_CODE = "coachmy_en";

    const SUBMIT_URL = "aspapi/myInvoisAPI/v2.0/submitDocument";

    const AUTH_URL = "authservice/v1.6/authentication";

    protected $tokenFactory;

    protected $oauthFactory;

    protected $storeManager;

    protected $configHelper;

    protected $emailHelper;

    protected $productRepository;
    
    protected $invInterface;

    protected $cmInterface;

    protected $statusFactory;

    protected $einvoiceFactory;

    protected $_netLineItemTotalExcludingTax = 0;

    public function __construct(
        EinvoiceTokenFactory $tokenFactory,
        EinvoiceOauthFactory $oauthFactory,
        StoreManagerInterface $storeManager,
        ConfigHelper $configHelper,
        ProductRepositoryInterface $productRepository,
        InvoiceInterface $invInterface,
        CreditmemoInterface $cmInterface,
        EinvoiceStatusFactory $statusFactory,
        EinvoiceFactory $einvoiceFactory,
        EmailHelper $emailHelper
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->oauthFactory = $oauthFactory;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->productRepository = $productRepository;
        $this->invInterface = $invInterface;
        $this->cmInterface = $cmInterface;
        $this->statusFactory = $statusFactory;
        $this->einvoiceFactory = $einvoiceFactory;
        $this->emailHelper = $emailHelper;
    }

    public function callApi($payload, $url, $header)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            $header
        );

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function callOauth()
    {
        // $this->configHelper->writeLog("======= callOauth API START =======");


        $postFields = array(
            'client_id'     => $this->configHelper->getClientId(),
            'client_secret' => $this->configHelper->getClientSecret(),
            'grant_type'    => 'client_credentials',
            'scope'         => $this->configHelper->getClientScope()
            
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->configHelper->getBaseUrl().self::AUTH_URL);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));

        $response = curl_exec($ch);

        $this->configHelper->writeLog("oauth url : ".$this->configHelper->getBaseUrl().self::AUTH_URL);
        $this->configHelper->writeLog("oauth payload : ".json_encode($postFields));
        $this->configHelper->writeLog("oauth response : $response");
        curl_close($ch);

        $responseDecode = json_decode($response,true);

        $error = "";
        if(isset($responseDecode['error'])){
            $error = $responseDecode['error'];
        } else if(isset($responseDecode['error_message'])){
            $error = $responseDecode['error_message'];
        }

        if($error != ""){
            $dataStatus = array(
                "payload_request" => json_encode($postFields),
                "request_type" => "oauth_token",
                "request_response" => $response,
                "request_increment_id" => "",
                "tried_times" => 0,
                "status" => 0,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            );
            $this->saveInvoiceStatus($dataStatus, "FAILED", NULL);
        }

        // $this->configHelper->writeLog("======= callOauth END =======");

        return json_decode($response, true);;
    }


    public function getOauthToken()
    {
        $model = $this->oauthFactory->create();
        $oAuthCollection = $model->getCollection();
        $storeId = $this->storeManager->getStore()->getId();
        $accessToken = "";

        // $this->configHelper->writeLog("======= getOauthToken START =======");

        if(count($oAuthCollection) != 0){
            
            $oauthModel = $oAuthCollection->getFirstItem();
            $accessToken = $oauthModel->getAccessToken();
            $expiredSec = $oauthModel->getExpiry();
            $createdAt = $oauthModel->getCreatedAt();
            $expiredAt = strtotime($createdAt) + $expiredSec;
            $dateNow = new \DateTime(date('Y-m-d H:i:s', strtotime('+8 hours')));
            $diff_in_seconds = $dateNow->getTimestamp() - $expiredAt;

            if ($diff_in_seconds > 0) {

                $this->configHelper->writeLog("======= oauth has been expired and will be regenerate =======");

                $authDecode = $this->callOauth();

                $model->load($oauthModel->getId());
                $model->setAccessToken(isset($authDecode["access_token"]) ? $authDecode["access_token"] : "");
                $model->setExpiry(isset($authDecode["expires_in"]) ? $authDecode["expires_in"] : "");
                $model->setTokenType(isset($authDecode["token_type"]) ? $authDecode["token_type"] : "");
                $model->setUpdatedAt(date("Y-m-d H:i:s"));
                $model->setCreatedAt(date("Y-m-d H:i:s"));
                $model->save();
                
                $accessToken = isset($authDecode["access_token"]) ? $authDecode["access_token"] : "";

                $this->configHelper->writeLog("======= regenerated oauth token : $accessToken =======");
            }
        }else{

            $this->configHelper->writeLog("======= oauth does not exist on system and will be generate via API =======");

            $authDecode = $this->callOauth();
            
            $dateNow = date("Y-m-d H:i:s");
            
            $dataToSave = array(
                "access_token" => isset($authDecode["access_token"]) ? $authDecode["access_token"] : "",
                "token_type" => isset($authDecode["token_type"]) ? $authDecode["token_type"] : "",
                "expiry" => isset($authDecode["expiry"]) ? $authDecode["expiry"] : "",
                "created_at" => $dateNow,
                "updated_at" => $dateNow
            );

            $model->setData($dataToSave)->save();
            $accessToken = $dataToSave['access_token'];

            $this->configHelper->writeLog("======= oauth generated API token : $accessToken =======");
        }
        
        // $this->configHelper->writeLog("======= getOauthToken END =======");
        return $accessToken;   
    }

    public function updateToken($tokenModel, $data)
    {
        $model = $this->tokenFactory->create();
        $model->load($tokenModel->getId());
        $model->setToken($data['token']);
        $model->setExpiry($data['expiry']);
        $model->setStatus($data['status']);
        $model->save();
    }

    public function getToken($retryFlag=0)
    {
        $tokenCollection = $this->tokenFactory->create()->getCollection();
        $storeId = $this->storeManager->getStore()->getId();

        $token = "";

        // $this->configHelper->writeLog("======= getToken START =======");

        if(count($tokenCollection) != 0){
            $tokenModel = $tokenCollection->getFirstItem();
            $token = $tokenModel->getToken(); 
            $expiredAt = new \DateTime($tokenModel->getExpiry());            
            // $dateNow = new \DateTime(date('Y-m-d H:i:s', strtotime('+8 hours')));
            $dateNow = new \DateTime(date('Y-m-d H:i:s'));
            
            $diff_in_seconds = $dateNow->getTimestamp() - $expiredAt->getTimestamp();

            // $this->configHelper->writeLog("diff_in_seconds : ".$diff_in_seconds);

            if ($diff_in_seconds > 0) {
                
                $this->configHelper->writeLog("======= access token has been expired and will be regenerate via API =======");

                $data = $this->getApiToken($storeId);

                if(!isset($data['error']) || (isset($data['error']) && $data['error']=="")){
                    $this->updateToken($tokenModel,$data);
                    $token = $data['token'];
                }
            } else {

                $this->configHelper->writeLog("======= access token $token active and can be use =======");

                if($retryFlag==1){
                    $data = $this->getApiToken($storeId);
                    if(!isset($data['error']) || (isset($data['error']) && $data['error']=="")){
                        $this->updateToken($tokenModel,$data);
                        $token = $data['token'];
                    }   
                }
            }
        }else{
            $data = $this->getApiToken($storeId);
            if(!isset($data['error']) || (isset($data['error']) && $data['error']=="")){
                $model = $this->tokenFactory->create();
                $token = $data['token'];
                $model->setData($data)->save();
            }
        }
        // $this->configHelper->writeLog("======= getToken END =======");
        
        return $token;
    }

    private function getApiToken($storeId)
    {
        // $this->configHelper->writeLog("      ======= getApiToken START =======");

        $payload = [
            'client_id'     => $this->configHelper->getClientId(),
            'client_secret' => $this->configHelper->getClientSecret(),
            'scope'         => $this->configHelper->getClientScope()
        ];

        $header = array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer '.$this->getOauthToken()
        );

        $url = $this->configHelper->getBaseUrl().self::AUTH_URL;

        $getToken = $this->callApi(http_build_query($payload), $url, $header);

        $response = json_decode($getToken,true);

        $this->configHelper->writeLog("======= authentication url : $url =======");
        $this->configHelper->writeLog("authentication header : ".print_r($header,true));
        $this->configHelper->writeLog("authentication payload : ".print_r($payload,true));
        $this->configHelper->writeLog("======= authentication response : $getToken =======");
        
        $token = isset($response['access_token']) ? $response['access_token'] : "";
        $expiredSec = isset($response['expiry']) ? $response['expiry'] : 0;
        if($expiredSec > 0){
            $expiredSec = $expiredSec / 2;
        }

        $error = "";
        if(isset($response['error'])){
            $error = $response['error'];
        } else if(isset($response['error_message'])){
            $error = $response['error_message'];
        }

        if($error != ""){
            $dataStatus = array(
                "payload_request" => json_encode($payload),
                "request_type" => "access_token",
                "request_response" => $getToken,
                "request_increment_id" => "",
                "tried_times" => 0,
                "status" => 0,
                "created_at" => date("Y-m-d H:i:s"),
                "updated_at" => date("Y-m-d H:i:s")
            );
            $this->saveInvoiceStatus($dataStatus, "FAILED", NULL);
        }

        $createdAt = date("Y-m-d H:i:s");
        $expiredAt = date("Y-m-d H:i:s",strtotime($createdAt) + $expiredSec);

        // $this->configHelper->writeLog("      createdAt : ".print_r($createdAt, true));
        // $this->configHelper->writeLog("      expiredAt : ".print_r($expiredAt, true));

        $data = [
            'token' => $token,
            'expiry' => $expiredAt,
            'status' => 1,
            'error' => $error
        ];
        // $this->configHelper->writeLog("      ======= getApiToken END =======");      

        return $data;
    }

    public function submitDocument($token, $docType, $date = null, $docNumber = null)
    {
       
        $startDateTime = $this->configHelper->getStartSyncDateTime();
        $startDateTimeStr = strtotime($startDateTime);
        $nowStr = strtotime("+8 hours 5 minutes");

        if($nowStr < $startDateTimeStr){            
            $now = new \DateTime(date('Y-m-d H:i:s', strtotime(' +8 hours 5 minutes')));
            $this->configHelper->writeLog("startDateTime : $startDateTime || now : ".json_encode($now));
            $this->configHelper->writeLog("startDateTimeStr : $startDateTimeStr || nowStr : $nowStr");
            exit();
        }
        $this->configHelper->writeLog("======= submitDocument START =======");

        $storeId = $this->storeManager->getStore()->getId();
        $stores = $this->storeManager->getStores(true, true);
        if(isset($stores[self::STORE_CODE])){
            $storeId = $stores[self::STORE_CODE]->getId();
        }

        if($date!=null){
            $dateStart = new \DateTime(date('Y-m-d H:i:s', strtotime($date.' -15 minutes')));
            $dateEnd = new \DateTime(date('Y-m-d H:i:s', strtotime($date)));
        }else{
            $dateStart = new \DateTime(date('Y-m-d H:i:s', strtotime(' -15 mins')));
            $dateEnd = new \DateTime(date('Y-m-d H:i:s'));
        }
        $this->configHelper->writeLog(json_encode($dateStart));
        $this->configHelper->writeLog(json_encode($dateEnd));

        if($docType=="invoice"){
            $docCollection = $this->invInterface->getCollection();
        } else{
            $docCollection = $this->cmInterface->getCollection();
        }

        if($docNumber != NULL){
            $docCollection->addAttributeToFilter('increment_id',$docNumber);
            $this->configHelper->writeLog("increment : $docNumber");
        }else{
            $docCollection
            ->addAttributeToFilter('created_at', array(
                'from' => $dateStart,
                'to' => $dateEnd, 
                'date' => true
            ))
            ->addAttributeToFilter('store_id',$storeId);
        }        

        if(count($docCollection) > 0){
            $header = array(
                'Content-Type: application/json',
                'clientId: '.$this->configHelper->getClientId(),
                'onBehalfOf: '.$this->configHelper->getTaxpayer(),
                'Authorization: Bearer '. $token
            );
    
            $url = $this->configHelper->getBaseUrl().self::SUBMIT_URL;   

            foreach ($docCollection as $doc) {

                if($doc->getEmailSent() != '1'){

                    $payloadDocuments = $this->getSubmitPayload($doc, $docType);
                    $payload = [
                        "datafor" => "supplier",
                        "documents" => [$payloadDocuments]
                    ];

                    $this->configHelper->writeLog("======= submitDocument $docType START =======");
                    $this->configHelper->writeLog("submitDocument $docType Payload : ".json_encode($payload));
                    

                    $submitResult = $this->callApi(json_encode($payload), $url, $header);
                    $response = json_decode($submitResult,true);
                    $this->configHelper->writeLog("submitDocument $docType Response : $submitResult ");

                    if( $response["status"]==0 || 
                        ($response["status"]==1 && isset($response["data"]["errorDetails"]))
                    ){
                        if(isset($response["errors"]["errorCode"]) && ($response["errors"]["errorCode"]=="E2042" || $response["errors"]["errorCode"]=="E2048")){
                            $token = $this->getToken(1);
                            $this->submitDocument($token, "invoice", $date, $docNumber);
                        }else{
                            $processStatus = "";
                            $statusType = 0;
                            if(isset($response["data"]["processStatus"]) && $response["data"]["processStatus"] == "SUCCESS"){
                                $processStatus = $response["data"]["processStatus"];
                                $statusType = 1;
                            }

                            $data = array(
                                "payload_request" => json_encode($payload),
                                "request_type" => $docType,
                                "request_response" => $submitResult,
                                "request_increment_id" => $doc->getIncrementId(),
                                "tried_times" => 0,
                                "status" => $statusType,
                                "created_at" => date("Y-m-d H:i:s"),
                                "updated_at" => date("Y-m-d H:i:s")
                            );
                            $this->saveInvoiceStatus($data, $processStatus, $doc);
                        }
                    }
                }
            }
        }

        $this->configHelper->writeLog("======= submitDocument END =======");
    }

    private function getSubmitPayload($doc, $docType)
    {
        $order = $doc->getOrder();
        $invoiceNumber = "";
        $originalEinvoicNumber = "";
        if($docType=="credit_memo"){
            $originalEinvoicNumber = "NA";
            foreach ($order->getInvoiceCollection() as $invoice)
            {
                $invoiceNumber = $invoice->getIncrementId();
            } 
        }
        $docItems = $this->getDocItems($doc);
        $totalNetAmountTest = $this->configHelper->gettotalNetAmountTest();

        if($totalNetAmountTest!="" && $totalNetAmountTest != NULL){
            $totalNetAmount = number_format((float)$totalNetAmountTest, 2, '.', '');
        }else{
            $totalNetAmount = number_format((float)$this->_netLineItemTotalExcludingTax, 2, '.', '');
        }

        // $storeId = $this->storeManager->getStore()->getId();
        $createdAt = strtotime($doc->getCreatedAt()."+8 hours");
        $payload = array(
            "buyerDetails" => $this->getBuyerData($order),
            "eInvoiceTypeCode" => $docType == "invoice" ? "01" : "04",
            "documentNumber" => $doc->getIncrementId(),
            "documentDate" => date('d/m/Y', $createdAt),
            "documentTime" => date('H:i:s', $createdAt),
            "originalEInvoiceReferenceNumber" =>  $originalEinvoicNumber,
            "originalERPReferenceDocumentNumber" => $invoiceNumber,
            "originalERPReferenceDocumentDate"=> "",
            "documentCurrencyCode" => $order->getOrderCurrencyCode(),
            "currencyExchangeRateInMYR" => "1",
            "billingFrequency" => "",
            "billingPeriodStartDate" => "",
            "billingPeriodEndDate" => "",            
            "invoiceLineItems" => $docItems,
            "totalExcludingTax" => number_format(($doc->getSubtotal() + $doc->getDiscountAmount()), 2, '.', ''),
            "totalIncludingTax" => number_format(($doc->getSubtotal() + $doc->getDiscountAmount() + $doc->getTaxAmount()), 2, '.', ''),
            "totalPayableAmount" => number_format(($doc->getSubtotal() + $doc->getDiscountAmount() + $doc->getTaxAmount()), 2, '.', ''),
            "totalNetAmount" => $totalNetAmount,
            "totalDiscountValue" => "0.00",
            "totalFeeOrChargeAmount" => "0.00",
            "totalTaxAmount" => number_format((float)$doc->getTaxAmount(), 2, '.', ''),
            "roundingAmount" => "0.00",
            "paymentDetails" => array(
                "prePaymentAmount" => "0.00"
            ),
            "invoiceAdditionalDiscountAmount" => "0.00",
            "invoiceAdditionalFeeAmount" => "0.00",
            "DocLevelAmountExemptedFromTax" => "0.00",
            "DocLevelDetailsOfTaxExemption" => "Sales- service tax exempt",
            "transactionType" => "B2C",
            "divisionCode" => "0020",
            "branchCode" => "OCE48",
        );
        
        
        return $payload;
    }

    private function getBuyerData($order)
    {
        /**
        * $orderData = $order->getData();
        * $shippingAddress = $order->getShippingAddress();
         */
        

        return array(
                "registrationName"=> "",
                "buyerTIN"=> "",
                "partyIdentifierIdType"=> "",
                "partyIdentifierIdValue"=> "",
                "partySSTRegistrationNumber"=> "",
                "emailId"=> "",
                "addressLine0" => "",
                "addressLine1" => "",
                "addressLine2" => "",
                "postalZone" => "",
                "cityName" => "",
                "countryCode" => "",
                "stateCode" => "",
                "contactNumber" => ""
        );
    }

    private function getDocItems($doc)
    {
        $docItems = $doc->getItems();
        $incNumber = 0;
        $arrResult = [];
        $maxClass = 3;
        $incClassNumber = "";
        $this->_netLineItemTotalExcludingTax = 0;

        $parentItems = [];
        $childItems = [];
        foreach ($docItems as $item) {
            $product = $this->productRepository->getById($item->getProductId());
            $typeId = $product->getTypeId();
            
            if($typeId=="configurable"){
                $parentItems[$item->getSku()] = array(
                    "product_id" => $item->getProductId(),
                    "name" => $item->getName(),
                    "price" => (float)$item->getPrice(),
                    "qty" => (int)$item->getQty(),
                    "tax_amount" => (float)$item->getTaxAmount(),
                    "discount_amount" => (float)$item->getDiscountAmount(),
                    "discount_percent" => (float)$item->getDiscountPercent(),
                );
            }else{
                $childItems[] = array(
                    "sku" => $item->getSku(),
                    "name" => $item->getName(),
                    "product_id" => $item->getProductId(),
                    "price" => (float)$item->getPrice(),
                    "qty" => (int)$item->getQty(),
                    "discount_amount" => (float)$item->getDiscountAmount(),
                    "tax_amount" => (float)$item->getTaxAmount(),
                    "discount_percent" => (float)$item->getDiscountPercent(),
                );
            }
        }

        foreach ($childItems as $child) {
            if(isset($parentItems[$child["sku"]])){
                $quantity = $parentItems[$child["sku"]]["qty"];
                $unitPrice = $parentItems[$child["sku"]]["price"];
                $discountAmount = $parentItems[$child["sku"]]["discount_amount"];
                $discountPercent = $parentItems[$child["sku"]]["discount_percent"];
                $taxAmount = $parentItems[$child["sku"]]["tax_amount"];
                $name = $parentItems[$child["sku"]]["name"];
            }else{
                $quantity = $child["qty"];
                $unitPrice = $child["price"];
                $discountAmount = $child["discount_amount"];
                $discountPercent = $child["discount_percent"];
                $taxAmount = $child["tax_amount"];
                $name = $child["name"];
            }

            
            $subTotal = $quantity * $unitPrice;

            $incNumber++;
            if(strlen($incNumber)==1){
                $incClassNumber = "00".$incNumber;
            }elseif (strlen($incNumber)==2) {
                $incClassNumber = "0".$incNumber;
            }else{
                $incClassNumber = $incNumber;
            }
            
            $arrResult[] = array(
                "itemSerialNumber" => $incClassNumber,
                "classificationCode" => "022",
                "descriptionProductOrService" => strtoupper($name),
                "unitPrice" => number_format($unitPrice, 2, '.', ''),
                "taxType" => "06",
                // "taxRate" => number_format((float)$item->getTaxAmount(), 2, '.', ''),
                "taxRate" => "0.00",
                "measurementCode" => "",
                "quantity" => $quantity,
                "subtotalLineItemAmountExcludingOthers" => number_format((float)$subTotal, 2, '.', ''),
                "discountRate" => number_format($discountPercent, 2, '.', ''),
                "discountAmount" => number_format($discountAmount, 2, '.', ''),
                "feeOrChargeAmount" => "0.00",
                "feeOrChargeRate" => "0.00",
                "lineItemTotalExcludingTax" => number_format($subTotal - $discountAmount, 2, '.', ''),
                "buyerExemptionCertificateNumber" => "",
                "amountExemptedFromTax" => "0.00",
                "lineItemTaxableAmount" => number_format($subTotal - $discountAmount, 2, '.', ''),
                "lineItemTaxAmount" => number_format($taxAmount, 2, '.', ''),
                "lineItemTotalIncludingTax" => number_format($subTotal - $discountAmount + $taxAmount, 2, '.', ''),
                "productTariffCode" => "",
                "originCountryCode" => "MYS",
                "creditGLCode"=> "",
                "creditGLName"=> "",
                "debitGLCode"=> "",
                "debitGLName"=> ""
            );
            $this->_netLineItemTotalExcludingTax += ($subTotal - $discountAmount);
        }

        // $this->configHelper->writeLog($arrResult);

        return $arrResult;
    }

    private function getPaymentData($doc) 
    {
        $order = $doc->getOrder();
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $createdAt = strtotime($doc->getCreatedAt()."+8 hours");
        $prePaymenAmount = 0;
        /**
         * There’s no prepayment in e-comm and therefore ‘0.00’ value for prepayment column
         * 
         * if($payment->getAmountPaid() < $payment->getAmountOrderer()){
         *  $prePaymenAmount = $payment->getAmountPaid();
         * }
        */
        $arrResult = array(
            "paymentMode" => "01",
            "supplierBankAccountNumber" => "",
            "paymentTerms" => "",
            "prePaymentAmount" => number_format((float)$prePaymenAmount, 2, '.', ''),
            "prePaymentDate" => date('d/m/Y', $createdAt),
            "prePaymentTime" => date('H:i:s', $createdAt),
            "prePaymentReferenceNumber" => "",
            "billReferenceNumber" => ""
        );
        return $arrResult;
    }

    private function getShippingData($inv) 
    {
        $order = $inv->getOrder();
        $orderData = $order->getData();
        $shippingAddress = $order->getShippingAddress();
        
        $arrResult = array(
            "registrationName" => $shippingAddress->getFirstName()." ".$shippingAddress->getLastName(),
            "recipientTIN" => "C2584563202",
            "partyIdentifierIdType" => "BRN",
            "partyIdentifierIdValue" => "",
            "addressLine0" => $shippingAddress->getStreet()[0],
            "addressLine1" => isset($shippingAddress->getStreet()[1]) ? $shippingAddress->getStreet()[1] : "",
            "postalZone" => $shippingAddress->getZipCode(),
            "cityName" => "Malaysia",
            "stateCode" => "15",
            "countryCode" => "MYS"
        );
        return $arrResult;
    }

    private function saveInvoiceStatus($data, $processStatus, $doc)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $invoiceSender = $objectManager->get('\Wow\Einvoice\Model\Order\Email\Sender\InvoiceSender');
        $creditMemoSender = $objectManager->get('\Wow\Einvoice\Model\Order\Email\Sender\CreditmemoSender');

        $statusCollection = $this->statusFactory->create()->getCollection()
        ->addFieldToFilter('request_type',$data["request_type"])
        ->addFieldToFilter('request_increment_id',$data["request_increment_id"]);
        $model = $this->statusFactory->create();
        if(count($statusCollection) == 0){
            $model->setData($data)->save();
        }else{
            $statusModel = $statusCollection->getFirstItem();
            $model->load($statusModel->getEinvoicestatusId());
            $model->setStatus($data['status']);
            $model->setTriedTimes($model->getTriedTimes() + $data['tried_times']);
            $model->setUpdatedAt($data['updated_at']);
            $model->setRequestResponse($data['request_response']);
            $model->save();
        }
        if($processStatus == "SUCCESS"){
            if($data["request_type"]=="invoice"){
                $invoiceSender->send($doc);
            }else{
                $creditMemoSender->send($doc);
            }
            $this->configHelper->writeLog("send email ".$data["request_type"]." : ".$data["request_increment_id"]);
        }
    }

    public function resubmitDocument($token)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $invoiceModel = $objectManager->get('\Magento\Sales\Model\Order\Invoice');
        $creditmemoFactory = $objectManager->get('\Magento\Sales\Model\Order\CreditmemoFactory');

        $this->configHelper->writeLog("======= resubmitDocument START =======","einvoice-repush-debug.log");
        
        $header = array(
            'Content-Type: application/json',
            'clientId: '. $this->configHelper->getClientId(),
            'onBehalfOf: '.$this->configHelper->getTaxpayer(),
            'Authorization: Bearer '. $token
        );

        $this->configHelper->writeLog($header,"einvoice-repush-debug.log");

        $url = $this->configHelper->getBaseUrl().self::SUBMIT_URL;

        $statusCollection = $this->statusFactory->create()->getCollection()->addFieldToFilter('status',0);

        $triedTimes = 0;

        $maxTry = $this->configHelper->getMaxTry();

        foreach ($statusCollection as $einvoiceStatus) 
        {
            
            $triedTimes = $einvoiceStatus->getTriedTimes();

            $continueProcess = true;
            if($triedTimes == 5)
            {
                $continueProcess = false;
            }

            if($continueProcess){
                $submitResult = $this->callApi($einvoiceStatus->getPayloadRequest(), $url, $header);
        
                $this->configHelper->writeLog($einvoiceStatus->getRequestIncrementId()." :: ".$submitResult,"einvoice-repush-debug.log");
    
                $response = json_decode($submitResult,true);
    
                if(isset($response["errors"]["errorCode"]) && $response["errors"]["errorCode"]=="E2042"){
                    $token = $this->getToken(1);
                    $this->resubmitDocument($token);
                }else{
    
                    $status = 0;
                    $processStatus = "";
    
                    if(isset($response["status"]) && $response["status"] != NULL){
                        $status = $response["status"];
                    }
                    
                    $data = array(
                        "request_response" => $submitResult,
                        "request_type" => $einvoiceStatus->getRequestType(),
                        "request_increment_id" => $einvoiceStatus->getRequestIncrementId(),
                        "tried_times" => 1,
                        "status" => $status,
                        "updated_at" => date("Y-m-d H:i:s")
                    );
    
                    if($einvoiceStatus->getRequestType()=="invoice"){
                        $doc = $invoiceModel->loadByIncrementId($einvoiceStatus->getRequestIncrementId());
                    }else{
                        $docCollection = $this->cmInterface->getCollection();
                        $cmemos = $docCollection->addAttributeToFilter('increment_id',$einvoiceStatus->getRequestIncrementId());
                        foreach ($cmemos as $cmemo) {
                            $doc = $cmemo;
                        }
                    }
    
                    if($status==0 || ($status==1 && isset($response["data"]["errorDetails"]) && !empty($response["data"]["errorDetails"]))){
                        $data["status"] = 0;
                        $triedTimes++;
                    }else{
                        $data["status"] = 2;                    
                        if(isset($response["data"]["processStatus"]) && $response["data"]["processStatus"] == "SUCCESS"){
                            $processStatus = $response["data"]["processStatus"];
                        }
                    }

                    $this->saveInvoiceStatus($data,$processStatus,$doc);
                    
                }
            }
        }

        $this->configHelper->writeLog("======= resubmitDocument END =======","einvoice-repush-debug.log");
    }

}