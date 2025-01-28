<?php

namespace Fef\CustomVoucherPoint\Model\ResourceModel;

class CalculateTemp extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct() 
    {
        $this->_init('proseller_calculate_temp', 'id');
    }

}