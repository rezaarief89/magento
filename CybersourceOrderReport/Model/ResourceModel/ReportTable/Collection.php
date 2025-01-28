<?php

namespace Wow\CybersourceOrderReport\Model\ResourceModel\ReportTable;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    protected function _construct() 
    {
        $this->_init(
        	'Wow\CybersourceOrderReport\Model\ReportTable',
        	'Wow\CybersourceOrderReport\Model\ResourceModel\ReportTable'
        );
    }
}