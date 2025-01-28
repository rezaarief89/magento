<?php

namespace Wow\Einvoice\Observer\Invoice;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class EmailTemplateVars implements ObserverInterface
{
    private $dataHelper;
    private $storeManager;
    private $configHelper;
    private $generateController;

    public function __construct(
        \Wow\Einvoice\Helper\Data $dataHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Wow\Einvoice\Helper\Configuration $configHelper,
        \Wow\Einvoice\Controller\Index\Generate $generateController
    ) {
        $this->dataHelper = $dataHelper;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->generateController = $generateController;
    }
    
    public function execute(Observer $observer)
    {
        $transportObject = $observer->getEvent()->getData('transportObject');
        $order = $transportObject->getData('order');
        $invoices = $order->getInvoiceCollection();

        $qrSrcDiv = "";        
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $generateUrl = "einvoice/index/generate";
        $emailGenerateUrl = "einvoice/index/emailgenerate";

        foreach ($invoices as $invoice)
        {
            $invoiceId = $invoice->getIncrementId();
            $successCount = $this->dataHelper->getSyncSuccessCountByType("invoice",$invoiceId);
            $createdAt = strtotime($invoice->getCreatedAt()."+8 hours");

            if($successCount > 0){
                
                // $params = [
                //     "TIN"=>"C21964606060",
                //     "documentNo"=>$invoiceId,
                //     "documentDate"=>date('d-m-Y', $createdAt)
                // ];
                // $paramsText = $this->generateController->arrangeParamsToText($params,0);
                // $qrSrcDiv = '<div style="height: 144px; width: 144px; display: block; background: url('.$baseUrl.'/'.$generateUrl.'?'.$paramsText.'); background-size: contain;"></div>';

                $txtGenerateParams = "TIN=C21964606060&documentNo=".$invoiceId."&documentDate=".date('d-m-Y', $createdAt);
                $paramsText = base64_encode($txtGenerateParams);
                $qrSrcDiv = '<div style="height: 144px; width: 144px; display: block; background: url('.$baseUrl.'/'.$emailGenerateUrl.'/'.$paramsText.'); background-size: contain;"></div>';
                
            }
        }
        $transportObject->setData('einvoiceQrDiv', $qrSrcDiv);
    }
}