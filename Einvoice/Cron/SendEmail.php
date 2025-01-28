<?php

declare(strict_types=1);

namespace Wow\Einvoice\Cron;

class SendEmail
{

   	protected $emailHelper;

    public function __construct(
		\Wow\Einvoice\Helper\Email $emailHelper
	)
    {
        $this->emailHelper = $emailHelper;
    }

    public function execute()
    {
        $emailData = $this->emailHelper->prepareSendEmail();
        $this->emailHelper->sendEmail($emailData);
    }
}

