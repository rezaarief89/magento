<?php
namespace Wow\Einvoice\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearImage extends Command
{

    private $state;
    private $generator;

    public function __construct(
        \Magento\Framework\App\State $state, 
        \Wow\Einvoice\Helper\Generator $generator,
        $name = null
    ){ 
        $this->state = $state;
        $this->generator = $generator;
        parent::__construct($name);
	}

    protected function configure()
    {
        $this->setName('wow:einvoice:clearimage');
        $this->setDescription('Clear Generated QR Image');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
        $this->generator->clearImage();

        return 1;
    }
}
