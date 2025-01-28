<?php

namespace Wow\Ipay88\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wow\Ipay88\Helper\Data;

class Test extends Command
{
    private $dataHelper;

    public function __construct(
        State $appState,
        Data $dataHelper
    ) {
        parent::__construct();
        $this->appState = $appState;
        $this->dataHelper = $dataHelper;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('wow:ipay88:test')
            ->setDescription('Wow Ipay88 Test coomand');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $executionStartTime = microtime(true);
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $executionEndTime = microtime(true);

        $seconds = $executionEndTime - $executionStartTime;

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $eventManager = $objectManager->get('\Magento\Framework\Event\ManagerInterface');

        $this->dataHelper->callApi();

        echo 'Done in ' . $seconds . ' seconds';

        return 1;
    }
}
