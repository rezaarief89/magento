<?php
namespace Wow\Einvoice\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Magento\Framework\App\State;
use Wow\Einvoice\Helper\Api as ApiHelper;

class RepushSubmitDocument extends Command
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
        $options = [
			new InputOption(
				self::DATE,
				null,
				InputOption::VALUE_OPTIONAL,
				'Date with Y-m-d format'
			)
		];

        $this->setName('wow:einvoice:repush');
        $this->setDescription('Repush Submit Invoice Document');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        $token = $this->apiHelper->getToken();

        $this->apiHelper->resubmitDocument($token);
        
        return 1;
    }
}