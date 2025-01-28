<?php

namespace Wow\Einvoice\Console\Command;

class SendEmail extends \Symfony\Component\Console\Command\Command
{

    private $state;
    private $emailHelper;
    public function __construct(
        \Magento\Framework\App\State $state, 
        \Wow\Einvoice\Helper\Email $emailHelper,
        $name = null
    ){ 
        $this->state = $state;
        $this->emailHelper = $emailHelper;
        parent::__construct($name);
	}

    protected function configure()
    {
        $this->setName('wow:einvoice:sendemail');
        $this->setDescription('Send Log to Email');
        parent::configure();
    }

    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input, 
        \Symfony\Component\Console\Output\OutputInterface $output
    ){
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        $emailData = $this->emailHelper->prepareSendEmail();
        $this->emailHelper->sendEmail($emailData);

        return 1;
    }
}
