<?php

namespace Wow\Ipay88\Controller\Signature;

class Index extends \Magento\Framework\App\Action\Action
{

    
    private function getInitConfig()
    {
        if (!class_exists('Ipay88_Config')) {
            $config = \Magento\Framework\App\Filesystem\DirectoryList::getDefaultConfig();
            require_once(BP . '/' . $config['lib_internal']['path'] . "/ipay88-php/Include.php");
        }
    }

    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->create('Ipay88\Ipay88\Helper\Data');

        $this->getInitConfig();

        $merchantKey = $helper->getConfig('ipay88_merchant_key');
        $merchantCode = $helper->getConfig('ipay88_merchant_code');
        $paymentId = "TRX673H546";
        $orderId = "00007841";
        $orderAmount = 12;
        $currency = "RM";
        $status = "Complete";
        
        $signature = new \Ipay88_Signature();
        $signature->setMechantKey($merchantKey);
        $signature->setMerchantCode($merchantCode);
        $signature->setPaymentId($paymentId);
        $signature->setRefNo($orderId);
        $signature->setAmount($orderAmount);
        $signature->setCurrency($currency);
        $signature->setStatus($status);
        
        $expectedSignature = $signature->getResponseSignature();
        
        $dataResponse = array(
            "sign" => $expectedSignature
        );

        // $source = $signature->getMechantKey() . $signature->getMerchantCode() . $signature->getRefNo() . $signature->getHashAmount() . $signature->getCurrency();

        echo "<pre>".print_r($dataResponse,true)."</pre>";
    }
}