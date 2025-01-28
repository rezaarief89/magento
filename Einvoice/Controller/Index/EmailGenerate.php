<?php 

namespace Wow\Einvoice\Controller\Index;

class EmailGenerate extends \Magento\Framework\App\Action\Action
{

    protected $requestInterface;

    protected $helperGen;

	protected $configHelper;
	
    public function __construct(
		\Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\RequestInterface $requestInterface,
        \Wow\Einvoice\Helper\Generator $helperGen,
		\Wow\Einvoice\Helper\Configuration $configHelper
	)
	{
		$this->requestInterface = $requestInterface;
		$this->helperGen = $helperGen;
		$this->configHelper = $configHelper;
		return parent::__construct($context);
		
	}
	public function execute()
	{
		// $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
		// $logger = new \Zend_Log();
		// $logger->addWriter($writer);

		$params = $this->requestInterface->getParams();
		foreach ($params as $key => $value) {
			$paramsText = $key;
		}
		$qrCodeUrl = $this->configHelper->getQrCodeUrl();
		// $logger->info("paramsTextGenerateEmail : $paramsText");
		
		// $urlText = "https://uat-microsite1.bdomiddleware.my/buyerportal/customer/login?identity=".$paramsText;
		$urlText = $qrCodeUrl."?identity=".$paramsText;
		return $this->helperGen->generate($urlText);
	}
}