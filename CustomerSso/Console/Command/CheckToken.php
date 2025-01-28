<?php
namespace Fef\CustomerSso\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateShipment
 */
class CheckToken extends Command
{
    protected $createshipment;

    protected function configure()
    {
        $this->setName('fef:sso:checktoken');
        $this->setDescription('Check Payload Token');
        
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $om->get('\Fef\CustomerSso\Helper\Token')->CheckToken();
    }
}