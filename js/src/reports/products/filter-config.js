/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { getProductLabels, getVariationLabels } from './async-requests';

// XXX: Should we register those filters somewhere?
// XXX: Or maybe YAGNI?
const PRODUCTS_REPORT_FILTERS_FILTER = 'gla_products_report_filters';
const PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER =
	'gla_products_report_advanced_filters';

const productsFilterConfig = {
	label: __( 'Show', 'woocommerce-admin' ),
	staticParams: [ 'chartType', 'paged', 'per_page' ],
	param: 'filter',
	showFilters: () => true,
	filters: [
		{ label: __( 'All Products', 'woocommerce-admin' ), value: 'all' },
		{
			label: __( 'Single Product', 'woocommerce-admin' ),
			value: 'select_product',
			chartMode: 'item-comparison',
			subFilters: [
				{
					component: 'Search',
					// Values casing was unified to kebab case.
					// https://github.com/woocommerce/woocommerce-admin/issues/6237
					value: 'single-product',
					chartMode: 'item-comparison',
					path: [ 'select_product' ],
					settings: {
						type: 'products',
						param: 'products',
						getLabels: getProductLabels,
						labels: {
							placeholder: __(
								'Type to search for a product',
								'woocommerce-admin'
							),
							button: __( 'Single Product', 'woocommerce-admin' ),
						},
					},
				},
			],
		},
		{
			label: __( 'Comparison', 'woocommerce-admin' ),
			value: 'compare-products',
			chartMode: 'item-comparison',
			settings: {
				type: 'products',
				param: 'products',
				getLabels: getProductLabels,
				labels: {
					helpText: __(
						'Check at least two products below to compare',
						'woocommerce-admin'
					),
					placeholder: __(
						'Search for products to compare',
						'woocommerce-admin'
					),
					title: __( 'Compare Products', 'woocommerce-admin' ),
					update: __( 'Compare', 'woocommerce-admin' ),
				},
			},
		},
	],
};

const variationsConfig = {
	// XXX: Why can we get `is-variable` as we do get labels: `getProductLabels`?
	showFilters: ( query ) =>
		query.filter === 'single-product' &&
		!! query.products &&
		query[ 'is-variable' ],
	staticParams: [ 'filter', 'products', 'chartType', 'paged', 'per_page' ],
	param: 'filter-variations',
	filters: [
		{
			label: __( 'All Variations', 'woocommerce-admin' ),
			chartMode: 'item-comparison',
			value: 'all',
		},
		{
			label: __( 'Single Variation', 'woocommerce-admin' ),
			value: 'select_variation',
			subFilters: [
				{
					component: 'Search',
					// Values casing was unified to kebab case.
					// https://github.com/woocommerce/woocommerce-admin/issues/6237
					value: 'single-variation',
					path: [ 'select_variation' ],
					settings: {
						type: 'variations',
						param: 'variations',
						getLabels: getVariationLabels,
						labels: {
							placeholder: __(
								'Type to search for a variation',
								'woocommerce-admin'
							),
							button: __(
								'Single Variation',
								'woocommerce-admin'
							),
						},
					},
				},
			],
		},
		{
			label: __( 'Comparison', 'woocommerce-admin' ),
			chartMode: 'item-comparison',
			value: 'compare-variations',
			settings: {
				type: 'variations',
				param: 'variations',
				getLabels: getVariationLabels,
				labels: {
					helpText: __(
						'Check at least two variations below to compare',
						'woocommerce-admin'
					),
					placeholder: __(
						'Search for variations to compare',
						'woocommerce-admin'
					),
					title: __( 'Compare Variations', 'woocommerce-admin' ),
					update: __( 'Compare', 'woocommerce-admin' ),
				},
			},
		},
	],
};

export const productsFilter = applyFilters( PRODUCTS_REPORT_FILTERS_FILTER, [
	productsFilterConfig,
	variationsConfig,
] );

export const advancedFilters = applyFilters(
	PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER,
	{}
);
