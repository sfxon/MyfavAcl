const { Component } = Shopware;

Component.override('sw-category-detail', {
    computed: {
        categoryCriteria() {
            const criteria = this.$super('categoryCriteria');
            criteria.addAssociation('myfavAclAllowCustomerGroup');
            return criteria;
        }
    }
});
