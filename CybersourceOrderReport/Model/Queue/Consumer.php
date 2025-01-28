<?php

namespace Wow\CybersourceOrderReport\Model\Queue;

use Magento\Framework\MessageQueue\ConsumerConfiguration;
use Wow\CybersourceOrderReport\Model\ReportTableFactory;
use Wow\CybersourceOrderReport\Helper\Data;
use Wow\CybersourceOrderReport\Model\ReportTable;

class Consumer extends ConsumerConfiguration
{

    public $reportTableFactory;

    public $reportTableModel;

    public $helper;
    
    public const TOPIC_NAME = "wow.cyber.order";

    public function __construct(
        ReportTableFactory $reportTableFactory, 
        ReportTable $reportTableModel,
        Data $helper,
    ){ 
        $this->reportTableFactory = $reportTableFactory;
        $this->reportTableModel = $reportTableModel;
        $this->helper = $helper;
	}

    public function process($orderDate)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        try{
            $this->execute($orderDate);
        }catch (\Exception $e){
            $errorCode = $e->getCode();
            $message = __('Sorry, something went wrong during add order to queue. Please see log for details.');
            $logger->info("Exception ($errorCode) : ".$e->getMessage());
        }
    }

    /**
     * @param $orderDatas
     *
     * @throws LocalizedException
     */
    private function execute($orderDate)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        try {

            $orderDate = json_decode($orderDate, true);
            $orderColl = $this->helper->getOrderCollection($orderDate["date"]);
            $orderDatas = $orderColl->getData();

            $reportTableData = $this->helper->getReportCollection($orderDate["date"]);

            if(count($reportTableData)==0){
                $reportTable = $this->reportTableFactory->create(); 
            }else{
                $reportTable = $this->reportTableModel->load($reportTableData['id']);
            }

            if(count($orderDatas) > 0){
                foreach ($orderDatas as $key => $item) {
                    $reportTable->setDate($item["date"]);
                    $reportTable->setTotalOrder($item["total_order"]);
                    $reportTable->save();
                }
            }else{
                $reportTable->setDate($orderDate["date"]);
                $reportTable->setTotalOrder(0);
                $reportTable->save();
            }
            

        } catch (\Exception $ex) {
            $logger->info("Exception : ".$ex->getMessage());
        }
    }

    private function getReportModel()
    {
        $reportTableData = $this->helper->getReportCollection($item["date"]);
        if(count($reportTableData)==0){
            $reportTable = $this->reportTableFactory->create(); 
        }else{
            $reportTable = $this->reportTableModel->load($reportTableData['id']);
        }
        return $reportTable;
    }


}