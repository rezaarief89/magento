<?php

declare(strict_types=1);

namespace Wow\Einvoice\Cron;

use Wow\Einvoice\Helper\Api as ApiHelper;

class SubmitInvoice
{

   	protected $apiHelper;

    public function __construct(
		ApiHelper $helper,
	)
    {
        $this->apiHelper = $helper;
    }

    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $configHelper = $objectManager->get('\Wow\Einvoice\Helper\Configuration');

        $configHelper->writeLog("======= submitDocument cron START =======");

        $token = $this->apiHelper->getToken();

        $configHelper->writeLog("submitDocument cron token  : $token");
        
        $this->apiHelper->submitDocument($token, "invoice");

        $configHelper->writeLog("======= submitDocument cron END =======");
    }
}

