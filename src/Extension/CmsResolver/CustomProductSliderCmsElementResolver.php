<?php

namespace Myfav\Acl\Extension\CmsResolver;

use Myfav\Acl\Service\AllowProductService;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\CmsElementResolverInterface;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductSliderStruct;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomProductSliderCmsElementResolver extends AbstractCmsElementResolver
{
    private const PRODUCT_SLIDER_ENTITY_FALLBACK = 'product-slider-entity-fallback';
    private const STATIC_SEARCH_KEY = 'product-slider';
    private const FALLBACK_LIMIT = 50;

    public function __construct(
        private readonly AllowProductService $allowProductService,
        private readonly CmsElementResolverInterface $elementResolver,
        private readonly ProductStreamBuilderInterface $productStreamBuilder,
        private readonly SystemConfigService $systemConfigService)
    {
    }

    public function getType(): string
    {
        return $this->elementResolver->getType();
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $config = $slot->getFieldConfig();
        $collection = new CriteriaCollection();

        $products = $config->get('products');

        if ($products === null) {
            return null;
        }

        if ($products->isStatic() && $products->getValue()) {
            $criteria = new Criteria($products->getArrayValue());
            $criteria->addAssociation('cover');
            $criteria->addAssociation('options.group');
            $criteria->addAssociation('manufacturer');
            $collection->add(self::STATIC_SEARCH_KEY . '_' . $slot->getUniqueIdentifier(), ProductDefinition::class, $criteria);
        }

        if ($products->isMapped() && $products->getValue() && $resolverContext instanceof EntityResolverContext) {
            $criteria = $this->collectByEntity($resolverContext, $products);
            if ($criteria !== null) {
                $collection->add(self::PRODUCT_SLIDER_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier(), ProductDefinition::class, $criteria);
            }
        }

        if ($products->isProductStream() && $products->getValue()) {
            $criteria = $this->collectByProductStream($resolverContext, $products, $config);
            $collection->add(self::PRODUCT_SLIDER_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier(), ProductDefinition::class, $criteria);
        }

        return $collection->all() ? $collection : null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $config = $slot->getFieldConfig();
        $slider = new ProductSliderStruct();
        $slot->setData($slider);

        $productConfig = $config->get('products');

        if ($productConfig === null) {
            return;
        }

        if ($productConfig->isStatic()) {
            $this->enrichFromSearch($slider, $result, self::STATIC_SEARCH_KEY . '_' . $slot->getUniqueIdentifier(), $resolverContext->getSalesChannelContext());
        }

        if ($productConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
            $products = $this->resolveEntityValue($resolverContext->getEntity(), $productConfig->getStringValue());

            if ($products === null) {
                $this->enrichFromSearch($slider, $result, self::PRODUCT_SLIDER_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier(), $resolverContext->getSalesChannelContext());
            } else {
                $slider->setProducts($products);
            }
        }

        if ($productConfig->isProductStream() && $productConfig->getValue()) {
            $entitySearchResult = $result->get(self::PRODUCT_SLIDER_ENTITY_FALLBACK . '_' . $slot->getUniqueIdentifier());

            if ($entitySearchResult === null) {
                return;
            }

            $streamResult = $entitySearchResult->getEntities();
            if (!$streamResult instanceof ProductCollection) {
                return;
            }

            $finalProducts = $this->handleProductStream($streamResult);

            $slider->setProducts($finalProducts);
            $slider->setStreamId($productConfig->getStringValue());
        }
    }

    private function enrichFromSearch(ProductSliderStruct $slider, ElementDataCollection $result, string $searchKey, SalesChannelContext $saleschannelContext): void
    {
        $searchResult = $result->get($searchKey);
        if ($searchResult === null) {
            return;
        }

        $products = $searchResult->getEntities();
        if (!$products instanceof ProductCollection) {
            return;
        }

        if ($this->systemConfigService->get('core.listing.hideCloseoutProductsWhenOutOfStock', $saleschannelContext->getSalesChannel()->getId())) {
            $products = $this->filterOutOutOfStockHiddenCloseoutProducts($products);
        }

        /* Filter out products, that should not be visible, because they are under ACL. */
        $context = $saleschannelContext->getContext();
        $currentCustomerGroupId = $saleschannelContext->getCurrentCustomerGroup()->getId();

        $finalProducts = new \Shopware\Core\Content\Product\ProductCollection();

        foreach($products as $product) {
            $productId = $product->getId();
            
            $isProductAllowed = $this->allowProductService->isProductAllowedForCustomerGroup(
                $context,
                $productId,
                $currentCustomerGroupId
            );

            if($isProductAllowed !== null && $isProductAllowed === false) {
            } else {
                $finalProducts->add($product);
            }
        }

        /* Continue with original code. */
        $slider->setProducts($finalProducts);
    }

    private function filterOutOutOfStockHiddenCloseoutProducts(ProductCollection $products): ProductCollection
    {
        return $products->filter(function (ProductEntity $product) {
            if ($product->getIsCloseout() && $product->getAvailableStock() <= 0) {
                return false;
            }

            return true;
        });
    }

    private function collectByEntity(EntityResolverContext $resolverContext, FieldConfig $config): ?Criteria
    {
        $entityProducts = $this->resolveEntityValue($resolverContext->getEntity(), $config->getStringValue());
        if ($entityProducts) {
            return null;
        }

        $criteria = $this->resolveCriteriaForLazyLoadedRelations($resolverContext, $config);
        if ($criteria === null) {
            return null;
        }

        $criteria->addAssociation('cover');
        $criteria->addAssociation('options.group');
        $criteria->addAssociation('manufacturer');

        return $criteria;
    }

    private function collectByProductStream(ResolverContext $resolverContext, FieldConfig $config, FieldConfigCollection $elementConfig): Criteria
    {
        $filters = $this->productStreamBuilder->buildFilters(
            $config->getStringValue(),
            $resolverContext->getSalesChannelContext()->getContext()
        );

        $sorting = 'name:' . FieldSorting::ASCENDING;
        $productStreamSorting = $elementConfig->get('productStreamSorting');
        if ($productStreamSorting !== null) {
            $sorting = $productStreamSorting->getStringValue();
        }
        $limit = self::FALLBACK_LIMIT;
        $productStreamLimit = $elementConfig->get('productStreamLimit');
        if ($productStreamLimit !== null) {
            $limit = $productStreamLimit->getIntValue();
        }

        $criteria = new Criteria();
        $criteria->addFilter(...$filters);
        $criteria->setLimit($limit);
        $criteria->addAssociation('options.group');
        $criteria->addAssociation('manufacturer');

        if ($sorting === 'random') {
            return $this->addRandomSort($criteria);
        }

        if ($sorting) {
            $sorting = explode(':', $sorting);
            $field = $sorting[0];
            $direction = $sorting[1];

            $criteria->addSorting(new FieldSorting($field, $direction));
        }

        return $criteria;
    }

    private function addRandomSort(Criteria $criteria): Criteria
    {
        $fields = [
            'id',
            'stock',
            'releaseDate',
            'manufacturer.id',
            'unit.id',
            'tax.id',
            'cover.id',
        ];
        shuffle($fields);
        $fields = \array_slice($fields, 0, 2);
        $direction = [FieldSorting::ASCENDING, FieldSorting::DESCENDING];
        $direction = $direction[random_int(0, 1)];
        foreach ($fields as $field) {
            $criteria->addSorting(new FieldSorting($field, $direction));
        }

        return $criteria;
    }

    private function handleProductStream(ProductCollection $streamResult): ProductCollection
    {
        $finalProducts = new ProductCollection();

        foreach ($streamResult as $product) {
            $variantConfig = $product->getVariantListingConfig();

            if (!$variantConfig) {
                $finalProducts->add($product);
                continue;
            }

            $idToFetch = $variantConfig->getDisplayParent() ? $product->getParentId() : $variantConfig->getMainVariantId();

            if ($idToFetch === null) {
                continue;
            }

            $productToAdd = $streamResult->get($idToFetch);

            if (!$productToAdd) {
                continue;
            }

            $finalProducts->add($productToAdd);
        }

        return $finalProducts;
    }
}