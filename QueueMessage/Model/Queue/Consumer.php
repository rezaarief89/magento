<?php

namespace Wow\QueueMessage\Model\Queue;

use Magento\Framework\MessageQueue\ConsumerConfiguration;

class Consumer extends ConsumerConfiguration
{

    protected $_notifier;

    /**
     * @param string $orderSearchResult
     */
    public const TOPIC_NAME = "magento.queue.order";

    public function process($orderSearchResult)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_notifier = $objectManager->get('\Magento\Framework\Notification\NotifierInterface');

        try{
            $this->execute($orderSearchResult);
        }catch (\Exception $e){
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during add order to queue. Please see log for details.');
            $logger->info("Exceptionx ($errorCode) : ".$e->getMessage());
        }
    }

    /**
     * @param $orderItems
     *
     * @throws LocalizedException
     */
    private function execute($orderItems)
    {
        try {
            $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info("test");

            $orderCollectionArr = [];
            $orderItems = json_decode($orderItems, true);
            if(is_array($orderItems)){
                foreach ($orderItems as $type => $orderId) {
                    $orderCollectionArr[] = [
                        'type' => $type,
                        'entity_id' => $orderId,
                        'priority' => 1,
                    ];
                }
            }
            // $logger->info("orderCollectionArr : ".print_r($orderCollectionArr,true));
        } catch (\Exception $ex) {
            $logger->info("Exception : ".$ex->getMessage());
        }
        
        
    }
}