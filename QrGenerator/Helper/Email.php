<?php
namespace Wow\QrGenerator\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Wow\QrGenerator\Helper\Generator;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $inlineTranslation;
    protected $escaper;
    protected $transportBuilder;
    protected $logger;
    protected $scopeConfig;
    protected $generator;

    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfig,
        Generator $generator
    ) {
        parent::__construct($context);
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->generator = $generator;
        $this->logger = $context->getLogger();
    }

    public function sendEmail($baseUrl, $urlText, $params)
    {

        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/qrcode.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $this->generator->generate($urlText,true);

        $qrCont = '<img src="'.$baseUrl.'qrcode?'.$params.'" alt = "QR Code Image" />';

        try {            
            
            $this->inlineTranslation->suspend();

            // $recipientEmailsString = $this->getCronStatusEmailRecipient();
            $recipientEmailsString = 'rezaarief89@gmail.com';

            $recipientEmailsArray = explode(",",$recipientEmailsString);


            $sender = [
                'name' => $this->escaper->escapeHtml($this->getStorename()),
                'email' => $this->escaper->escapeHtml($this->getStoreEmail()),
            ];

            $templateVars = [
                'qrCont' => $qrCont
            ];
            
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('qr_report_template')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($recipientEmailsArray)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $logger->info("QR Send Email Exception Message : ".$e->getMessage());
            $this->logger->debug($e->getMessage());
        }
    }

    public function getStorename()
    {
        return $this->scopeConfig->getValue(
            'trans_email/ident_general/name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getStoreEmail()
    {
        return $this->scopeConfig->getValue(
            'trans_email/ident_general/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getCronStatusEmailRecipient()
    {
        return $this->scopeConfig->getValue(
            'wowcronjobstatus/general/recipient_email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}