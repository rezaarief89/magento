<?php

namespace Wow\CybersourceOrderReport\Model\ResourceModel;

class ReportTable extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('wow_cybersource_order_report', 'id');
    }

}