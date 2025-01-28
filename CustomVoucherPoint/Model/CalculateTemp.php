<?php

namespace Fef\CustomVoucherPoint\Model;

use Magento\Framework\Model\AbstractModel;

class CalculateTemp extends AbstractModel
{
    protected function _construct() {
        $this->_init('Fef\CustomVoucherPoint\Model\ResourceModel\CalculateTemp');
    }

}