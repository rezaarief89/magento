<?php

declare(strict_types=1);

namespace Wow\Einvoice\Cron;

use Wow\Einvoice\Helper\Api as ApiHelper;

class SubmitCreditMemo
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
        $token = $this->apiHelper->getToken();
        
        $this->apiHelper->submitDocument($token, "credit_memo");
    }
}

