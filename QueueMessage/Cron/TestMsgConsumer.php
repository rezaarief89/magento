<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Wow\QueueMessage\Cron;

use Magento\Framework\Json\Helper\Data;
use Magento\Framework\MessageQueue\PublisherInterface;
use Wow\QueueMessage\Model\Queue\TestMsg;
use Magento\Framework\MessageQueue\ConsumerFactory;

class TestMsgConsumer
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var Data
     */
    private $jsonHelper;

	/**
	 * @var Logger
	 */
    private $logger;

    /**
     * @var ConsumerFactory
     */
    private $consumerFactory;

    /**
     * @param PublisherInterface $publisher
     * @param Data $jsonHelper
     * @param Logger $logger
     * @param ConsumerFactory $consumerFactory
     */
    public function __construct(
        PublisherInterface $publisher,
        Data $jsonHelper,
        ConsumerFactory $consumerFactory
    ) {
        $this->publisher = $publisher;
        $this->jsonHelper = $jsonHelper;
        $this->consumerFactory = $consumerFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $details[] = [
            "any_informatic_index" => "value",
        ];
        $batchSize = 20;
        $noOfMessages = 10;

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        

        $this->publisher->publish(
            TestMsg::TOPIC_NAME,
            $this->jsonHelper->jsonEncode($details)
        );
        $consumer = $this->consumerFactory->get('testMsgConsumer', $batchSize);
        $consumer->process($noOfMessages);

        $logger->info('Cron executed successfully');
    }
}