<?php

declare(strict_types=1);

namespace Wow\Einvoice\Cron;

class ClearImage
{

   	protected $generator;

    public function __construct(
		\Wow\Einvoice\Helper\Generator $generator
	)
    {
        $this->generator = $generator;
    }

    public function execute()
    {
        $this->generator->clearImage();
    }
}

