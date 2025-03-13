<?php declare(strict_types=1);

namespace Myfav\Acl\Storefront\Subscriber;

use Myfav\Acl\Service\AllowCategoryService;
use Shopware\Core\Content\Category\Event\NavigationLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategorySubscriber implements EventSubscriberInterface
{
    /**
     * __construct
     */
    public function __construct(
        private readonly AllowCategoryService $allowCategoryService,
        private readonly EntityRepository $myfavCategoryAllowCustomerGroup,
        private readonly RequestStack $requestStack,
    )
    {
    }

    /**
     * getSubscribedEvents
     */
    public static function getSubscribedEvents(): array
    {
        return[
            'category.loaded' => 'onCategoryLoaded',
            NavigationPageLoadedEvent::class => 'onNavigationPageLoaded',
            NavigationLoadedEvent::class => 'onNavigationLoaded',
        ];
    }

    /**
     * onCategoryLoaded
     *
     * @param mixed $event
     * @return void
     */
    public function onCategoryLoaded(mixed $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return;
        }

        /** @var SalesChannelContext|null $salesChannelContext */
        $salesChannelContext = $request->attributes->get('sw-sales-channel-context');

        if(!$salesChannelContext) {
            return;
        }

        $currentCustomerGroupId = $salesChannelContext->getCurrentCustomerGroup()->getId();

        $context = $event->getContext();
        $categories = $event->getEntities();

        $criteria = new Criteria();

        $categoryIds = [];

        foreach ($categories as $category) {
            $categoryIds[] = $category->getId();
        }

        if (empty($categoryIds)) {
            return;
        }

        // Produkte mit der Relation nachladen
        $criteria->addFilter(new EqualsAnyFilter('categoryId', $categoryIds));
        $result = $this->myfavCategoryAllowCustomerGroup->searchIds($criteria, $context);
        $categoryCustomerGroupEntries = $result->getIds();

        foreach ($categories as $category) {
            $allowedCustomerGroupIds = [];

            foreach($categoryCustomerGroupEntries as $entry) {
                if($entry['categoryId'] === $category->getId()) {
                    $allowedCustomerGroupIds[] = $entry['customerGroupId'];
                }
            }

            $category->assign([
                'myfavAclAllowedCustomerGroupIds' => $allowedCustomerGroupIds
            ]);

            // Check, if category is visible:
            $categoryIsVisible = true;

            if(count($allowedCustomerGroupIds) > 0) {
                $categoryIsVisible = false;

                foreach($allowedCustomerGroupIds as $tmpGroupId) {
                    if($tmpGroupId === $currentCustomerGroupId) {
                        $categoryIsVisible = true;
                    }
                }
            }

            $category->assign([
                'myfavAclCustomerGroupIsVisible' => $categoryIsVisible
            ]);
        }
    }

    /**
     * onNavigationPageLoaded
     *
     * @param  NavigationPageLoadedEvent $event
     * @return void
     */
    public function onNavigationPageLoaded(NavigationPageLoadedEvent $event): void
    {
        $navigationId = $event->getRequest()->get('navigationId', $event->getSalesChannelContext()->getSalesChannel()->getNavigationCategoryId());
        
        if(empty($navigationId)) {
            return;
        }

        if(!$this->allowCategoryService->isCategoryAllowedForCustomerGroup($navigationId, $event->getSalesChannelContext()) === true) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * onNavigationLoaded
     *
     * @param  NavigationLoadedEvent $event
     * @return void
     */
    public function onNavigationLoaded(NavigationLoadedEvent $event): void
    {
        $navigation = $event->getNavigation();
        $convertedTreeItems = [];

        foreach ($navigation->getTree() as $key => $treeItem) {
            if($this->allowCategoryService->allowCategory($treeItem, $event->getSalesChannelContext()) === false) {
                continue;
            }
            $convertedTreeItems[$key] = $treeItem;
        }

        $navigation->setTree($convertedTreeItems);
    }
}