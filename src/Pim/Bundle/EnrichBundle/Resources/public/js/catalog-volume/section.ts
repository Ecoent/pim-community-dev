import * as Backbone from 'backbone';
import BaseView = require('pimenrich/js/view/base')

const _ = require('underscore');
const __ = require('oro/translator');
const template = require('pim/template/catalog-volume/section');
const requireContext = require('require-context');

interface Templates {
    average_max?: string;
    count?: string;
    [propName: string]: any;
}

interface SectionConfig {
    align: string
    warningText: string
    templates: Templates
    axes: Array<string>
    hint: {
        code: string
        title: string
    },
    title: string
}

interface Axis {
    value: number | object
    has_warning: boolean,
    type: string
}

/**
 * Section view for catalog volume screen
 *
 * @author    Tamara Robichet <tamara.robichet@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class SectionView extends BaseView {
    readonly template = _.template(template)
    public hideHint: any = false

    public config: SectionConfig = {
        align: 'left',
        warningText: __('catalog_volume.axis.warning'),
        templates: {
            average_max: 'pim/template/catalog-volume/average-max',
            count: 'pim/template/catalog-volume/number'
        },
        axes: [],
        hint: {
            code: '',
            title: ''
        },
        title: ''
    }

   public events(): Backbone.EventsHash {
        return {
            'click .AknCatalogVolume-remove': 'closeHint',
            'click .AknCatalogVolume-icon--active': 'closeHint',
            'click .open-hint:not(.AknCatalogVolume-icon--active)': 'openHint'
        }
    }

    /**
     * {@inheritdoc}
     */
    constructor(options: { config: SectionConfig}) {
        super(options);

        this.config = Object.assign({}, this.config, options.config);
        this.hideHint = false;
    }

    /**
     * If the hint key is in localStorage, don't show it on first render
     * @return {Boolean}
     */
    hintIsHidden(): boolean {
        if (false === this.hideHint) return false;

        return !!localStorage.getItem(this.config.hint.code);
    }

    /**
     * Returns true if the section contains data
     *
     * @param sectionData
     * @param sectionAxes
     */
    sectionHasData(sectionData: object, sectionAxes: Array<string>): boolean {
        return Object.keys(sectionData).filter(field => sectionAxes.indexOf(field) > -1).length > 0;
    }

    /**
     * {@inheritdoc}
     */
    render(): BaseView {
        const sectionData = this.getRoot().getFormData();
        const sectionAxes = this.config.axes;
        const sectionHasData = this.sectionHasData(sectionData, sectionAxes);

        if (false === sectionHasData) {
            return this;
        }

        this.$el.empty().html(this.template({
            title: __(this.config.title),
            hintTitle: __(this.config.hint.title),
            hintIsHidden: this.hintIsHidden(),
            align: this.config.align
        }));

        this.renderAxes(this.config.axes, sectionData);

        return this;
    }

    /**
     * Replaces underscores with dashes given a string
     *
     * @param name
     */
    getIconName(name: string): string {
        return name.replace(/_/g, '-');
    }

    /**
     * Generates the html for each axis depending on the type, appends the axis to the axis container
     * @param  {Array} axes An array of field names for each axis
     * @param  {Object} data An object containing data for each axis
     */
    renderAxes(axes: Array<string>, data: any) {
        axes.forEach(name => {
            const axis: Axis = data[name];

            if (undefined === axis) return;

            const typeTemplate: string = this.config.templates[axis.type];

            if (undefined === typeTemplate) {
                throw Error(`The axis ${name} does not have a template for ${axis.type}`);
            }

            const template = _.template(requireContext(typeTemplate));

            const el = template({
                name,
                icon: this.getIconName(name),
                value: axis.value,
                has_warning: axis.has_warning,
                title: __(`catalog_volume.axis.${name}`),
                warningText: this.config.warningText
            });

            this.$('.AknCatalogVolume-axisContainer').append(el);
        });
    }

    /**
     * Close the hint box and store the key in localStorage
     */
    closeHint() {
        localStorage.setItem(this.config.hint.code, '1');
        this.hideHint = true;
        this.render();
    }

    /**
     * Open the hint box
     */
    openHint() {
        this.hideHint = false;
        this.render();
    }
};

export = SectionView;