<?php
namespace Wow\Einvoice\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use Magento\Framework\App\State;
use Wow\Einvoice\Helper\Api as ApiHelper;

class SubmitCreditMemo extends Command
{

    private $state;

    private $apiHelper;

    const DATE = 'date';

    const NUMBER = 'number';

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
            ),
            new InputOption(
				self::NUMBER,
				null,
				InputOption::VALUE_OPTIONAL,
				'Credit memo increment Number'
			)
		];

        $this->setName('wow:einvoice:submitcreditmemo');
        $this->setDescription('Submit Credit Memo Document');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        $token = $this->apiHelper->getToken();

        if ($date = $input->getOption(self::DATE)) {
			$this->apiHelper->submitDocument($token, "credit_memo", $date);
		}else{
            if($incNumber = $input->getOption(self::NUMBER)){
                $this->apiHelper->submitDocument($token, "credit_memo", NULL, $incNumber);
            }else{
                $this->apiHelper->submitDocument($token, "credit_memo");
            }
        }
        
        return 1;
    }
}