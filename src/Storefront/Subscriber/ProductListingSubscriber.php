<?php declare(strict_types=1);

namespace Myfav\Acl\Storefront\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductListingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'onProductListingCriteria',
        ];
    }

    public function onProductListingCriteria(ProductListingCriteriaEvent $event): void
    {
        $criteria = $event->getCriteria();
        $context = $event->getSalesChannelContext();

        // Hole die aktuelle Customer Group ID
        $customerGroupId = $context->getCurrentCustomerGroup()->getId();

        if (!$customerGroupId || !Uuid::isValid($customerGroupId)) {
            return;
        }

        $criteria->addAssociation('myfavAclAllowCustomerGroup');

        // Filter auf die ManyToMany-Relation setzen
        $criteria->addFilter(
            new EqualsAnyFilter('myfavAclAllowCustomerGroup.id', [$customerGroupId])
        );
    }
}