<?php

namespace Wow\CybersourceOrderReport\Component\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class OrderGrid extends Column
{
    public function __construct(
        ContextInterface $contextInterface,
        UiComponentFactory $componentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($contextInterface, $componentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        $dateFormat = "Y-m-d";
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $date = date($dateFormat, strtotime($item["date"]));
                $item["date"] = $date;
            }
        }
        return $dataSource;
    }
}