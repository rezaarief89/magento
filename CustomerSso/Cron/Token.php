<?php

namespace Fef\CustomerSso\Cron;

class Token
{

	public function execute()
	{

        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $om->get('\Fef\CustomerSso\Helper\Token')->CheckToken();

		return $this;

	}
}