<?php

namespace Wow\CybersourceOrderReport\Controller\Adminhtml\Report;

use Magento\Framework\Controller\ResultFactory;

class Reload extends \Magento\Backend\App\Action
{


    protected $helper;

    public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Wow\CybersourceOrderReport\Helper\Data $helper
	)
	{
		parent::__construct($context);
		$this->helper = $helper;
	}

    public function execute()
    {

        try {
            $this->helper->publish();
            $this->messageManager->addSuccess(__('Message is added to queue, wait to get your current data soon'));
        } catch (\Exception $ex) {
            $this->messageManager->addError(__('Failed reload report data : '.$ex->getMessage()));
        }
        

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('wow_cyber/report/index');
    }
}