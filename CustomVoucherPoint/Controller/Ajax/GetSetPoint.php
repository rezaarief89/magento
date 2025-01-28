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
namespace Fef\CustomVoucherPoint\Controller\Ajax;

use Magento\Framework\Controller\ResultFactory;
use Fef\CustomShipping\Helper\Data as shippingHelper;
use Magento\Customer\Model\Session;
use Fef\CustomerSso\Helper\Data as customerHelper;
use Fef\CustomVoucherPoint\Helper\Data as vocHelper;

class GetSetPoint extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Action\Contex
     */
    private $context;
    private $shippinghelper;
    private $customerSession;
    private $customerHelper;
    private $vocHelper;


    /**
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        shippingHelper $shippinghelper,
        Session $customerSession,
        customerHelper $customerHelper,
        vocHelper $vocHelper
    ) {
        parent::__construct($context);
        $this->context = $context;
        $this->shippinghelper = $shippinghelper;
        $this->customerSession = $customerSession;
        $this->customerHelper = $customerHelper;
        $this->vocHelper = $vocHelper;
    }
    
    /**
     * @return json
     */
    public function execute()
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // $helper = $objectManager->get("\Fef\CustomShipping\Helper\Data");
        // $customerSession = $objectManager->get("\Magento\Customer\Model\Session");
        // $customerHelper = $objectManager->get("\Fef\CustomerSso\Helper\Data");

        $customerEmail = $this->customerSession->getCustomer()->getEmail();

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            
            $whoteData = $this->context->getRequest()->getParams();
            $customer = $this->customerHelper->getCustomerByEmail($customerEmail);

            $arrCheckProseller = $this->customerHelper->checkProsellerByEmail($customerEmail);

            if(isset($arrCheckProseller["status"]) && $arrCheckProseller["status"]=="success"){
                $this->setPoint($arrCheckProseller["data"],$customer->getId());
                $dataPoint = $arrCheckProseller["data"]["points"];
                $resultJson = $this->setResult(true,"Process Done",$dataPoint);
            } else {
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
            "dataPoint" =>json_encode($data)
        ]);
        return $resultJson;
    }

    private function setPoint($respData,$customerId)
    {
        if(isset($respData["points"]["balance"]) && $respData["points"]["balance"] != NULL && $respData["points"]["balance"] != ""){
            $this->vocHelper->addZokuPoint($customerId, $respData["points"]["balance"]);
        }
    }
}