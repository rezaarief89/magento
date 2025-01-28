<?php 

namespace Wow\CybersourceOrderReport\Controller\Adminhtml\Report; 

class Index extends \Magento\Backend\App\Action 
{

    protected $resultPageFactory = false;
	
    public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	)
	{
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
	}
	public function execute()
	{
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend((__('Cyber Source Sales Report')));

		return $resultPage;
	}

	protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Wow_CyberReport::report');
    }
}