<?php declare(strict_types=1);

namespace Myfav\Acl\Storefront\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductSearchSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductSearchCriteriaEvent::class => 'onProductSearchCriteria',
        ];
    }

    public function onProductSearchCriteria(ProductSearchCriteriaEvent $event): void
    {
        $context = $event->getSalesChannelContext();
        $criteria = $event->getCriteria();

        // Aktuelle Customer Group ID abrufen
        $customerGroupId = $context->getCurrentCustomerGroup()->getId();

        if (!$customerGroupId || !Uuid::isValid($customerGroupId)) {
            return;
        }

        dd($customerGroupId);

        // Sicherstellen, dass die ManyToMany-Relation geladen wird
        $criteria->addAssociation('myfavAclAllowCustomerGroup');

        // Filter auf die Kundengruppe setzen
        $criteria->addFilter(
            new EqualsAnyFilter('myfavAclAllowCustomerGroup.id', [$customerGroupId])
        );
    }
}