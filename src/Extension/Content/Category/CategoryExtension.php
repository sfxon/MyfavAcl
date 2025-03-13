<?php declare(strict_types=1);

namespace Myfav\Acl\Extension\Content\Category;

use Myfav\Acl\Core\Content\MyfavCategoryAllowCustomerGroup\MyfavCategoryAllowCustomerGroupDefinition;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupDefinition;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class CategoryExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'myfavAclAllowCustomerGroup',
                CustomerGroupDefinition::class,
                MyfavCategoryAllowCustomerGroupDefinition::class,
                'category_id',
                'customer_group_id'
            ))->addFlags(new Inherited(), new ApiAware())
        );
    }

    public function getDefinitionClass(): string
    {
        return CategoryDefinition::class;
    }
}