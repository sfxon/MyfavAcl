<?php declare(strict_types=1);

namespace Myfav\Acl\Service;

use Shopware\Core\Content\Category\Tree\TreeItem;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AllowCategoryService
{
    private $allowedCategoryIds = [];

    public function __construct(private readonly EntityRepository $myfavCategoryAllowCustomerGroupRepository)
    {
    }

    /**
     * isCategoryAllowedForCustomerGroup
     *
     * @param  string $categoryId
     * @param  SalesChannelContext $salesChannelContext
     */
    public function isCategoryAllowedForCustomerGroup(string $categoryId, SalesChannelContext $salesChannelContext)
    {
        $customerGroupId = $salesChannelContext->getCurrentCustomerGroup()->getId();
        $allowedCategoryIds = $this->getAllowedCategoryIdsForCustomerGroupId($customerGroupId, $salesChannelContext->getContext());

        return in_array($categoryId, $allowedCategoryIds) === true;
    }

    /**
     * getAllowedCategoryIdsForCustomerGroupId
     *
     * @param  null|string $customerGroupId
     * @param  Context $context
     * @return array
     */
    public function getAllowedCategoryIdsForCustomerGroupId(?string $customerGroupId, Context $context): array
    {
        if(empty($customerGroupId)) {
            return [];
        }

        if(array_key_exists($customerGroupId, $this->allowedCategoryIds) && $this->allowedCategoryIds[$customerGroupId]) {
            return $this->allowedCategoryIds[$customerGroupId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('customerGroupId', $customerGroupId));

        $allowedCategoryIdsResult = $this->myfavCategoryAllowCustomerGroupRepository->searchIds(
            $criteria,
            $context
        );

        if(empty($allowedCategoryIdsResult->getIds())) {
            return [];
        }

        $allowedCategoryIds = [];

        foreach ($allowedCategoryIdsResult->getIds() as $customerGroupCategory) {
            array_push($allowedCategoryIds, $customerGroupCategory['categoryId']);
        }

        $this->allowedCategoryIds[$customerGroupId] = $allowedCategoryIds;
        return $allowedCategoryIds;
    }

    /**
     * allowCategory
     *
     * @param  TreeItem $treeItem
     * @param  SalesChannelContext $salesChannelContext
     * @return mixed
     */
    public function allowCategory(TreeItem $treeItem, SalesChannelContext $salesChannelContext): mixed
    {
        if($this->isCategoryAllowedForCustomerGroup($treeItem->getCategory()->getId(), $salesChannelContext) === true) {
            $convertedTreeItems = [];

            foreach ($treeItem->getChildren() as $key => $childTreeItem) {
                $childTreeItem = $this->allowCategory($childTreeItem, $salesChannelContext);

                if($childTreeItem === false) {
                    continue;
                }

                $convertedTreeItems[$key] = $childTreeItem;
            }

            $treeItem->setChildren($convertedTreeItems);
            return $treeItem;
        }

        return false;
    }
}
