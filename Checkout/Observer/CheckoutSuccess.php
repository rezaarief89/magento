<?php
/**
 * Copyright © ktech All rights reserved.
 * See COPYING.txt for license details.
 */

namespace KTech\Checkout\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;


class CheckoutSuccess implements ObserverInterface
{

    /**
     * Order Model
     *
     * @var \Magento\Sales\Model\Order $order
     */
    protected $objectManager;
    protected $orderRepository;
    protected $jsonHelper;
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        LoggerInterface $logger,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->objectManager = $objectManager;
        $this->logger = $logger;
        $this->orderRepository = $orderRepositoryInterface;
        $this->jsonHelper = $jsonHelper;
    }
    public function execute(Observer $observer) {
//        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/ktech.log');
//        $logger = new \Zend_Log();
//        $logger->addWriter($writer);
//        $logger->info("Checkout Success");
//        $order = $observer->getEvent()->getOrder();
//        $logger->info(json_encode($order));
//        $orderId = $order->getId();
//        $order = $this->orderRepository->get($orderId);
//        $logger->info(json_encode($order));
//        $logger->info('Order ID',$orderId);
//        $status = $order->getStatus();
//        $logger->info($status);
//
//
//        $order_ids = $observer->getEvent()->getOrderIds();
//        $logger->info('Order',$order);
//        $logger->info($order_ids[0]);

        return $this;
    }
}

