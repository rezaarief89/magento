<?php

namespace Wow\CmsContentTranslator\Model\DataConverter;

/**
 * Class RendererPool
 */
class Logger
{
   
    public function writeLog($message)
    {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        if(is_array($message)){
            $logger->info(print_r($message,true));
        } else if(is_object($message)){
            $array = json_decode(json_encode($message), true);
            $logger->info(print_r($array,true));
        } else {
            $logger->info($message);
        }
    }
}
