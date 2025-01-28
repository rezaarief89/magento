<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fef\CustomVoucherPoint\Model\Quote\Address;

use Magento\Framework\DataObject\Copy;
use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory as OrderFactory;

/**
 * Class ToOrder converter
 */
class ToOrder
{
    /**
     * @var Copy
     */
    protected $objectCopyService;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @param OrderFactory $orderFactory
     * @param Copy $objectCopyService
     * @param ManagerInterface $eventManager
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        OrderFactory $orderFactory,
        Copy $objectCopyService,
        ManagerInterface $eventManager,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
    ) {
        $this->orderFactory = $orderFactory;
        $this->objectCopyService = $objectCopyService;
        $this->eventManager = $eventManager;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * @param Address $object
     * @param array $data
     * @return OrderInterface
     */
    public function convert(Address $object, $data = [])
    {
        
        // $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/cart-coupon.log');
        // $logger = new \Zend_Log();
        // $logger->addWriter($writer);
        // $logger->info("convert");

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $voucherCalculateTempFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\CalculateTempFactory');
        $voucherPointUsedFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\VoucherPointUsedFactory');
        $quoteRepository = $objectManager->get('\Magento\Quote\Api\CartRepositoryInterface');
        $zokuRewardQuoteFactory = $objectManager->get('\Zoku\Rewards\Model\ResourceModel\Quote');

        $orderData = $this->objectCopyService->getDataFromFieldset(
            'sales_convert_quote_address',
            'to_order',
            $object
        );

        $zokuRewardQuoteCollection = $zokuRewardQuoteFactory->loadByQuoteId($object->getQuote()->getId());

        $usedPoints = 0;
        if(!empty($zokuRewardQuoteCollection)){
            $usedPoints = $zokuRewardQuoteCollection["reward_points"];
        }

        
        $quote = $quoteRepository->get($object->getQuote()->getId());
        $quoteItems = $quote->getAllItems();
        $totalTax = 0;
        foreach ($quoteItems as $_quoteItem) {
            $quoteItem = $quote->getItemById($_quoteItem->getItemId());
            $totalTax += $quoteItem->getTaxAmount();
        }

        // $logger->info("totalTax : $totalTax");
        

        $voucherCalculateTempCollection = $voucherCalculateTempFactory->create()
        ->getCollection()
        ->addFieldToSelect(array("calculate_result"))
        ->addFieldToFilter('customer_id', $orderData["customer_id"])
        ->addFieldToFilter('quote_id', $object->getQuote()->getId());
        $voucherCalculateTempData = $voucherCalculateTempCollection->getData();

        $voucherUsedCollection = $voucherPointUsedFactory->create()
        ->getCollection()
        ->addFieldToSelect(array("voucher_name"))
        ->addFieldToFilter('customer_id', $orderData["customer_id"])
        ->addFieldToFilter('quote_id', $object->getQuote()->getId());
        $voucherusedData = $voucherUsedCollection->getData();


        foreach ($voucherCalculateTempData as $voucherCalculateTemp) {
            $tempResultArr = json_decode($voucherCalculateTemp["calculate_result"],true);
            
            

            // $order = $objectManager->create('Magento\Sales\Model\Order')->load($orderId);
            // $orderItems = $order->getAllItems();

            $shippingAmount = $orderData["shipping_incl_tax"];
            $orderData["base_subtotal"] =  $tempResultArr['totalNettAmount'] - $totalTax;
            $orderData["subtotal"]= $tempResultArr['totalNettAmount'] - $totalTax;
            $orderData["discount_amount"] = $tempResultArr['totalDiscountAmount'];
            $orderData["discount_invoiced"] = $tempResultArr['totalDiscountAmount'];
            $orderData["base_discount_amount"] = $tempResultArr['totalDiscountAmount'];
            $orderData["base_discount_invoiced"] = $tempResultArr['totalDiscountAmount'];
            $orderData["grand_total"] = $tempResultArr['totalNettAmount'] + $shippingAmount;
            $orderData["base_grand_total"] = $tempResultArr['totalNettAmount'] + $shippingAmount;
            if($usedPoints > 0 && isset($voucherusedData[0]) && $voucherusedData[0]["voucher_name"] != ""){
                $orderData["discount_description"] =  "Voucher and Redeem Point";
            }
            
            $orderData["discount_tax_compensation_amount"] = 0;
            $orderData["base_discount_tax_compensation_amount"] = 0;
            $orderData["shipping_discount_tax_compensation_amount"] = 0;
            $orderData["base_shipping_discount_tax_compensation_amnt"] = 0;

        }


        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $this->orderFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $order,
            array_merge($orderData, $data),
            \Magento\Sales\Api\Data\OrderInterface::class
        );
        $order->setStoreId($object->getQuote()->getStoreId())
            ->setQuoteId($object->getQuote()->getId())
            ->setIncrementId($object->getQuote()->getReservedOrderId());

        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_quote',
            'to_order',
            $object->getQuote(),
            $order
        );
        $this->eventManager->dispatch(
            'sales_convert_quote_to_order',
            ['order' => $order, 'quote' => $object->getQuote()]
        );
        return $order;
    }
}
