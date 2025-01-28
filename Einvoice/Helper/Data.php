<?php

namespace Wow\Einvoice\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    private $statusFactory;

    private $configHelper;
    
    public function __construct(
        \Wow\Einvoice\Model\EinvoiceStatusFactory $statusFactory,
        \Wow\Einvoice\Helper\Configuration $configHelper
    ){
        $this->statusFactory = $statusFactory;
        $this->configHelper = $configHelper;
    }

    public function getSyncSuccessCountByType($type, $incrementId)
    {
        $statusCollection = $this->statusFactory->create()->getCollection()
        ->addFieldToFilter('status',['gt'=>0])
        ->addFieldToFilter('request_type',$type)
        ->addFieldToFilter('request_increment_id',$incrementId);
        return count($statusCollection);
    }

    public function getSyncSuccess()
    {
        $statusCollection = $this->statusFactory->create()->getCollection()
        ->addFieldToFilter('status',['gt'=>0]);
        return $statusCollection;
    }

    public function getQrText()
    {
        $qrText = "Please scan the QR Code for E-Invoice Issuance\n"
              . "1) E-Invoices are available 2 hours after time of purchase until the 3rd day of the following month.\n"
              . "2) Customers who have requested E-invoices are required to approach original store of purchase for returns / refunds.";
        return $qrText;
    }
}