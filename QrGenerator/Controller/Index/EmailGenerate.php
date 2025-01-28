<?php 

namespace Wow\QrGenerator\Controller\Index;

class EmailGenerate extends \Magento\Framework\App\Action\Action
{

    protected $resultPageFactory = false;
	
    public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	)
	{
		$this->resultPageFactory = $resultPageFactory;
		return parent::__construct($context);
		
	}
	public function execute()
	{
		$writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$requestInterface = $objectManager->get('\Magento\Framework\App\RequestInterface');
		$helperGen = $objectManager->get('\Wow\QrGenerator\Helper\Generator');

		$params = $requestInterface->getParams();
		foreach ($params as $key => $value) {
			$paramsText = $key;
		}
		$logger->info("paramsTextGenerateEmail : $paramsText");
		
		$urlText = "https://uat-microsite1.bdomiddleware.my/buyerportal/customer/login?identity=".$paramsText;
        
		return $helperGen->generate($urlText);
	}

}
