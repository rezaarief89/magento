<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Api\Data;

interface EinvoiceInterface
{

    const DOCUMENT_DATE = 'document_date';
    const UNIT_PRICE = 'unit_price';
    const TOTAL_TAX_AMOUNT = 'total_tax_amount';
    const DOCUMENT_NUMBER = 'document_number';
    const ORIGINAL_EINVOICE_REFERENCE_NUMBER = 'original_einvoice_reference_number';
    const DISCOUNT_AMOUNT = 'discount_amount';
    const TAX_RATE = 'tax_rate';
    const INVOICE_CURRENCY_CODE = 'invoice_currency_code';
    const INVOICE_ADDITIONAL_FEE_AMOUNT = 'invoice_additional_fee_amount';
    const TOTAL_EXCLUDING_TAX = 'total_excluding_tax';
    const DESCRIPTION_OF_PRODUCT_OR_SERVICE = 'description_of_product_or_service';
    const LINE_ITEM_TOTAL_EXCLUDING_TAX = 'line_item_total_excluding_tax';
    const PRE_PAYMENT_AMOUNT = 'pre_payment_amount';
    const TOTAL_DISCOUNT_VALUE = 'total_discount_value';
    const DIVISION_CODE = 'division_code';
    const ITEM_TAXABLE_AMOUNT = 'item_taxable_amount';
    const INVOICE_ADDITIONAL_DISCOUNT_AMOUNT = 'invoice_additional_discount_amount';
    const BRANCH_CODE = 'branch_code';
    const QUANTITY = 'quantity';
    const UDF1 = 'udf1';
    const TAX_AMOUNT = 'tax_amount';
    const EINVOICE_TYPE = 'einvoice_type';
    const TOTAL_PAYABLE_AMOUNT = 'total_payable_amount';
    const TOTAL_FEE_OR_CHARGE_AMOUNT = 'total_fee_or_charge_amount';
    const FEE_OR_CHARGE_AMOUNT = 'fee_or_charge_amount';
    const EINVOICE_ID = 'einvoice_id';
    const TOTAL_INCLUDING_TAX = 'total_including_tax';
    const DOCUMENT_TIME = 'document_time';
    const SUBTOTAL = 'subtotal';
    const LINE_ITEM_INCLUDING_TAX = 'line_item_including_tax';
    const TOTAL_NET_AMOUNT = 'total_net_amount';

    /**
     * Get einvoice_id
     * @return string|null
     */
    public function getEinvoiceId();

    /**
     * Set einvoice_id
     * @param string $einvoiceId
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setEinvoiceId($einvoiceId);

    /**
     * Get pre_payment_amount
     * @return string|null
     */
    public function getPrePaymentAmount();

    /**
     * Set pre_payment_amount
     * @param string $prePaymentAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setPrePaymentAmount($prePaymentAmount);

    /**
     * Get document_number
     * @return string|null
     */
    public function getDocumentNumber();

    /**
     * Set document_number
     * @param string $documentNumber
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setDocumentNumber($documentNumber);

    /**
     * Get einvoice_type
     * @return string|null
     */
    public function getEinvoiceType();

    /**
     * Set einvoice_type
     * @param string $einvoiceType
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setEinvoiceType($einvoiceType);

    /**
     * Get division_code
     * @return string|null
     */
    public function getDivisionCode();

    /**
     * Set division_code
     * @param string $divisionCode
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setDivisionCode($divisionCode);

    /**
     * Get branch_code
     * @return string|null
     */
    public function getBranchCode();

    /**
     * Set branch_code
     * @param string $branchCode
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setBranchCode($branchCode);

    /**
     * Get document_date
     * @return string|null
     */
    public function getDocumentDate();

    /**
     * Set document_date
     * @param string $documentDate
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setDocumentDate($documentDate);

    /**
     * Get document_time
     * @return string|null
     */
    public function getDocumentTime();

    /**
     * Set document_time
     * @param string $documentTime
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setDocumentTime($documentTime);

    /**
     * Get original_einvoice_reference_number
     * @return string|null
     */
    public function getOriginalEinvoiceReferenceNumber();

    /**
     * Set original_einvoice_reference_number
     * @param string $originalEinvoiceReferenceNumber
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setOriginalEinvoiceReferenceNumber($originalEinvoiceReferenceNumber);

    /**
     * Get description_of_product_or_service
     * @return string|null
     */
    public function getDescriptionOfProductOrService();

    /**
     * Set description_of_product_or_service
     * @param string $descriptionOfProductOrService
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setDescriptionOfProductOrService($descriptionOfProductOrService);

    /**
     * Get quantity
     * @return string|null
     */
    public function getQuantity();

    /**
     * Set quantity
     * @param string $quantity
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setQuantity($quantity);

    /**
     * Get unit_price
     * @return string|null
     */
    public function getUnitPrice();

