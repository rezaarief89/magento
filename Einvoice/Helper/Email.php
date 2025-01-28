<?php

namespace Wow\Einvoice\Helper;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $inlineTranslation;
    protected $escaper;
    protected $transportBuilder;
    protected $scopeConfig;
    protected $configHelper;
    protected $statusFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Wow\Einvoice\Helper\Configuration $configHelper,
        \Wow\Einvoice\Model\EinvoiceStatusFactory $statusFactory,
    ) {
        parent::__construct($context);
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->configHelper = $configHelper;
        $this->statusFactory = $statusFactory;
    }

    public function sendEmail($paramsData)
    {       
        // $docType = $paramsData["doc_type"]=="credit_memo" ? "Credit memo" : "Invoice";        

        // $this->configHelper->writeLog($paramsData,"einvoice-email-debug.log");

        try {
            
            $this->inlineTranslation->suspend();

            $recipientEmailsString = $this->configHelper->getEmailRecipient();

            $recipientEmailsArray = explode(",",$recipientEmailsString ?? '');

            $sender = [
                'name' => $this->escaper->escapeHtml($this->getStorename()),
                'email' => $this->escaper->escapeHtml($this->getStoreEmail()),
            ];
            
            $this->configHelper->writeLog($sender,"einvoice-email-debug.log");
            $this->configHelper->writeLog($recipientEmailsArray,"einvoice-email-debug.log");
            
            foreach ($paramsData["items"] as $key => $value)
            {
                $countItem = 0;
                $strMsg = "";

                foreach ($value["items"] as $logKey => $logValue) {
                    $strMsgDecode = json_decode($logValue["message"],true);
                    $strMsg .= "<pre>document number : ".$logValue["doc_increment_id"]."<br/>error details : <br/>".json_encode($strMsgDecode,JSON_PRETTY_PRINT)."</pre>";
                    $countItem ++;
                }

                $emailSubject = str_replace("x",$countItem,$value["subject"]);;
                $templateVars = [
                    'subject' => $emailSubject,
                    'message' => $strMsg
                ];

                $this->configHelper->writeLog($templateVars,"einvoice-email-debug.log");

                $transport = $this->transportBuilder
                    ->setTemplateIdentifier($paramsData["email_template"])
                    ->setTemplateOptions(
                        [
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => 1,
                        ]
                    )
                    ->setTemplateVars($templateVars)
                    ->setFrom($sender)
                    ->addTo($recipientEmailsArray)
                    ->getTransport();

                $transport->sendMessage();
            }
            
            $this->inlineTranslation->resume();

        } catch (\Exception $ex) {
            $this->configHelper->writeLog("Exception : ".$ex->getMessage(),"einvoice-email-debug.log");
        }
    }

    public function prepareSendEmail()
    {
        $statusCollection = $this->statusFactory->create()->getCollection()->addFieldToFilter('status',0);
        $defaultHeader = [
            "email_template" => "einvoice_status_message",
            "items" => []
        ];
        $emailData = $defaultHeader;
        foreach ($statusCollection as $einvoiceStatus) 
        {
            $triedTimes = $einvoiceStatus->getTriedTimes();
            
            if($triedTimes >= 5){
                $docType = $einvoiceStatus->getRequestType();
                if($docType=="credit_memo"){
                    $docType = "Credit memo";
                } elseif($docType=="invoice"){
                    $docType = "Invoice";
                }

                $items[$einvoiceStatus->getRequestType()]["subject"] = "x ".$docType." data failed to sync to BDO";
                $items[$einvoiceStatus->getRequestType()]["items"][] = array(
                    "message" => $einvoiceStatus->getRequestResponse(),
                    "doc_increment_id" => $einvoiceStatus->getRequestIncrementId()
                );
                $emailData["items"] = $items;
            }
            
        }
        return $emailData;
    }

    public function getStorename()
    {
        return $this->scopeConfig->getValue('trans_email/ident_general/name',\Magento\Store\Model\ScopeInterface::SCOPE_STORE, 1);
    }

    public function getStoreEmail()
    {
        return $this->scopeConfig->getValue('trans_email/ident_general/email',\Magento\Store\Model\ScopeInterface::SCOPE_STORE, 1);
    }
}