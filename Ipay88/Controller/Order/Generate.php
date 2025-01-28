<?php

namespace Wow\Ipay88\Controller\Order;

use Magento\Framework\Controller\ResultFactory;

class Generate extends \Magento\Framework\App\Action\Action
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
        $this->getInitConfig();

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->get('\Ipay88\Ipay88\Helper\Data');
        $resultFactory = $objectManager->get('\Magento\Framework\Controller\ResultFactory');

        $resultJson = $resultFactory->create(ResultFactory::TYPE_JSON);

        $post = (array) $this->getRequest()->getPost();

        // $logger->info("post : ".print_r($post,true));
        
        $merchantKey            = $helper->getConfig('ipay88_app_merchant_key');
        $merchantCode           = isset($post['MerchantCode']) ? $post['MerchantCode'] : '';
        $paymentId              = isset($post['PaymentId']) ? $post['PaymentId'] : '';
        $orderAmount            = isset($post['Amount']) ? $post['Amount'] : 0;
        $currency               = isset($post['Currency']) ? $post['Currency'] : '';
        $refNo                  = isset($post['RefNo']) ? $post['RefNo'] : '';
        $status                 = isset($post['Status']) ? $post['Status'] : '';

        $signature = new \Ipay88_Signature();
        $signature->setMechantKey($merchantKey);
        $signature->setMerchantCode($merchantCode);
        $signature->setRefNo($refNo);
        $signature->setAmount($orderAmount);
        $signature->setCurrency($currency);
        
        $expectedSignature = $signature->getResponseSignature();

        $resultJson->setData([
            "signature" => $expectedSignature,
            "success" => true
        ]);

        return $resultJson;
    }
}