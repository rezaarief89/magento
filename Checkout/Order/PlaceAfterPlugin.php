<?php

namespace KTech\Checkout\Order;

use Psr\Log\LoggerInterface;

class PlaceAfterPlugin
{
    protected $ktechHelper;
    protected $logger;
    protected $orderRepository;
    protected $jsonHelper;

    public function __construct(
        \KTech\Checkout\Helper\Data $ktechHelper,
        LoggerInterface $logger,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->ktechHelper = $ktechHelper;
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagementInterface
     * @param \Magento\Sales\Model\Order\Interceptor $order
     * @return $order
     */
    public function afterPlace(\Magento\Sales\Api\OrderManagementInterface $orderManagementInterface, $order)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info('KTech PlaceAfterPlugin');

        $orderId = $order->getId();
        $logger->info('orderId: ' . $orderId);

        // do something with order object (Interceptor )
        // $key = 'order::' . $orderId;
        // $this->queue->insertOrUpdate($key, $this->jsonHelper->jsonEncode(['id' => $orderId]), 1);

        return $order;
    }
}
