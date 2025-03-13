import template from './sw-category-detail-base.html.twig';

const { Mixin } = Shopware;
const { Component } = Shopware;

Component.override('sw-category-detail-base', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder')
    ],

    computed: {
        customerGroupRepository() {
            return this.repositoryFactory.create(this.category.extensions.myfavAclAllowCustomerGroup.entity);
        }
    },
});
