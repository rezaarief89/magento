<?php

namespace Wow\QrGenerator\Observer\Invoice;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class EmailTemplateVars implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$generateController = $objectManager->get('\Wow\QrGenerator\Controller\Index\Index');
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$file = $objectManager->get('\Magento\Framework\Filesystem\Driver\File');

        $mediaDirectory = $objectManager->get('Magento\Framework\Filesystem')->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $mediaRootDir = $mediaDirectory->getAbsolutePath();
        $mediaRootDir = rtrim($mediaRootDir,"/");

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $transportObject = $observer->getEvent()->getData('transportObject');
        $order = $transportObject->getData('order');
        $invoices = $order->getInvoiceCollection();
        $baseUrl = $storeManager->getStore()->getBaseUrl();

        $qrSrc = "";
        $qrFilePath = "";
        foreach ($invoices as $invoice)
        {
            $createdAt = strtotime($invoice->getCreatedAt()."+8 hours");
            $invoiceId = $invoice->getIncrementId();
            $params = [
                "TIN"=>"'C21964606060'",
                "documentNo"=>$invoiceId,
                "documentDate"=>"'".date('d-m-Y', $createdAt)."'"
            ];
            $arrayReturn = $generateController->generate($params, true, "customer_email_invoice_$invoiceId");
            $paramsText = $generateController->arrangeParamsToText($params);
            $qrSrc = '<div style="height: 144px; width: 144px; display: block; background: url('.$baseUrl.'/qrcode/index/index?'.$paramsText.'); background-size: contain;"></div>';

            // $txtGenerateParams = "TIN=C21964606060&documentNo=".$invoiceId."&documentDate=".date('d-m-Y', $createdAt);
            // $paramsText = base64_encode($txtGenerateParams);
            // $qrSrc = '<div style="height: 144px; width: 144px; display: block; background: url('.$baseUrl.'/qrcode/index/emailgenerate/'.$paramsText.'); background-size: contain;"></div>';
        }

        $transportObject->setData('einvoiceQrImage', $qrSrc);
        // if ($file->isExists($mediaRootDir . $arrayReturn["filePath"])){
        //     $file->deleteFile($mediaRootDir . $arrayReturn["filePath"]);
        // }
    }
}