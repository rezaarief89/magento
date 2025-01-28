<?php

namespace Wow\Einvoice\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Configuration extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function getClientId()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/api_config/clientId', ScopeInterface::SCOPE_STORES, 1);
    }

    public function getClientSecret()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/api_config/clientSecret', ScopeInterface::SCOPE_STORES, 1);
    }

    public function getClientScope()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/api_config/clientScope', ScopeInterface::SCOPE_STORES, 1);
    }


    public function getSecretEncrypted()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/api_config/clientsecretencrypted', ScopeInterface::SCOPE_STORES, 1);
    }

    public function getAppSecretKey()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/api_config/appsecretkey', ScopeInterface::SCOPE_STORES, 1);
    }

    public function getTaxpayer()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/api_config/taxpayer', ScopeInterface::SCOPE_STORES, 1);
    }

    public function getBaseUrl()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/api_config/baseUrl', ScopeInterface::SCOPE_STORES, 1);
    }

    public function getEmailRecipient()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/notif_config/recepient_email', ScopeInterface::SCOPE_STORES, 1);
    }

    public function getMaxTry()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/api_config/maxtry', ScopeInterface::SCOPE_STORES, 1);
    }

    public function gettotalNetAmountTest()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/test_failed/total_net_amount', ScopeInterface::SCOPE_STORES, 1);
    }

    public function getStartSyncDateTime()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/start_config/startdate', ScopeInterface::SCOPE_STORES, 1);
    }

    public function writeLog($message, $filename = "einvoice-debug.log")
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/'.$filename);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        if(is_array($message)){
            $logger->info(print_r($message,true));
        } else if(is_object($message)){
            $array = json_decode(json_encode($message), true);
            $logger->info(print_r($array,true));
        } else {
            $logger->info($message);
        }
    }

    public function getQrCodeUrl()
    {
        return $this->scopeConfig->getValue('woweinvoiceapi/api_config/qrcodeurl', ScopeInterface::SCOPE_STORES, 1);
    }
}