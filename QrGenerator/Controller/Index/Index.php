<?php 

namespace Wow\QrGenerator\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
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

		$resultPage = $this->resultPageFactory->create();
		$params = $requestInterface->getParams();

		$logger->info("params : ".print_r($params,true));
		
        $this->generate($params);
	}

	public function generate($params=[], $returnUri = false, $type = "invoice")
    {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$helperGen = $objectManager->get('\Wow\QrGenerator\Helper\Generator');

		// $paramsText = "";
		// foreach ($params as $key => $value) {
		// 	if($paramsText!=""){
		// 		$paramsText.="&";
		// 	}
		// 	$paramsText.=$key."=".$value;
		// }
        // if($paramsText!=""){
        //     $paramsText = base64_encode($paramsText);
        // }

		$paramsText = $this->arrangeParamsToText($params);

		$urlText = "https://uat-microsite1.bdomiddleware.my/buyerportal/customer/login?identity=".$paramsText;
        
		return $helperGen->generate($urlText, $returnUri, $type);
    }

	public function arrangeParamsToText($params)
	{
		$writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

		$paramsText = "";
		foreach ($params as $key => $value) {
			if($paramsText!=""){
				$paramsText.="&";
			}
			$paramsText.=$key."=".$value;
		}
		$logger->info("paramsText : ".$paramsText);
        if($paramsText!=""){
            $paramsText = base64_encode($paramsText);
        }
		return $paramsText;
	}
}