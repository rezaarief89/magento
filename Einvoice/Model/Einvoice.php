<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Model;

use Magento\Framework\Model\AbstractModel;
use Wow\Einvoice\Api\Data\EinvoiceInterface;

class Einvoice extends AbstractModel implements EinvoiceInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Wow\Einvoice\Model\ResourceModel\Einvoice::class);
    }

    /**
     * @inheritDoc
     */
    public function getEinvoiceId()
    {
        return $this->getData(self::EINVOICE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEinvoiceId($einvoiceId)
    {
        return $this->setData(self::EINVOICE_ID, $einvoiceId);
    }

    /**
     * @inheritDoc
     */
    public function getPrePaymentAmount()
    {
        return $this->getData(self::PRE_PAYMENT_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setPrePaymentAmount($prePaymentAmount)
    {
        return $this->setData(self::PRE_PAYMENT_AMOUNT, $prePaymentAmount);
    }

    /**
     * @inheritDoc
     */
    public function getDocumentNumber()
    {
        return $this->getData(self::DOCUMENT_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setDocumentNumber($documentNumber)
    {
        return $this->setData(self::DOCUMENT_NUMBER, $documentNumber);
    }

    /**
     * @inheritDoc
     */
    public function getEinvoiceType()
    {
        return $this->getData(self::EINVOICE_TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setEinvoiceType($einvoiceType)
    {
        return $this->setData(self::EINVOICE_TYPE, $einvoiceType);
    }

    /**
     * @inheritDoc
     */
    public function getDivisionCode()
    {
        return $this->getData(self::DIVISION_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setDivisionCode($divisionCode)
    {
        return $this->setData(self::DIVISION_CODE, $divisionCode);
    }

    /**
     * @inheritDoc
     */
    public function getBranchCode()
    {
        return $this->getData(self::BRANCH_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setBranchCode($branchCode)
    {
        return $this->setData(self::BRANCH_CODE, $branchCode);
    }

    /**
     * @inheritDoc
     */
    public function getDocumentDate()
    {
        return $this->getData(self::DOCUMENT_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setDocumentDate($documentDate)
    {
        return $this->setData(self::DOCUMENT_DATE, $documentDate);
    }

    /**
     * @inheritDoc
     */
    public function getDocumentTime()
    {
        return $this->getData(self::DOCUMENT_TIME);
    }

    /**
     * @inheritDoc
     */
    public function setDocumentTime($documentTime)
    {
        return $this->setData(self::DOCUMENT_TIME, $documentTime);
    }

    /**
     * @inheritDoc
     */
    public function getOriginalEinvoiceReferenceNumber()
    {
        return $this->getData(self::ORIGINAL_EINVOICE_REFERENCE_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setOriginalEinvoiceReferenceNumber($originalEinvoiceReferenceNumber)
    {
        return $this->setData(self::ORIGINAL_EINVOICE_REFERENCE_NUMBER, $originalEinvoiceReferenceNumber);
    }

    /**
     * @inheritDoc
     */
    public function getDescriptionOfProductOrService()
    {
        return $this->getData(self::DESCRIPTION_OF_PRODUCT_OR_SERVICE);
    }

    /**
     * @inheritDoc
     */
    public function setDescriptionOfProductOrService($descriptionOfProductOrService)
    {
        return $this->setData(self::DESCRIPTION_OF_PRODUCT_OR_SERVICE, $descriptionOfProductOrService);
    }

    /**
     * @inheritDoc
     */
    public function getQuantity()
    {
        return $this->getData(self::QUANTITY);
    }

    /**
     * @inheritDoc
     */
    public function setQuantity($quantity)
    {
        return $this->setData(self::QUANTITY, $quantity);
    }

    /**
     * @inheritDoc
     */
    public function getUnitPrice()
    {
        return $this->getData(self::UNIT_PRICE);
    }

    /**
     * @inheritDoc
     */
    public function setUnitPrice($unitPrice)
    {
        return $this->setData(self::UNIT_PRICE, $unitPrice);
    }

    /**
     * @inheritDoc
     */
    public function getSubtotal()
    {
        return $this->getData(self::SUBTOTAL);
    }

    /**
     * @inheritDoc
     */
    public function setSubtotal($subtotal)
    {
        return $this->setData(self::SUBTOTAL, $subtotal);
    }

    /**
     * @inheritDoc
     */
    public function getDiscountAmount()
    {
        return $this->getData(self::DISCOUNT_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setDiscountAmount($discountAmount)
    {
        return $this->setData(self::DISCOUNT_AMOUNT, $discountAmount);
    }

    /**
     * @inheritDoc
     */
    public function getFeeOrChargeAmount()
    {
        return $this->getData(self::FEE_OR_CHARGE_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setFeeOrChargeAmount($feeOrChargeAmount)
    {
        return $this->setData(self::FEE_OR_CHARGE_AMOUNT, $feeOrChargeAmount);
    }

    /**
     * @inheritDoc
     */
    public function getLineItemTotalExcludingTax()
    {
        return $this->getData(self::LINE_ITEM_TOTAL_EXCLUDING_TAX);
    }

    /**
     * @inheritDoc
     */
    public function setLineItemTotalExcludingTax($lineItemTotalExcludingTax)
    {
        return $this->setData(self::LINE_ITEM_TOTAL_EXCLUDING_TAX, $lineItemTotalExcludingTax);
    }

    /**
     * @inheritDoc
     */
    public function getItemTaxableAmount()
    {
        return $this->getData(self::ITEM_TAXABLE_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setItemTaxableAmount($itemTaxableAmount)
    {
        return $this->setData(self::ITEM_TAXABLE_AMOUNT, $itemTaxableAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTaxRate()
    {
        return $this->getData(self::TAX_RATE);
    }

    /**
     * @inheritDoc
     */
    public function setTaxRate($taxRate)
    {
        return $this->setData(self::TAX_RATE, $taxRate);
    }

    /**
     * @inheritDoc
     */
    public function getTaxAmount()
    {
        return $this->getData(self::TAX_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTaxAmount($taxAmount)
    {
        return $this->setData(self::TAX_AMOUNT, $taxAmount);
    }

    /**
     * @inheritDoc
     */
    public function getLineItemIncludingTax()
    {
        return $this->getData(self::LINE_ITEM_INCLUDING_TAX);
    }

    /**
     * @inheritDoc
     */
    public function setLineItemIncludingTax($lineItemIncludingTax)
    {
        return $this->setData(self::LINE_ITEM_INCLUDING_TAX, $lineItemIncludingTax);
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceAdditionalDiscountAmount()
    {
        return $this->getData(self::INVOICE_ADDITIONAL_DISCOUNT_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setInvoiceAdditionalDiscountAmount($invoiceAdditionalDiscountAmount)
    {
        return $this->setData(self::INVOICE_ADDITIONAL_DISCOUNT_AMOUNT, $invoiceAdditionalDiscountAmount);
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceAdditionalFeeAmount()
    {
        return $this->getData(self::INVOICE_ADDITIONAL_FEE_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setInvoiceAdditionalFeeAmount($invoiceAdditionalFeeAmount)
    {
        return $this->setData(self::INVOICE_ADDITIONAL_FEE_AMOUNT, $invoiceAdditionalFeeAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTotalDiscountValue()
    {
        return $this->getData(self::TOTAL_DISCOUNT_VALUE);
    }

    /**
     * @inheritDoc
     */
    public function setTotalDiscountValue($totalDiscountValue)
    {
        return $this->setData(self::TOTAL_DISCOUNT_VALUE, $totalDiscountValue);
    }

    /**
     * @inheritDoc
     */
    public function getTotalFeeOrChargeAmount()
    {
        return $this->getData(self::TOTAL_FEE_OR_CHARGE_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalFeeOrChargeAmount($totalFeeOrChargeAmount)
    {
        return $this->setData(self::TOTAL_FEE_OR_CHARGE_AMOUNT, $totalFeeOrChargeAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTotalNetAmount()
    {
        return $this->getData(self::TOTAL_NET_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalNetAmount($totalNetAmount)
    {
        return $this->setData(self::TOTAL_NET_AMOUNT, $totalNetAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTotalExcludingTax()
    {
        return $this->getData(self::TOTAL_EXCLUDING_TAX);
    }

    /**
     * @inheritDoc
     */
    public function setTotalExcludingTax($totalExcludingTax)
    {
        return $this->setData(self::TOTAL_EXCLUDING_TAX, $totalExcludingTax);
    }

    /**
     * @inheritDoc
     */
    public function getTotalTaxAmount()
    {
        return $this->getData(self::TOTAL_TAX_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalTaxAmount($totalTaxAmount)
    {
        return $this->setData(self::TOTAL_TAX_AMOUNT, $totalTaxAmount);
    }

    /**
     * @inheritDoc
     */
    public function getTotalIncludingTax()
    {
        return $this->getData(self::TOTAL_INCLUDING_TAX);
    }

    /**
     * @inheritDoc
     */
    public function setTotalIncludingTax($totalIncludingTax)
    {
        return $this->setData(self::TOTAL_INCLUDING_TAX, $totalIncludingTax);
    }

    /**
     * @inheritDoc
     */
    public function getTotalPayableAmount()
    {
        return $this->getData(self::TOTAL_PAYABLE_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setTotalPayableAmount($totalPayableAmount)
    {
        return $this->setData(self::TOTAL_PAYABLE_AMOUNT, $totalPayableAmount);
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceCurrencyCode()
    {
        return $this->getData(self::INVOICE_CURRENCY_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setInvoiceCurrencyCode($invoiceCurrencyCode)
    {
        return $this->setData(self::INVOICE_CURRENCY_CODE, $invoiceCurrencyCode);
    }

    /**
     * @inheritDoc
     */
    public function getUdf1()
    {
        return $this->getData(self::UDF1);
    }

    /**
     * @inheritDoc
     */
    public function setUdf1($udf1)
    {
        return $this->setData(self::UDF1, $udf1);
    }
}

