<?php
namespace Wow\Einvoice\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Framework\App\State;
use Wow\Einvoice\Helper\Api as ApiHelper;

class Invoice extends Command
{

    private $state;

    public function __construct(
        State $state, 
        $name = null
    ){ 
        $this->state = $state;
        parent::__construct($name);
	}

    protected function configure()
    {

        $this->setName('wow:einvoice:invoice');
        $this->setDescription('Generate Invoice Report');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $invoiceCron = $objectManager->get('\Wow\Einvoice\Cron\Invoice');
        $invoiceCron->execute();
        return 1;
    }
}