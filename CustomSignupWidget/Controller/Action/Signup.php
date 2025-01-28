<?php
namespace Wow\CustomSignupWidget\Controller\Action;

use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;
use Wow\CustomSignupWidget\Model\CustomsignupFactory;
use Magento\Framework\View\Result\PageFactory;

class Signup extends \Magento\Framework\App\Action\Action
{
	protected $storeManager;
	protected $signupFactory;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		StoreManagerInterface $storeManager,
		CustomsignupFactory $signupFactory
	)
	{
		$this->storeManager = $storeManager;
		$this->signupFactory = $signupFactory;
		return parent::__construct($context);
	}

	public function execute()
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$params = $this->getRequest()->getParams();
		$storeId = $this->storeManager->getStore()->getId();
		$baseUrl = $this->storeManager->getStore()->getBaseUrl();
		$redirectUrl = isset($params["redirect_url"]) ? $params["redirect_url"] : "";
		$currentUrl = isset($params["current_url"]) ? $params["current_url"] : "";
		
		$escaper = $objectManager->get(\Magento\Framework\Escaper::class);
		$signupCollection = $this->signupFactory->create()->getCollection()
			->addFieldToFilter('email',$params["email"])
			->addFieldToFilter('page',$currentUrl)
			->addFieldToFilter('store_id',$storeId);

        if(count($signupCollection) == 0){
			$model = $this->signupFactory->create();
			$data = array(
				"store_id" => $storeId,
				"email" => $params["email"],
				"page" => $currentUrl,
				"created_at" => date("Y-m-d H:i:s")
			);
            $model->setData($data)->save();
			$this->messageManager->addSuccessMessage(__('Success register email "%1"',$escaper->escapeHtml($params["email"])));
        }else{
			$this->messageManager->addErrorMessage(__('This email address is already signed up.'));
		}

		$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
		$resultRedirect->setUrl($baseUrl.$redirectUrl);
		
        return $resultRedirect;
	}

	public function writeLog($message, $filename = "reza-test.log")
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/'.$filename);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        if(is_array($message)){
            $logger->info(print_r($message,true));
        } else if(is_object($message)){
            $array = json_decode(json_encode($message), true);
            $logger->info(print_r($array,true));
        } else {
            $logger->info($message);
        }
    }
}