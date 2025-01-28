<?php
namespace Wow\QueueMessage\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Framework\App\State;

/**
 * Class CheckStatus
 */
class PushDataOrder extends Command
{

    public $state;

    const TOPIC_NAME = 'magento.queue.order';

	const SIZE = 5;

    public function __construct(
        State $state, 
        $name = null)
    { 
        $this->state = $state;
        parent::__construct($name);
	}

    protected function configure()
    {
        $this->setName('wow:queue:pushorder');
        $this->setDescription('Push Data Order');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_orderColFactory = $objectManager->get('\Magento\Sales\Model\ResourceModel\Order\CollectionFactory');

        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

        $orderCollection = $_orderColFactory->create()
        ->addFieldToSelect('entity_id')
        // ->getAllIds();
        ->setPageSize(10);

        $this->publishData($orderCollection);

        return 1;
    }

    public function publishData($order)
	{
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_publisher = $objectManager->get('\Magento\Framework\MessageQueue\PublisherInterface');
        $_json = $objectManager->get('\Magento\Framework\Serialize\Serializer\Json');

        $data = $order->getData();

    	if(is_array($data)){
		    //split list of IDs into arrays of 5000 IDs each
        	$chunks = array_chunk($data,self::SIZE);
            // $logger->info("Order : ".print_r($chunks,true));

        	foreach ($chunks as $chunk){
			    //publish IDs to queue
            	// $rawData = [$type => $chunk];
                // $logger->info("rawData : ".print_r($rawData,true));
                $_publisher->publish(self::TOPIC_NAME, $_json->serialize($chunk));
        	}
    	}
	}

}