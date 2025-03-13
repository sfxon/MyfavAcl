<?php declare(strict_types=1);

namespace Myfav\Acl;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;

class MyfavAcl extends Plugin {
    /**
     * install
     *
     * @param  InstallContext $installContext
     * @return void
     */
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->installCustomFields($installContext->getContext());
    }

    /**
     * uninstall
     *
     * @param  UninstallContext $context
     * @return void
     */
    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }
    }

    /**
     * update
     *
     * @param  UpdateContext $updateContext
     * @return void
     */
    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
    }

    /**
     * installCustomFields
     *
     * @param  Context $context
     * @return void
     */
    private function installCustomFields(Context $context): void
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $customFieldSetRepository->upsert([
            [
                'name' => 'myfav_acl_product_customer_group',
                'config' => [
                    'label' => [
                        'en-GB' => 'CustomerGroup-Settings',
                        'de-DE' => 'Kundengruppen-Einstellungen'
                    ]
                ],
                'customFields' => [
                    [
                        'name' => 'myfav_acl_product_customer_group_exclude_sitemap',
                        'type' => CustomFieldTypes::BOOL,
                        'config' => [
                            'label' => [
                                'en-GB' => 'Exclude article from sitemap',
                                'de-DE' => 'Artikel von der Sitemap ausschließen'
                            ],
                            'type' => 'checkbox',
                            'componentName' => 'sw-field',
                            'customFieldType' => 'checkbox',
                            'customFieldPosition' => 10,
                        ],
                        'active' => true
                    ],
                ],
            ]
        ], $context);

        $customFieldSetRepository->upsert([
            [
                'name' => 'myfav_acl_category_customer_group',
                'config' => [
                    'label' => [
                        'en-GB' => 'Category-CustomerGroup-Settings',
                        'de-DE' => 'Kategorie-Kundengruppen-Einstellungen'
                    ]
                ],
                'customFields' => [
                    [
                        'name' => 'myfav_acl_category_customer_group_exclude_sitemap',
                        'type' => CustomFieldTypes::BOOL,
                        'config' => [
                            'label' => [
                                'en-GB' => 'Exclude category from sitemap',
                                'de-DE' => 'Kategorie von der Sitemap ausschließen'
                            ],
                            'type' => 'checkbox',
                            'componentName' => 'sw-field',
                            'customFieldType' => 'checkbox',
                            'customFieldPosition' => 10,
                        ],
                        'active' => true
                    ],
                ],
            ]
        ], $context);
    }
}