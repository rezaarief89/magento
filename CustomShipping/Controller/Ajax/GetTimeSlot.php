<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_<modulename>
 * @author    Webkul Software Private Limited
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Fef\CustomShipping\Controller\Ajax;

use Magento\Framework\Controller\ResultFactory;

class GetTimeSlot extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Action\Contex
     */
    private $context;
    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->context = $context;
    }
    
    /**
     * @return json
     */
    public function execute()
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->get("\Fef\CustomShipping\Helper\Data");
        $outletId = $helper->getConfig("carriers/custom/outlet_id");

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $dataTimeslot = [];
        try {
            
            $whoteData = $this->context->getRequest()->getParams();

            if(isset($whoteData["delivery_date"])){
                $dateParams = strtotime ($whoteData["delivery_date"]); 
                $dateParams =  date ( 'Y-m-d' , strtotime('+3 days',$dateParams));

                $url = $helper->getConfig("carriers/custom/base_url")."generate-timeslot";
                $apiParams = [
                    "date" => $dateParams,
                    "daysCount" => 1,
                    "orderMode" => "DELIVERY",
                    "outletId" => $outletId
                ];
                $resGetSlotResult = $helper->setCurl($url,"POST",$apiParams,1);
                if($helper->getDebugMode()==1){
                    // $logger->info("apiParams : ".json_encode($apiParams));
                    // $logger->info("resGetSlotResult : ".json_encode($resGetSlotResult));
                }
                $resGetSlotResultArray = json_decode($resGetSlotResult,true);

                if($resGetSlotResultArray["status"]=="success"){
                    $dataTimeslot = $resGetSlotResultArray["data"][0]["timeSlot"];
                }
                // else{
                //     $dataTimeslot = array(
                //         "13:00 - 14:00",
                //         "14:00 - 15:00"
                //     );
                // }
                $resultJson = $this->setResult(true,"Process Done",$dataTimeslot);
            }else{
                $resultJson = $this->setResult(false,"Process Failed",[]);
            }
        } catch (\Exception $ex) {
            $resultJson = $this->setResult(false,$ex->getMessage(),[]);
        }
        

        return $resultJson;
    }
    private function setResult($flag, $msg, $data){
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData([
            "message" => ($msg), 
            "success" => $flag,
            "dataTimeslot" =>json_encode($data)
        ]);
        return $resultJson;
    }
}