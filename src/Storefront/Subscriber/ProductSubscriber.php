<?php declare(strict_types=1);

namespace Myfav\Acl\Storefront\Subscriber;

use Myfav\Acl\Service\AllowProductService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductSubscriber implements EventSubscriberInterface
{
    /**
     * __construct
     */
    public function __construct(
        private readonly AllowProductService $allowProductService)
    {
    }

    /**
     * getSubscribedEvents
     */
    public static function getSubscribedEvents(): array
    {
        return[
            ProductPageLoadedEvent::class => 'onProductPageLoadedEvent'
        ];
    }

    /**
     * onProductPageLoadedEvent
     */
    public function onProductPageLoadedEvent(ProductPageLoadedEvent $event)
    {
        $context = $event->getContext();
        $currentCustomerGroupId = $event->getSalesChannelContext()->getCurrentCustomerGroup()->getId();
        $productId = $event->getPage()->getProduct()->getId();
        
        if(empty($productId)) {
            return;
        }

        $isProductAllowed = $this->allowProductService->isProductAllowedForCustomerGroup(
            $context,
            $productId,
            $currentCustomerGroupId
        );

        if($isProductAllowed !== null && $isProductAllowed === false) {
            throw new NotFoundHttpException();
        }
    }
}
