<?php

namespace Fef\CustomShipping\Model;

use Magento\Framework\Model\AbstractModel;

class FefRateResult extends AbstractModel
{
    
    protected function _construct() {
        $this->_init('Fef\CustomShipping\Model\ResourceModel\FefRateResult');
    }

}