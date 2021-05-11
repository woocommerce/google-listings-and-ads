/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { getQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	REPORT_SOURCE_PARAM,
	REPORT_SOURCE_PAID,
	REPORT_SOURCE_FREE,
	REPORT_SOURCE_DEFAULT,
} from '.~/constants';
import { freeFields } from '.~/data/utils';
import { getProductLabels, getVariationLabels } from './async-requests';

// XXX: Should we register those filters somewhere?
// XXX: Or maybe YAGNI?
const PRODUCTS_REPORT_FILTERS_FILTER = 'gla_products_report_filters';
const PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER =
	'gla_products_report_advanced_filters';

/**
 * Fallback to default field if the field of the given parameter in URL query
 * doesn't exist in the free field list.
 *
 * @see https://github.com/woocommerce/woocommerce-admin/blob/v2.1.2/packages/components/src/filter-picker/index.js#L143-L145
 *
 * @param  {string} paramName The parameter name to check if needs fallback.
 * @return {string|undefined} Returns `undefined` when fallback.
 *                            Otherwise, returns the current value of the given parameter.
 */
function getFieldWithFallbackCheck( paramName ) {
	const field = getQuery()[ paramName ];
	if ( freeFields.includes( field ) ) {
		return field;
	}
	return undefined;
}

const productsFilterConfig = {
	label: __( 'Show', 'google-listings-and-ads' ),
	staticParams: [
		REPORT_SOURCE_PARAM,
		'chartType',
		'orderby',
		'order',
		'paged',
		'per_page',
		'selectedMetric',
	],
	param: 'filter',
	showFilters: () => true,
	filters: [
		{
			label: __( 'All Products', 'google-listings-and-ads' ),
			value: 'all',
		},
		{
			label: __( 'Single Product', 'google-listings-and-ads' ),
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
								'google-listings-and-ads'
							),
							button: __(
								'Single Product',
								'google-listings-and-ads'
							),
						},
					},
				},
			],
		},
		{
			label: __( 'Comparison', 'google-listings-and-ads' ),
			value: 'compare-products',
			chartMode: 'item-comparison',
			settings: {
				type: 'products',
				param: 'products',
				getLabels: getProductLabels,
				labels: {
					helpText: __(
						'Check at least two products below to compare',
						'google-listings-and-ads'
					),
					placeholder: __(
						'Search for products to compare',
						'google-listings-and-ads'
					),
					title: __( 'Compare Products', 'google-listings-and-ads' ),
					update: __( 'Compare', 'google-listings-and-ads' ),
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
	staticParams: [
		'filter',
		'products',
		'chartType',
		'paged',
		'per_page',
		'selectedMetric',
	],
	param: 'filter-variations',
	filters: [
		{
			label: __( 'All Variations', 'google-listings-and-ads' ),
			chartMode: 'item-comparison',
			value: 'all',
		},
		{
			label: __( 'Single Variation', 'google-listings-and-ads' ),
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
								'google-listings-and-ads'
							),
							button: __(
								'Single Variation',
								'google-listings-and-ads'
							),
						},
					},
				},
			],
		},
		{
			label: __( 'Comparison', 'google-listings-and-ads' ),
			chartMode: 'item-comparison',
			value: 'compare-variations',
			settings: {
				type: 'variations',
				param: 'variations',
				getLabels: getVariationLabels,
				labels: {
					helpText: __(
						'Check at least two variations below to compare',
						'google-listings-and-ads'
					),
					placeholder: __(
						'Search for variations to compare',
						'google-listings-and-ads'
					),
					title: __(
						'Compare Variations',
						'google-listings-and-ads'
					),
					update: __( 'Compare', 'google-listings-and-ads' ),
				},
			},
		},
	],
};

const reportSourceConfig = {
	label: __( 'Show data from', 'google-listings-and-ads' ),
	param: REPORT_SOURCE_PARAM,
	staticParams: [ 'filter', 'products', 'order', 'chartType' ],
	defaultValue: REPORT_SOURCE_DEFAULT,
	filters: [
		{
			value: REPORT_SOURCE_PAID,
			label: __( 'Paid campaigns', 'google-listings-and-ads' ),
			query: {
				get orderby() {
					return getQuery().orderby;
				},
				get selectedMetric() {
					return getQuery().selectedMetric;
				},
			},
		},
		{
			value: REPORT_SOURCE_FREE,
			label: __( 'Free listings', 'google-listings-and-ads' ),
			query: {
				get orderby() {
					return getFieldWithFallbackCheck( 'orderby' );
				},
				get selectedMetric() {
					return getFieldWithFallbackCheck( 'selectedMetric' );
				},
			},
		},
	],
	showFilters: ( { hasPaidSource } ) => hasPaidSource,
};

export const productsFilter = applyFilters( PRODUCTS_REPORT_FILTERS_FILTER, [
	productsFilterConfig,
	variationsConfig,
	reportSourceConfig,
] );

export const advancedFilters = applyFilters(
	PRODUCTS_REPORT_ADVANCED_FILTERS_FILTER,
	{}
);
