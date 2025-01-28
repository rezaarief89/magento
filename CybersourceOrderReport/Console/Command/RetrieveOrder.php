<?php
namespace Wow\CybersourceOrderReport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Magento\Framework\App\State;
use Wow\CybersourceOrderReport\Helper\Data;


class RetrieveOrder extends Command
{

    private $state;

    private $helper;

    const DATE = 'date';

    public function __construct(
        State $state, 
        Data $helper,
        $name = null
    ){ 
        $this->state = $state;
        $this->helper = $helper;
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

        $this->setName('wow:cyber:receiveorder');
        $this->setDescription('Receive order with cybersource payment gateway');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($date = $input->getOption(self::DATE)) {
			$this->helper->publish($date);
		}else{
            $this->helper->publish();
        }

        return 1;
    }
}