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

class GetCost extends \Magento\Framework\App\Action\Action
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
        // $logger->info("Get Cost");

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');
        $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');

        try {
            $currSym = $storeManager->getStore()->getBaseCurrency()->getCurrencySymbol();
            
            $voucherPointUsed = $voucherPointUsedFactory->create();
            $voucherPointUsedCollection = $voucherPointUsed->getCollection()
            ->addFieldToFilter('customer_id', $checkoutSession->getQuote()->getCustomerId())
            ->addFieldToFilter('quote_id', $checkoutSession->getQuote()->getId());

            $dataCollection = $voucherPointUsedCollection->getData();

            // $logger->info("voucherPointUsed : ".print_r($dataCollection,true));

            $voucherName = "";
            $voucherAmount = 0;
            $voucherType = "";
            foreach ($dataCollection as $voucher) {
                $voucherName = $voucher["voucher_name"];
                $voucherAmount = $voucher["voucher_amount"];
                if($voucher["voucher_type"]=="%"){
                    $voucherAmount = $voucherAmount." ".$voucher["voucher_type"]." Discount";
                }else{
                    $voucherAmount = $currSym." ".$voucherAmount;
                }
            }

            $checkoutQuote = $checkoutSession->getQuote();
            // $logger->info(print_r($checkoutQuote->getData(),true));
            $arrResult = array(
                "cost_weight"=>$checkoutQuote->getData('cost_weight'),
                "cost_location"=>$checkoutQuote->getData('cost_location'),
                // "cost_location"=>5,
                "cost_staircase"=>$checkoutQuote->getData('cost_staircase'),
                "voucher_name"=>$voucherName,
                "voucher_amount"=>$voucherAmount,
                // "cost_item_spesific"=>$currSym.$checkoutQuote->getData('cost_item_spesific'),
                
                // "cost_period"=>$currSym.$checkoutQuote->getData('cost_period'),
                // "cost_delivery_type"=>$currSym.$checkoutQuote->getData('cost_delivery_type'),
            );

            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

            $whoteData = $this->context->getRequest()->getParams();
            $resultJson->setData([
                "message" => ("Process Done"), 
                "success" => true,
                "costData" => json_encode($arrResult)
            ]);
        } catch (\Exception $ex) {
            $resultJson->setData([
                "message" => ($ex->getMessage()), 
                "success" => false,
                "costData" => "{}"
            ]);
        }
        

        return $resultJson;
    }
}