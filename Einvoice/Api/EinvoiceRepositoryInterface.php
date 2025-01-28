<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Wow\Einvoice\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface EinvoiceRepositoryInterface
{

    /**
     * Save einvoice
     * @param \Wow\Einvoice\Api\Data\EinvoiceInterface $einvoice
     * @return \Wow\Einvoice\Api\Data\EinvoiceInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Wow\Einvoice\Api\Data\EinvoiceInterface $einvoice
    );

    /**
     * Retrieve einvoice
     * @param string $einvoiceId
     * @return \Wow\Einvoice\Api\Data\EinvoiceInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($einvoiceId);

    /**
     * Retrieve einvoice matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Wow\Einvoice\Api\Data\EinvoiceSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete einvoice
     * @param \Wow\Einvoice\Api\Data\EinvoiceInterface $einvoice
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Wow\Einvoice\Api\Data\EinvoiceInterface $einvoice
    );

    /**
     * Delete einvoice by ID
     * @param string $einvoiceId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($einvoiceId);
}

