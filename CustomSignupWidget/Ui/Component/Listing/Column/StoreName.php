<?php

namespace Wow\CustomSignupWidget\Ui\Component\Listing\Column;

class StoreName extends \Magento\Ui\Component\Listing\Columns\Column {

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ){
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeRepository = $objectManager->get('\Magento\Store\Api\StoreRepositoryInterface');
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $store = $storeRepository->getById($item['store_id']);
                $item['store_id'] = $store->getName();
            }
        }

        return $dataSource;
    }
}