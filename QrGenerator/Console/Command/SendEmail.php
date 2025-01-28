<?php
namespace Wow\QrGenerator\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\State;
use Wow\QrGenerator\Helper\Email as HelperEmail;

/**
 * Class CheckStatus
 */
class SendEmail extends Command
{

    public $state;
    public $emailHelper;
    public $dataHelper;
    public $urlBuilder;

    const PARAMS = 'params';

    public function __construct(
        State $state, 
        HelperEmail $emailHelper,
        UrlInterface $urlBuilder,
        $name = null)
    { 
        $this->state = $state;
        $this->emailHelper = $emailHelper;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($name);
	}

    protected function configure()
    {
        $options = [
			new InputOption(
				self::PARAMS,
				null,
				InputOption::VALUE_OPTIONAL,
				'Params'
			)
		];

        $this->setName('wow:qrcode:sendemail');
        $this->setDescription('Send QR Email');
        $this->setDefinition($options);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $params = $input->getOption(self::PARAMS);

        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

        $this->emailHelper->sendEmail($this->urlBuilder->getUrl(), "https://google.com?".$params, $params);

        return 1;
    }

}