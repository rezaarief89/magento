<?php

namespace Fef\CustomerSso\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\ResourceModel\CustomerFactory;
use Magento\Customer\Model\Customer;
use Fef\CustomerSso\Helper\Data;

use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResponseFactory;
use Magento\Customer\Model\Session;

class CustomerAfterUpdate implements ObserverInterface
{
    protected $_customerFactory;
    protected $_customerModel;
    protected $_helper;
    protected $_messageManager;
    protected $_storeManager;
    protected $_url;
    protected $_responseFactory;
    protected $customerSession;

    public function __construct(
        CustomerFactory $customerFactory,
        Customer $customerModel,
        Data $helper,
        ManagerInterface $messageManager,
        StoreManager $storeManager,
        UrlInterface $url,
        ResponseFactory $responseFactory,
        Session $customerSession
    ) {
        $this->_customerFactory = $customerFactory;
        $this->_customerModel = $customerModel;
        $this->_helper = $helper;
        $this->_messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->_url = $url;
        $this->_responseFactory = $responseFactory;
        $this->customerSession = $customerSession;
    }
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {    

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/customer-update.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("======================= CustomerAfterUpdate =======================");
        
        $customer = $observer->getEvent()->getCustomer();
        $customerId = $customer->getId();

        if (!$customer || ($customerId==NULL || $customerId=="")) {
            return $this;
        }

        $emailCust = $customer->getEmail();

        $customerObj = $this->_customerModel->load($customerId);

        $countryPhoneCode = $customerObj->getData('country_phone_code');
        if($countryPhoneCode==NULL){
            $countryPhoneCode = "";
        } else {
            if((int)strpos($countryPhoneCode,"+") <= 0){
                $countryPhoneCode = "+".$countryPhoneCode;
            }
        }

        $phoneNumber = $customerObj->getData('phone_number');
        
        if($phoneNumber==NULL){
            $phoneNumber = "";
        }else{
            $phoneNumber = trim($countryPhoneCode).$phoneNumber;
        }
        // $phoneNumber = $customerObj->getData('phone_number')==NULL ? "" : trim($countryPhoneCode).$customerObj->getData('phone_number');
        $memberId = $customerObj->getData('proseller_member_id')==NULL ? "" : $customerObj->getData('proseller_member_id');

        $logger->info("customer : $emailCust, name : ".$customer->getName().", phoneNumber : ".$phoneNumber.", memberId : $memberId, countryPhoneCode : $countryPhoneCode");

        $dataUpdate = array(
            "name" => $customer->getName(),
            "email" => $emailCust,
            "phoneNumber" => $phoneNumber
        );
        $respUpdate = $this->_helper->updateMembership($dataUpdate,$memberId);

        return $this;
    }

}