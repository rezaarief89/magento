<?php

namespace Wow\Einvoice\Ui\Component\Listing\Column;

class DocumentDate extends \Magento\Ui\Component\Listing\Columns\Column {

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ){
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource) {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item['document_date'] = date("d/m/Y",strtotime($item['document_date'])); //Here you can do anything with actual data
            }
        }

        return $dataSource;
    }
}