<?php
namespace Fef\OverrideSummaryCheckout\Model\Total;

use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address\Total;

class Point extends AbstractTotal
{

    private $zoku_point;

    /**
     * Custom constructor.
     */
    public function __construct()
    {
        $this->setCode('zoku_point');
    }

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        
        // $this->zoku_point = $this->getPoint();
        $this->zoku_point = 0;

        $total->setTotalAmount('zoku_point', $this->zoku_point);
        $total->setBaseTotalAmount('zoku_point', $this->zoku_point);
        $total->setZokuPoint($this->zoku_point);
        $total->setBaseZokuPoint($this->zoku_point);

        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        $this->zoku_point = $this->getPoint();
        // $this->zoku_point = 0;

        return [
            'code' => $this->getCode(),
            'title' => 'Zoku Point',
            'value' => $this->zoku_point
        ];
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Zoku Point');
    }

    private function getPoint()
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $CalculateTempFactory = $objectManager->get('\Fef\CustomVoucherPoint\Model\CalculateTempFactory');
        $customerSession = $objectManager->get('\Magento\Customer\Model\Session');
        $checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');

        $CalculateTemp = $CalculateTempFactory->create();
        $customerId = $customerSession->getId();
        // $quoteId1 = $checkoutSession->getQuote()->getId();
        $quoteId = $checkoutSession->getQuoteId();

        // $logger->info("quoteId 1 : $quoteId1");
        // $logger->info("quoteId 2 : $quoteId");

        $CalculateTempCollection = $CalculateTemp
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('quote_id', $quoteId);
        $dataCollection = $CalculateTempCollection->getData();
        $zokuPoint = 0;

        foreach ($dataCollection as $key => $collection) {
            $calculateResult = $collection["calculate_result"];
            $calculateResultDecode = json_decode($calculateResult,true);
            
            // $logger->info(print_r($calculateResultDecode,true));
            
            foreach ($calculateResultDecode["details"] as $detail) {
                if(isset($detail["discountType"]) && $detail["discountType"] == "POINTS"){
                    $zokuPoint += $detail["totalDiscAmount"];
                }
            }
        }

        return $zokuPoint;
    }
}