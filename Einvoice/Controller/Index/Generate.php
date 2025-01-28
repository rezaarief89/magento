<?php 

namespace Wow\Einvoice\Controller\Index;

class Generate extends \Magento\Framework\App\Action\Action
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
		$params = $this->requestInterface->getParams();
        $this->generate($params);
        
	}

    public function generate($params=[], $returnUri = false, $type = "invoice")
    {
		$qrCodeUrl = $this->configHelper->getQrCodeUrl();
		$paramsText = $this->arrangeParamsToText($params);
		
		// $urlText = "https://uat-microsite1.bdomiddleware.my/buyerportal/customer/login?identity=".$paramsText;
		$urlText = $qrCodeUrl."?identity=".$paramsText;
		return $this->helperGen->generate($urlText, $returnUri, $type);
    }

	public function arrangeParamsToText($params, $encodeFlag = 1)
	{
		$paramsText = "";
		foreach ($params as $key => $value) {
			if($paramsText!=""){
				$paramsText.="&";
			}
			$paramsText.=$key."=".$value;
		}
        if($paramsText!="" && $encodeFlag==1){
            $paramsText = base64_encode($paramsText);
        }
		return $paramsText;
	}
}