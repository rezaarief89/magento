<?php

namespace Fef\CustomShipping\Model;

use Magento\Framework\Model\AbstractModel;

class FefToken extends AbstractModel
{
    
    protected function _construct() {
        $this->_init('Fef\CustomShipping\Model\ResourceModel\FefToken');
    }

}