    /**
     * Set unit_price
     * @param string $unitPrice
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setUnitPrice($unitPrice);

    /**
     * Get subtotal
     * @return string|null
     */
    public function getSubtotal();

    /**
     * Set subtotal
     * @param string $subtotal
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setSubtotal($subtotal);

    /**
     * Get discount_amount
     * @return string|null
     */
    public function getDiscountAmount();

    /**
     * Set discount_amount
     * @param string $discountAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setDiscountAmount($discountAmount);

    /**
     * Get fee_or_charge_amount
     * @return string|null
     */
    public function getFeeOrChargeAmount();

    /**
     * Set fee_or_charge_amount
     * @param string $feeOrChargeAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setFeeOrChargeAmount($feeOrChargeAmount);

    /**
     * Get line_item_total_excluding_tax
     * @return string|null
     */
    public function getLineItemTotalExcludingTax();

    /**
     * Set line_item_total_excluding_tax
     * @param string $lineItemTotalExcludingTax
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setLineItemTotalExcludingTax($lineItemTotalExcludingTax);

    /**
     * Get item_taxable_amount
     * @return string|null
     */
    public function getItemTaxableAmount();

    /**
     * Set item_taxable_amount
     * @param string $itemTaxableAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setItemTaxableAmount($itemTaxableAmount);

    /**
     * Get tax_rate
     * @return string|null
     */
    public function getTaxRate();

    /**
     * Set tax_rate
     * @param string $taxRate
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setTaxRate($taxRate);

    /**
     * Get tax_amount
     * @return string|null
     */
    public function getTaxAmount();

    /**
     * Set tax_amount
     * @param string $taxAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setTaxAmount($taxAmount);

    /**
     * Get line_item_including_tax
     * @return string|null
     */
    public function getLineItemIncludingTax();

    /**
     * Set line_item_including_tax
     * @param string $lineItemIncludingTax
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setLineItemIncludingTax($lineItemIncludingTax);

    /**
     * Get invoice_additional_discount_amount
     * @return string|null
     */
    public function getInvoiceAdditionalDiscountAmount();

    /**
     * Set invoice_additional_discount_amount
     * @param string $invoiceAdditionalDiscountAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setInvoiceAdditionalDiscountAmount($invoiceAdditionalDiscountAmount);

    /**
     * Get invoice_additional_fee_amount
     * @return string|null
     */
    public function getInvoiceAdditionalFeeAmount();

    /**
     * Set invoice_additional_fee_amount
     * @param string $invoiceAdditionalFeeAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setInvoiceAdditionalFeeAmount($invoiceAdditionalFeeAmount);

    /**
     * Get total_discount_value
     * @return string|null
     */
    public function getTotalDiscountValue();

    /**
     * Set total_discount_value
     * @param string $totalDiscountValue
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setTotalDiscountValue($totalDiscountValue);

    /**
     * Get total_fee_or_charge_amount
     * @return string|null
     */
    public function getTotalFeeOrChargeAmount();

    /**
     * Set total_fee_or_charge_amount
     * @param string $totalFeeOrChargeAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setTotalFeeOrChargeAmount($totalFeeOrChargeAmount);

    /**
     * Get total_net_amount
     * @return string|null
     */
    public function getTotalNetAmount();

    /**
     * Set total_net_amount
     * @param string $totalNetAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setTotalNetAmount($totalNetAmount);

    /**
     * Get total_excluding_tax
     * @return string|null
     */
    public function getTotalExcludingTax();

    /**
     * Set total_excluding_tax
     * @param string $totalExcludingTax
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setTotalExcludingTax($totalExcludingTax);

    /**
     * Get total_tax_amount
     * @return string|null
     */
    public function getTotalTaxAmount();

    /**
     * Set total_tax_amount
     * @param string $totalTaxAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setTotalTaxAmount($totalTaxAmount);

    /**
     * Get total_including_tax
     * @return string|null
     */
    public function getTotalIncludingTax();

    /**
     * Set total_including_tax
     * @param string $totalIncludingTax
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setTotalIncludingTax($totalIncludingTax);

    /**
     * Get total_payable_amount
     * @return string|null
     */
    public function getTotalPayableAmount();

    /**
     * Set total_payable_amount
     * @param string $totalPayableAmount
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setTotalPayableAmount($totalPayableAmount);

    /**
     * Get invoice_currency_code
     * @return string|null
     */
    public function getInvoiceCurrencyCode();

    /**
     * Set invoice_currency_code
     * @param string $invoiceCurrencyCode
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setInvoiceCurrencyCode($invoiceCurrencyCode);

    /**
     * Get udf1
     * @return string|null
     */
    public function getUdf1();

    /**
     * Set udf1
     * @param string $udf1
     * @return \Wow\Einvoice\Einvoice\Api\Data\EinvoiceInterface
     */
    public function setUdf1($udf1);
}

