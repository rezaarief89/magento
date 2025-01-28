<?php

namespace Wow\CybersourceOrderReport\Model;

use Magento\Framework\Model\AbstractModel;

class ReportTable extends AbstractModel
{
    
    protected function _construct() 
    {
        $this->_init('Wow\CybersourceOrderReport\Model\ResourceModel\ReportTable');
    }

}