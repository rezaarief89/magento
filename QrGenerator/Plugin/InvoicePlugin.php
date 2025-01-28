<?php
 namespace Wow\QrGenerator\Plugin;


 class InvoicePlugin
 {
     public function beforeSendEmail($subject, $templateVars, $area, $store)
     {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info("beforeSendEmail");


         $templateVars['businessname'] = 'XYZA';
         return [$templateVars, $area, $store];
     }
 }