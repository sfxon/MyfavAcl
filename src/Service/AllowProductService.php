<?php declare(strict_types=1);

namespace Myfav\Acl\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
// use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
// use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;

class AllowProductService
{
    // private $allowedProductIds = [];

    public function __construct(
        private readonly EntityRepository $myfavProductAllowCustomerGroupRepository,
        private readonly EntityRepository $productRepository,)
    {
    }

    /**
     * getAssignedCustomerGroups
     *
     * @param  Context $context
     * @param  mixed $productId
     * @return mixed
     */
    public function getAssignedCustomerGroups(Context $context, ?string $productId): mixed
    {
        if(empty($productId)) {
            return null;
        }

        // @var IdSearchResult $productIdSearch
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        $searchResult = $this->myfavProductAllowCustomerGroupRepository->searchIds(
            $criteria,
            $context
        );

        return $searchResult->getIds();
    }

    /**
     * isProductAllowedForCustomerGroup
     *
     * @param  Context $context
     * @param  mixed $productId
     * @param  string $customerGroupId
     * @return mixed
     */
    public function isProductAllowedForCustomerGroup(Context $context, ?string $productId, string $customerGroupId): mixed
    {
        $assignedCustomerGroups = $this->getAssignedCustomerGroups($context, $productId);

        if(null === $assignedCustomerGroups) {
            return null;
        }

        foreach($assignedCustomerGroups as $assignedCustomerGroup) {
            if($assignedCustomerGroup['customerGroupId'] === $customerGroupId) {
                return true;
            }
        }

        return false;
    }

    /*
    public function getAllowedProductIdsByCustomerGroupId(?string $customerGroupId, Context $context): array
    {
        if(empty($customerGroupId)) {
            return [];
        }

        if(array_key_exists($customerGroupId, $this->allowedProductIds) && $this->allowedProductIds[$customerGroupId]) {
            return $this->allowedProductIds[$customerGroupId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerGroupId', $customerGroupId));

        $allowededProductIdsResult = $this->myfavProductAllowCustomerGroupRepository->searchIds(
            $criteria,
            $context
        );

        if(empty($allowededProductIdsResult->getIds())) {
            return [];
        }

        $allowededProductIds = [];

        foreach ($allowededProductIdsResult->getIds() as $customerGroupProduct) {
            $allowededProductIds[] = $customerGroupProduct['productId'];
        }

        $allowededProductIds = $this->getVariantIds($allowededProductIds, $context);
        $this->allowedProductIds[$customerGroupId] = $allowededProductIds;
        return $allowededProductIds;
    }


    public function checkIfNoCustomerGroupsAssigned(?string $productId, Context $context): bool
    {
        if(empty($productId)) {
            return false;
        }

        // @var IdSearchResult $productIdSearch
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        $productIdSearch = $this->myfavProductAllowCustomerGroupRepository->searchIds(
            $criteria,
            $context
        );

        return ($productIdSearch->getTotal() === 0);
    }


    private function getVariantIds(array $productIds, Context $context): array
    {
        if (empty($productIds)) {
            return [];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('parentId', $productIds));

        $variantIds = $this->productRepository->searchIds($criteria, $context);

        if ($variantIds->getTotal() <= 0) {
             return $productIds;
        }

        foreach ($variantIds->getIds() as $productId) {
            array_push($productIds, $productId);
        }

        return $productIds;
    }
    */
}