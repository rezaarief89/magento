<?php

namespace Fef\CustomVoucherPoint\Model;

use Magento\Framework\Model\AbstractModel;

class VoucherPoint extends AbstractModel
{
    
    protected function _construct() {
        $this->_init('Fef\CustomVoucherPoint\Model\ResourceModel\VoucherPoint');
    }

}