<?php
namespace Wow\Einvoice\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Framework\App\State;
use Wow\Einvoice\Helper\Api as ApiHelper;

class GetToken extends Command
{

    private $state;

    private $apiHelper;

    const DATE = 'date';

    public function __construct(
        State $state, 
        ApiHelper $helper,
        $name = null
    ){ 
        $this->state = $state;
        $this->apiHelper = $helper;
        parent::__construct($name);
	}

    protected function configure()
    {
        $this->setName('wow:einvoice:gettoken');
        $this->setDescription('Get auth token from API or database');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->apiHelper->getToken();
        return 1;
    }
}