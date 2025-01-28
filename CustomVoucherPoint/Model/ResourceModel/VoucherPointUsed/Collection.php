<?php

namespace Fef\CustomVoucherPoint\Model\ResourceModel\VoucherPointUsed;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define collecion model
     */
    protected function _construct() {
        $this->_init(
        	'Fef\CustomVoucherPoint\Model\VoucherPointUsed',
        	'Fef\CustomVoucherPoint\Model\ResourceModel\VoucherPointUsed'
        	);
    }
}