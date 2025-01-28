<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Wow\Ipay88\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Payment\Model\InfoInterface;

class CreditmemoAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {

        $creditMemo = $observer->getEvent()->getCreditmemo();
        $order = $creditMemo->getOrder();

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/ipay88-reund.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        // $logger->info("after credit memo (".$creditMemo->getIncrementid().", order ".$order->getIncrementid()."), ipayTransId : ".$order->getIpayTransId());

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $dataHelper = $objectManager->get('\Wow\Ipay88\Helper\Data');
        $apiData = array(
            "RefNo" => $creditMemo->getIncrementid(),
            "RefundAmount" => $creditMemo->getGrandTotal(),
            "RefundCurrency"=> $creditMemo->getOrderCurrencyCode(),
            "IpayId"=> $order->getIpayTransId()
        );
        $dataHelper->setData($apiData);
        $dataHelper->callApi();
        
    }
}
