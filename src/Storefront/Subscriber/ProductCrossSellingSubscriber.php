<?php declare(strict_types=1);

namespace Myfav\Acl\Storefront\Subscriber;

use Shopware\Core\Content\Product\Events\ProductCrossSellingCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductCrossSellingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductCrossSellingCriteriaEvent::class => 'onProductCrossSellingCriteria',
        ];
    }

    public function onProductCrossSellingCriteria(ProductCrossSellingCriteriaEvent $event): void
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