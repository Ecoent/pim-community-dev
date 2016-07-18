'use strict';

define([
        'underscore',
        'oro/translator',
        'pim/filter/filter',
        'routing',
        'text!pim/template/filter/product/family',
        'pim/fetcher-registry',
        'pim/user-context',
        'pim/i18n',
        'jquery.select2'
    ], function (_, __, BaseFilter, Routing, template, fetcherRegistry, userContext, i18n) {
    return BaseFilter.extend({
        shortname: 'family',
        config: {},
        template: _.template(template),
        events: {
            'change [name="filter-value"]': 'updateState'
        },

        /**
         * {@inherit}
         */
        initialize: function (config) {
            this.config = config.config;

            this.selectOptions = {
                allowClear: true,
                multiple: true,
                ajax: {
                    url: Routing.generate(this.config.url),
                    quietMillis: 250,
                    cache: true,
                    data: function (term, page) {
                        return {
                            search: term,
                            options: {
                                limit: 20,
                                page: page,
                                locale: userContext.get('uiLocale')
                            }
                        };
                    },
                    results: function (families) {
                        var data = {
                            more: 20 === _.keys(families).length,
                            results: []
                        };
                        _.each(families, function (value, key) {
                            data.results.push({
                                id: key,
                                text: i18n.getLabel(value.labels, userContext.get('uiLocale'), value.code)
                            });
                        });

                        return data;
                    }
                },
                initSelection: function (element, callback) {
                    var families = this.getValue();
                    if (null !== families) {
                        fetcherRegistry.getFetcher('family')
                            .fetchByIdentifiers(families)
                            .then(function (families) {
                                callback(_.map(families, function (family) {
                                    return {
                                        id: family.code,
                                        text: i18n.getLabel(family.labels, userContext.get('uiLocale'), family.code)
                                    };
                                }));
                            });
                    }
                }.bind(this)
            };

            return BaseFilter.prototype.initialize.apply(this, arguments);
        },

        /**
         * {@inherit}
         */
        renderInput: function () {
            return this.template({
                isEditable: this.isEditable(),
                __: __,
                field: this.getField(),
                value: this.getValue(),
                shortname: this.shortname
            });
        },

        /**
         * {@inherit}
         */
        postRender: function () {
            this.$('[name="filter-value"]').select2(this.selectOptions);
        },

        /**
         * {@inherit}
         */
        isEmpty: function () {
            return _.isEmpty(this.getValue());
        },

        /**
         * {@inherit}
         */
        updateState: function () {
            var value = this.$('[name="filter-value"]').val();

            this.setData({
                operator: 'IN',
                value: '' === value ?
                    [] :
                    value.split(',')
            });
        }
    });
});
