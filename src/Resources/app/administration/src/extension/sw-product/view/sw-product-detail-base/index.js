import template from './sw-product-detail-base.html.twig';

const { Mixin } = Shopware;
const { Component } = Shopware;

Component.override('sw-product-detail-base', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    computed: {
        customerGroupRepository() {
            return this.repositoryFactory.create(this.product.extensions.myfavAclAllowCustomerGroup.entity);
        }
    },
});