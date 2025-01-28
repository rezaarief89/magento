<?php

namespace Fef\CustomVoucherPoint\Model\ResourceModel;

class VoucherPoint extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct() 
    {
        $this->_init('proseller_voucher_point', 'id');
    }

}