<?php

namespace Fef\CustomVoucherPoint\Controller\Checkout;

use Magento\Framework\App\Action\Context;

class Totals extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJson;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_helper;

    public function __construct(
        Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Json\Helper\Data $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJson
    )
    {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helper;
        $this->_resultJson = $resultJson;
    }

    /**
     * Trigger to re-calculate the collect Totals
     *
     * @return bool
     */
    public function execute()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("totals");

        $response = [
            'errors' => false,
            'message' => 'Re-calculate successful.'
        ];
        try {

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $total = $objectManager->get('\Magento\Quote\Model\Quote\Address\Total');
            $fefHelper = $objectManager->get('\Fef\CustomVoucherPoint\Helper\Data');
            
            
            //Trigger to re-calculate totals
            $payment = $this->_helper->jsonDecode($this->getRequest()->getContent());

            $quote = $this->_checkoutSession->getQuote();
            $quote->getPayment()->setMethod($payment['payment']);
            $quote->collectTotals();


        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'message' => $e->getMessage()
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultJson = $this->_resultJson->create();
        return $resultJson->setData($response);
    }
}