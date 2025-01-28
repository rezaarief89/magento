<?php

namespace Wow\CybersourceOrderReport\Cron;

use Wow\CybersourceOrderReport\Helper\Data as HelperData;

class OrderReport
{
    private $helper;
    
    public function __construct(
        HelperData $helper
    ) {
        $this->helper = $helper;
    }
    
    public function execute()
    {
        $this->helper->publish();
    }
}