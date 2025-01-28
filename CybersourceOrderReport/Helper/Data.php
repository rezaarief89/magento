<?php
namespace Wow\CybersourceOrderReport\Helper;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Wow\CybersourceOrderReport\Model\ResourceModel\ReportTable\CollectionFactory as ReportColFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $orderColFactory;
    public $publisher;
    public $json;
    public $reportColFactory;

    const TOPIC_NAME = 'wow.cyber.order';

	const SIZE = 10;


    public function __construct(
        CollectionFactory $orderColFactory,
        PublisherInterface $publisher,
        Json $json,
        ReportColFactory $reportColFactory
        ){ 
        $this->orderColFactory = $orderColFactory;
        $this->publisher = $publisher;
        $this->json = $json;
        $this->reportColFactory = $reportColFactory;
	}

    public function getOrderCollection($date = null)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);


        if($date == null){
            $date = date("Y-m-d");
        }

        $logger->info("date : $date");

        try {
            $orderCollection = $this->orderColFactory->create()
            ->addFieldToSelect(new \Zend_Db_Expr("DATE_FORMAT(`main_table`.`created_at`,'%Y-%m-%d') as date"))
            ->addFieldToFilter('created_at',array('like'=>$date."%"))
            ->addFieldToFilter('state',
                array('in' => array('complete', 'processing','received'))
            )
            ->setPageSize(10);

            $orderCollection->getSelect()
            ->columns('COUNT(*) AS total_order')
            ->group(new \Zend_Db_Expr("DATE_FORMAT(`main_table`.`created_at`,'%Y-%m-%d')"));

            $logger->info("query : ".$orderCollection->getSelect());
        } catch (\Exception $ex) {
            $logger->info("Exception in getOrderCollection : ".$ex->getMessage());
        }
        

        return $orderCollection;
    }

    public function publish($date = null)
	{
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        if($date == null){
            $date = date("Y-m-d");
        }

        $chunk = [
            "date" => $date
        ];
        
        $this->publisher->publish(self::TOPIC_NAME, $this->json->serialize($chunk));
	}

    public function getReportCollection($date)
    {
        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // $reportColFactory = $objectManager->get('\Wow\CybersourceOrderReport\Model\ResourceModel\ReportTable\CollectionFactory');

        $reportCollection = $this->reportColFactory->create()
        ->addFieldToSelect(array('date'))
        ->addFieldToFilter('date',$date);

        return $reportCollection->getFirstItem()->getData();
    }

}