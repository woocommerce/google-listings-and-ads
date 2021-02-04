/**
 * External dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';

import { omitBy, isUndefined } from 'lodash';
import { ReportFilters } from '@woocommerce/components';
import {
	getCurrentDates,
	getDateParamsFromQuery,
	isoDateFormat,
} from '@woocommerce/date';
import { ITEMS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	recordDatepickerUpdateEvent,
	recordFilterEvent,
} from '../../utils/recordEvent';
import { productsFilter, advancedFilters } from './filter-config';

// TODO: Consider importing it from something like '@woocommerce/wc-admin-settings'.
const currency = wcSettings.currency;
const siteLocale = wcSettings.locale.siteLocale;

/**
 * Set of filters to be used in Products Report page.
 * Contains date, product with variations pickers.
 *
 * @see https://github.com/woocommerce/woocommerce-admin/blob/main/client/analytics/components/report-filters/index.js
 *
 * @param {Object} props
 * @param {Object} props.query Search query object, to fetch filter values from.
 * @param {string} props.report Report ID used in tracking events.
 */
const ProductsReportFilters = ( props ) => {
	const { query, report } = props;

	const { period, compare, before, after } = getDateParamsFromQuery( query );
	const { primary: primaryDate, secondary: secondaryDate } = getCurrentDates(
		query
	);
	const dateQuery = {
		period,
		compare,
		before,
		after,
		primaryDate,
		secondaryDate,
	};

	const onDateSelect = ( data ) =>
		recordDatepickerUpdateEvent( {
			report,
			...omitBy( data, isUndefined ),
		} );

	const onFilterSelect = ( data ) =>
		recordFilterEvent( {
			report,
			filter: data.filter || 'all',
			// wc-admin does not send it
			// https://github.com/woocommerce/woocommerce-admin/issues/6221
			filterVariations: data[ 'filter-variations' ],
		} );

	return (
		<ReportFilters
			query={ query }
			siteLocale={ siteLocale }
			currency={ currency }
			filters={ productsFilter }
			advancedFilters={ advancedFilters }
			onDateSelect={ onDateSelect }
			onFilterSelect={ onFilterSelect }
			dateQuery={ dateQuery }
			isoDateFormat={ isoDateFormat }
		/>
	);
};

/**
 * @type {ProductsReportFilters}
 */
const ProductsReportFiltersWithVariationsData = compose(
	/**
	 * Fetch given product, for the single product view, to get `is-variable` property, to show vartiation filter if needed.
	 * Copied from https://github.com/woocommerce/woocommerce-admin/blob/087b9fad197c561119960def835650671e6f6e04/client/analytics/report/products/index.js#L144
	 * TODO: Check why it's so disjoint with fetching product data (label) in filter config.
	 * https://github.com/woocommerce/woocommerce-admin/commit/c7de3559d9180cf6ecf9a20b72fc20f0a6658518#diff-28cbd3395bb82ac11cadfbf5c5432ef382c9759b297ddd64ef1b93313bef9e52R129-R156
	 */
	withSelect( ( select, props ) => {
		const { query, isRequesting } = props;
		const isSingleProductView =
			! query.search &&
			query.products &&
			query.products.split( ',' ).length === 1;
		if ( isRequesting ) {
			return {
				query: {
					...query,
				},
				isSingleProductView,
				isRequesting,
			};
		}

		const { getItems, isResolving, getItemsError } = select(
			ITEMS_STORE_NAME
		);
		if ( isSingleProductView ) {
			const productId = parseInt( query.products, 10 );
			const includeArgs = { include: productId };
			// TODO Look at similar usage to populate tags in the Search component.
			const products = getItems( 'products', includeArgs );
			const isVariable =
				products &&
				products.get( productId ) &&
				products.get( productId ).type === 'variable';
			const isProductsRequesting = isResolving( 'getItems', [
				'products',
				includeArgs,
			] );
			const isProductsError = Boolean(
				getItemsError( 'products', includeArgs )
			);
			return {
				query: {
					...query,
					'is-variable': isVariable,
				},
				isSingleProductView,
				isRequesting: isProductsRequesting,
				isSingleProductVariable: isVariable,
				isError: isProductsError,
			};
		}

		return {
			query,
			isSingleProductView,
		};
	} )
)( ProductsReportFilters );

export default ProductsReportFiltersWithVariationsData;
