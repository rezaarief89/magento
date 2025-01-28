<?php

namespace Fef\CustomShipping\Model;

use \Magento\Checkout\Model\ConfigProviderInterface;

class AdditionalConfigVars implements ConfigProviderInterface
{
   public function getConfig()
   {
        $writer = new \Zend_Log_Writer_Stream(BP.'/var/log/reza-test.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();   
        $smartoscHelper = $objectManager->get('\Smartosc\Checkout\Helper\Data');
        
        $blockDays = $smartoscHelper->getBlockDays();
        $additionalVariables['block_days'] = $blockDays;
      //   $logger->info("block_days : ".$blockDays);
        return $additionalVariables;
   }
}