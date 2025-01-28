<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Wow\Einvoice\Api\Data\EinvoiceInterface;
use Wow\Einvoice\Api\Data\EinvoiceInterfaceFactory;
use Wow\Einvoice\Api\Data\EinvoiceSearchResultsInterfaceFactory;
use Wow\Einvoice\Api\EinvoiceRepositoryInterface;
use Wow\Einvoice\Model\ResourceModel\Einvoice as ResourceEinvoice;
use Wow\Einvoice\Model\ResourceModel\Einvoice\CollectionFactory as EinvoiceCollectionFactory;

class EinvoiceRepository implements EinvoiceRepositoryInterface
{

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var EinvoiceCollectionFactory
     */
    protected $einvoiceCollectionFactory;

    /**
     * @var ResourceEinvoice
     */
    protected $resource;

    /**
     * @var EinvoiceInterfaceFactory
     */
    protected $einvoiceFactory;

    /**
     * @var Einvoice
     */
    protected $searchResultsFactory;


    /**
     * @param ResourceEinvoice $resource
     * @param EinvoiceInterfaceFactory $einvoiceFactory
     * @param EinvoiceCollectionFactory $einvoiceCollectionFactory
     * @param EinvoiceSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceEinvoice $resource,
        EinvoiceInterfaceFactory $einvoiceFactory,
        EinvoiceCollectionFactory $einvoiceCollectionFactory,
        EinvoiceSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->einvoiceFactory = $einvoiceFactory;
        $this->einvoiceCollectionFactory = $einvoiceCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(EinvoiceInterface $einvoice)
    {
        try {
            $this->resource->save($einvoice);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the einvoice: %1',
                $exception->getMessage()
            ));
        }
        return $einvoice;
    }

    /**
     * @inheritDoc
     */
    public function get($einvoiceId)
    {
        $einvoice = $this->einvoiceFactory->create();
        $this->resource->load($einvoice, $einvoiceId);
        if (!$einvoice->getId()) {
            throw new NoSuchEntityException(__('einvoice with id "%1" does not exist.', $einvoiceId));
        }
        return $einvoice;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->einvoiceCollectionFactory->create();
        
        $this->collectionProcessor->process($criteria, $collection);
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        
        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }
        
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(EinvoiceInterface $einvoice)
    {
        try {
            $einvoiceModel = $this->einvoiceFactory->create();
            $this->resource->load($einvoiceModel, $einvoice->getEinvoiceId());
            $this->resource->delete($einvoiceModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the einvoice: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($einvoiceId)
    {
        return $this->delete($this->get($einvoiceId));
    }
}

