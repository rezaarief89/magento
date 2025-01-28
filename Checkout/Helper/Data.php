<?php

namespace KTech\Checkout\Helper;

use Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_SANDBOX_FLAG = 'carriers/custom/use_sandbox';

    const XML_BASE_URL_CONFIG = 'carriers/custom/base_url';
    const XML_SANDBOX_LOGIN_URL_CONFIG = 'carriers/custom/sandbox_login_url';
    const XML_SANDBOX_REFRESH_URL_CONFIG = 'carriers/custom/sandbox_refresh_url';
    const XML_SANDBOX_RATE_URL_CONFIG = 'carriers/custom/sandbox_rate_url';

    const XML_PROD_URL_LOGIN_CONFIG = 'carriers/custom/production_url';
    const XML_PROD_URL_REFRESH_CONFIG = 'carriers/custom/production_url';
    const XML_PROD_URL_RATE_CONFIG = 'carriers/custom/production_url';

    const XML_USERNAME_CONFIG = 'carriers/custom/username';
    const XML_PASSWORD_CONFIG = 'carriers/custom/password';

    const XML_DEBUG = 'carriers/custom/debug';

    protected $_shipmentQueue;
    protected $_orderFactory;
    protected $_shipmentRepository;
    protected $_shipmentApiRepository;
    protected $_searchCriteriaBuilder;
    protected $_trackResource;
    protected $_trackFactory;
    protected $_modelCityFactory;
    protected $_shipmentNotifier;
    protected $scopeConfig;
    protected $_objectManager;
    protected $_convertOrder;
    protected $_shipmentOrder;
    protected $_product;
    protected $resourceConnection;
    protected $orderCollectionFactory;
    protected $shipmentLoader;
    protected $registry;
    protected $transaction;
    protected $messageManager;
    private $logger;
    protected $orderItemRepository;

    public function __construct(
        \Magento\Sales\Model\Order $orderFactory,
        \Magento\Sales\Model\Order\ShipmentRepository $shipmentRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentApiRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Track $trackResource,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Convert\Order $convertOrder,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\ObjectManager\ConfigLoaderInterface $configLoader,
        \Magento\Catalog\Api\ProductRepositoryInterface $product,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader $shipmentLoader,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Psr\Log\LoggerInterface $logger

    ) {
        $this->_orderFactory = $orderFactory;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_shipmentApiRepository = $shipmentApiRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_trackResource = $trackResource;
        $this->_trackFactory = $trackFactory;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->_objectManager = $objectManager;
        $this->_convertOrder = $convertOrder;
        $this->scopeconfig = $config;
        $this->_product = $product;
        $this->resourceConnection = $resourceConnection;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->shipmentLoader = $shipmentLoader;
        $this->registry = $registry;
        $this->transaction = $transaction;
        $this->messageManager = $messageManager;
        $this->orderItemRepository = $orderItemRepository;
        $this->logger = $logger;
        $objectManager->configure($configLoader->load('frontend'));
    }

    public function getDebugMode()
    {
        return $this->getConfig(self::XML_DEBUG);
    }

    public function getUsername()
    {
        return $this->getConfig(self::XML_USERNAME_CONFIG);
    }

    public function getPassword()
    {
        return $this->getConfig(self::XML_PASSWORD_CONFIG);
    }

    public function getBaseUrl()
    {
        return $this->getConfig(self::XML_BASE_URL_CONFIG);
    }

    public function getConfig($config_path){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeconfig->getValue($config_path, $storeScope);
    }

    private function callAPi($paramCallApi, $output)
    {

    }

    private function removeUnusedCharacter($value){
        $resValue = preg_replace('~:|/~', ' ', $value);
        $resValue = preg_replace("/[\n\r]/", " ", $resValue);
        $resValue = str_replace('"', "", $resValue);
        $resValue = str_replace('/', "", $resValue);
        $resValue = str_replace(';', "", $resValue);

        return $resValue;
    }

    public function setCurl($url, $method, $param = null, $useToken = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($param != null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
        }

        if($useToken==0){
            $header = array(
                "accept: */*",
                "accept-language: en-US,en;q=0.8",
                "content-type: application/json"
            );
        } else {
            $token = $this->getToken();
            $header = array(
                "accept: */*",
                "accept-language: en-US,en;q=0.8",
                "content-type: application/json",
                "Authorization: Bearer ".$token
            );
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            $header
        );
        $result = curl_exec($ch);

        return $result;
    }

    public function getUrl($type)
    {
        $key = self::XML_SANDBOX_LOGIN_URL_CONFIG;

        if($this->getConfig(self::XML_SANDBOX_FLAG)==1){
            switch ($type) {
                case 'login':
                    $key = self::XML_SANDBOX_LOGIN_URL_CONFIG;
                    break;
                case 'refresh':
                    $key = self::XML_SANDBOX_REFRESH_URL_CONFIG;
                    break;
                case 'rate':
                    $key = self::XML_SANDBOX_RATE_URL_CONFIG;
                    break;
                case 'transactions':
                    $key = "transactions";
                    break;
                default:
                    $key = "";
                    break;
            }
        } else{
            switch ($type) {
                case 'login':
                    $key = self::XML_PROD_URL_LOGIN_CONFIG;
                    break;
                case 'refresh':
                    $key = self::XML_PROD_URL_REFRESH_CONFIG;
                    break;
                case 'rate':
                    $key = self::XML_PROD_URL_RATE_CONFIG;
                    break;
                case 'transactions':
                    $key = "transactions";
                    break;
                default:
                    $key = "";
                    break;
            }
        }

        return $this->getConfig(self::XML_BASE_URL_CONFIG).$this->getConfig($key);
    }

    public function callAuthApi($type, $param = null)
    {
        //GET AUTH LOGIN
        $authUrl = $this->getUrl($type);
        if($param==null){
            $usernameAuth = $this->getUsername();
            $passAuth = $this->getPassword();
            $param = array(
                "username" => $usernameAuth,
                "password" => $passAuth,
            );
        }
        $authReq = $this->setCurl($authUrl,"POST",$param);
        return $authReq;
    }

    public function callAuthRefreshApi($type)
    {

        //GET AUTH LOGIN
        $authUrl = $this->getUrl($type);
        $usernameAuth = $this->getUsername();
        $passAuth = $this->getPassword();
        $param = array(
            "username" => $usernameAuth,
            "password" => $passAuth,
        );
        $authReq = $this->setCurl($authUrl,"POST",$param);
        return $authReq;
    }

    public function generateToken()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/ktech.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // $helperData = $this->_objectManager->get('\Fef\CustomShipping\Helper\Data');

        //GET AUTH LOGIN
        $modelFefTokenFactory = $this->_objectManager->get('\Fef\CustomShipping\Model\FefTokenFactory');
        $modelFefToken = $modelFefTokenFactory->create();
        // $collection = $modelFefToken->getCollection();
        $collection = $this->getTokenCollection();
        $countToken = count($collection->getData());
        $token = "";
        if($countToken==0){
            // insert auto by cred
            // $logger->info("CALL LOGIN API");
            $authReq = $this->callAuthApi("login");
            $logger->info("LOGIN API RESULT : ".$authReq);
            $authReqArray = json_decode($authReq,true);
            if($authReqArray["status"]=="success"){
                if(!empty($authReqArray["data"]["expiry"])){
                    $token = $authReqArray["data"]["token"];
                    $modelFefToken->setToken($token);
                    $modelFefToken->setRefreshToken($authReqArray["data"]["refreshToken"]);
                    $modelFefToken->setExpiry($authReqArray["data"]["expiry"]);
                    $modelFefToken->save();
                    $logger->info("ADDED TOKEN ".$token." AND EXPIRED IN : ".$authReqArray["data"]["expiry"]);
                }
            }
        } else {
            // check token is expired or not
            // if expired then call API refresh token
            $dataCollection = $collection->getData();
            foreach ($dataCollection as $key => $collection) {

                $token = $collection["token"];

                // DIsabled process re-generate token cause using long live token
                // $id = $collection["id"];
                // $expiry = $collection["expiry"];
                // $expiry = str_replace("T"," ",$expiry);
                // $expiry = str_replace("Z","",$expiry);
                // $expiryStrTime = strtotime($expiry);

                // if(time() > $expiryStrTime){
                //     //  call API refresh token
                //     $paramToken = array(
                //         "refreshToken" => $collection["refresh_token"]
                //     );
                //     $authReq = $this->callAuthApi("refresh",$paramToken);
                //     $authReqArray = json_decode($authReq,true);
                //     $logger->info("UPDATE AUTH API RESULT : ".$authReq);

                //     if($authReqArray["status"]=="success"){
                //         $expiryData = $authReqArray["data"]["expiry"];
                //         if(!empty($expiryData)){
                //             $token = $authReqArray["data"]["token"];
                //             $postUpdate = $modelFefToken->load($id);
                //             $postUpdate->setToken($token);
                //             $postUpdate->setRefreshToken($authReqArray["data"]["refreshToken"]);
                //             $postUpdate->setExpiry($authReqArray["data"]["expiry"]);
                //             $postUpdate->save();
                //             $logger->info("TOKEN UPDATED TO ".$token ." AND REFRESH TOKEN UPDATED TO ".$authReqArray["data"]["refreshToken"]." AND EXPIRY UPDATED TO : ".$authReqArray["data"]["expiry"]);
                //         }
                //     }

                // } else {
                //     $token = $expiryStrTime;
                // }
            }
        }
        return $token;
    }

    public function getTokenCollection(){
        $modelFefTokenFactory = $this->_objectManager->get('\Fef\CustomShipping\Model\FefTokenFactory');
        $modelFefToken = $modelFefTokenFactory->create();
        return $modelFefToken->getCollection();
    }

    public function getToken()
    {
        $collection = $this->getTokenCollection();
        $countToken = count($collection->getData());
        $token = "";
        if($countToken>0){
            $dataCollection = $collection->getData();
            foreach ($dataCollection as $key => $collection) {
                $token = $collection["token"];
            }
        }
        return $token;
    }

}
