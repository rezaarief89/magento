<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Model\Order\Email\Sender;

use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Email\Container\InvoiceIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\Sender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice as InvoiceResource;
use Magento\Store\Model\App\Emulation;

/**
 * Sends order invoice email to the customer.
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InvoiceSender extends \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
{
   /**
     * Sends order invoice email to the customer.
     *
     * Email will be sent immediately in two cases:
     *
     * - if asynchronous email sending is disabled in global settings
     * - if $forceSyncMode parameter is set to TRUE
     *
     * Otherwise, email will be sent later during running of
     * corresponding cron job.
     *
     * @param Invoice $invoice
     * @param bool $forceSyncMode
     * @return bool
     * @throws \Exception
     */
    public function send(Invoice $invoice, $forceSyncMode = false)
    {
        $this->identityContainer->setStore($invoice->getStore());
        $invoice->setSendEmail($this->identityContainer->isEnabled());

        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            $order = $invoice->getOrder();
            if ($this->checkIfPartialInvoice($order, $invoice)) {
                $order->setBaseSubtotal((float) $invoice->getBaseSubtotal());
                $order->setBaseTaxAmount((float) $invoice->getBaseTaxAmount());
                $order->setBaseShippingAmount((float) $invoice->getBaseShippingAmount());
            }

            $invoiceDate = date("F d, Y");
            if($invoice->getCreatedAt() != NULL && $invoice->getCreatedAt() != ""){
                $invoiceDate = date("F d, Y",strtotime($invoice->getCreatedAt()));
            }
            //$this->appEmulation->startEnvironmentEmulation($order->getStoreId(), Area::AREA_FRONTEND, true);
            $transport = [
                'order' => $order,
                'order_id' => $order->getId(),
				'order_date' => date("F d, Y",strtotime($order->getCreatedAt())),
                'invoice' => $invoice,
                'invoice_id' => $invoice->getId(),
				'invoice_date' => $invoiceDate,
                'comment' => $invoice->getCustomerNoteNotify() ? $invoice->getCustomerNote() : '',
                'billing' => $order->getBillingAddress(),
                'payment_html' => $this->getPaymentHtml($order),
                'store' => $order->getStore(),
                'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
                'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
                'order_data' => [
                    'customer_name' => $order->getCustomerName(),
                    'is_not_virtual' => $order->getIsNotVirtual(),
                    'email_customer_note' => $order->getEmailCustomerNote(),
                    'frontend_status_label' => $order->getFrontendStatusLabel()
                ]
            ];
            $transportObject = new DataObject($transport);
           // $this->appEmulation->stopEnvironmentEmulation();

            /**
             * Event argument `transport` is @deprecated. Use `transportObject` instead.
             */
            $this->eventManager->dispatch(
                'email_invoice_set_template_vars_before',
                ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
            );

            $this->templateContainer->setTemplateVars($transportObject->getData());

            if ($this->checkAndSend($order)) {
                $invoice->setEmailSent(true);
                $this->invoiceResource->saveAttribute($invoice, ['send_email', 'email_sent']);
                return true;
            }
        } else {
            $invoice->setEmailSent(null);
            $this->invoiceResource->saveAttribute($invoice, 'email_sent');
        }

        $this->invoiceResource->saveAttribute($invoice, 'send_email');

        return false;
    }

   /**
     * Check if the order contains partial invoice
     *
     * @param Order $order
     * @param Invoice $invoice
     * @return bool
     */
    private function checkIfPartialInvoice(Order $order, Invoice $invoice): bool
    {
        $totalQtyOrdered = (float) $order->getTotalQtyOrdered();
        $totalQtyInvoiced = (float) $invoice->getTotalQty();
        return $totalQtyOrdered !== $totalQtyInvoiced;
    }
}
