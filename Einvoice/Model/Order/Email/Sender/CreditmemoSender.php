<?php
namespace Wow\Einvoice\Model\Order\Email\Sender;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Email\Container\CreditmemoIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\Sender;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo as CreditmemoResource;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;

class CreditmemoSender extends \Magento\Sales\Model\Order\Email\Sender\CreditmemoSender{

	public function send(Creditmemo $creditmemo, $forceSyncMode = false)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/einvoice-debug.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("CM ID : ".$creditmemo->getId());

        $this->identityContainer->setStore($creditmemo->getStore());
        $creditmemo->setSendEmail($this->identityContainer->isEnabled());

        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            $order = $creditmemo->getOrder();           
			
			foreach ($order->getInvoiceCollection() as $invoice)
			{
				$_invoice = $invoice;
			}

            $creditMemoDate = date("F d, Y");
            if($creditmemo->getCreatedAt() != NULL && $creditmemo->getCreatedAt() != ""){
                $creditMemoDate = date("F d, Y",strtotime($creditmemo->getCreatedAt()));
            }
		
			$transport = [
                'order' => $order,
                'order_id' => $order->getId(),
				'order_date' => date("F d, Y",strtotime($order->getCreatedAt())),
				'invoice' => ($_invoice !== null) ? $_invoice : "",
				'invoice_date' => ($_invoice !== null) ? date("F d, Y",strtotime($_invoice->getCreatedAt())) : "",
                'creditmemo' => $creditmemo,
				'creditmemo_date' => $creditMemoDate,
                'creditmemo_id' => $creditmemo->getId(),
                'comment' => $creditmemo->getCustomerNoteNotify() ? $creditmemo->getCustomerNote() : '',
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
            //$this->appEmulation->stopEnvironmentEmulation();

            /**
             * Event argument `transport` is @deprecated. Use `transportObject` instead.
             */
            $this->eventManager->dispatch(
                'email_creditmemo_set_template_vars_before',
                ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
            );

            $this->templateContainer->setTemplateVars($transportObject->getData());

            if ($this->checkAndSend($order)) {
                $creditmemo->setEmailSent(true);
                $this->creditmemoResource->saveAttribute($creditmemo, ['send_email', 'email_sent']);
                return true;
            }
        } else {
            $creditmemo->setEmailSent(null);
            $this->creditmemoResource->saveAttribute($creditmemo, 'email_sent');
        }

        $this->creditmemoResource->saveAttribute($creditmemo, 'send_email');

        return false;
    }
}