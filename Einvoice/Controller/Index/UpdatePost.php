<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Wow\Einvoice\Controller\Index;

use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Post update shopping cart.
 */
class UpdatePost extends \Magento\Checkout\Controller\Cart implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var RequestQuantityProcessor
     */
    private $quantityProcessor;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param RequestQuantityProcessor $quantityProcessor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        RequestQuantityProcessor $quantityProcessor = null
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );

        $this->quantityProcessor = $quantityProcessor ?: $this->_objectManager->get(RequestQuantityProcessor::class);
    }

    /**
     * Empty customer's shopping cart
     *
     * @return void
     */
    protected function _emptyShoppingCart()
    {
        try {
            $this->cart->truncate()->save();
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage($exception, __('We can\'t update the shopping cart.'));
        }
    }

    /**
     * Update customer's shopping cart
     *
     * @return void
     */
    protected function _updateShoppingCart()
    {
        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (is_array($cartData)) {
                if (!$this->cart->getCustomerSession()->getCustomerId() && $this->cart->getQuote()->getCustomerId()) {
                    $this->cart->getQuote()->setCustomerId(null);
                }
                $cartData = $this->quantityProcessor->process($cartData);
                $cartData = $this->cart->suggestItemsQty($cartData);
                $this->cart->updateItems($cartData)->save();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage(
                $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t update the shopping cart.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }
    }

    /**
     * Update shopping cart data action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $updateAction = (string)$this->getRequest()->getParam('update_cart_action');

        switch ($updateAction) {
            case 'empty_cart':
                $this->_emptyShoppingCart();
                break;
            case 'update_qty':
                $this->_updateShoppingCart();
                break;
            default:
                $this->_updateShoppingCart();
        }

        return $this->_goBack();
    }
	
	/**
     * Set back redirect url to response
     *
     * @param null|string $backUrl
     *
     * @return Redirect
     */
    protected function _goBack($backUrl = null)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($backUrl || $backUrl = $this->getBackUrl($this->_redirect->getRefererUrl())) {
            $resultRedirect->setUrl($backUrl);
        }

        return $resultRedirect;
    }
	
	 /**
     * Get resolved back url
     *
     * @param string|null $defaultUrl
     * @return mixed|null|string
     */
    protected function getBackUrl($defaultUrl = null)
    {
        $returnUrl = $this->getRequest()->getParam('return_url');
        if ($returnUrl && $this->_isInternalUrl($returnUrl)) {
            $this->messageManager->getMessages()->clear();
            return $returnUrl;
        }

        if (1 || $this->getRequest()->getParam('in_cart')) {
            if ($this->getRequest()->getActionName() == 'add' && !$this->getRequest()->getParam('in_cart')) {
                $this->_checkoutSession->setContinueShoppingUrl($this->_redirect->getRefererUrl());
            }
            return $this->_url->getUrl('checkout/cart');
        }

            return $this->_url->getUrl('checkout/cart');
    }
}
