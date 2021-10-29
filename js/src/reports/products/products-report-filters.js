/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { omitBy, isUndefined } from 'lodash';
import { ReportFilters } from '@woocommerce/components';
import {
	getCurrentDates,
	getDateParamsFromQuery,
	isoDateFormat,
} from '@woocommerce/date';
import { ITEMS_STORE_NAME } from '@woocommerce/data';

import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Internal dependencies
 */
import {
	recordDatepickerUpdateEvent,
	recordFilterEvent,
} from '.~/utils/recordEvent';
import { productsFilter, advancedFilters } from './filter-config';

const currency = getSetting( 'currency' );
const siteLocale = getSetting( 'locale' ).siteLocale;

/**
 * Set of filters to be used in Products Report page.
 * Contains date, product with variations pickers.
 *
 * @see https://github.com/woocommerce/woocommerce-admin/blob/main/client/analytics/components/report-filters/index.js
 *
 * @param {Object} props
 * @param {boolean} props.hasPaidSource Indicate whether display report data source selector.
 * @param {Object} props.query Search query object, to fetch filter values from.
 * @param {string} props.trackEventId Report ID used in tracking events.
 */
const ProductsReportFilters = ( props ) => {
	const { hasPaidSource, query, trackEventId } = props;

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

	/**
	 * Record datepicker update tracking event.
	 * Forward all defined data.
	 *
	 * @param {Object} data Data to be forwarded from ReportFilters' date picker.
	 */
	const onDateSelect = ( data ) =>
		recordDatepickerUpdateEvent( {
			report: trackEventId,
			...omitBy( data, isUndefined ),
		} );

	/**
	 * Record filter tracking event.
	 * Forward selected values of products and variations filters.
	 *
	 * @param {Object} data Data to be forwarded from ReportFilters.
	 */
	const onFilterSelect = ( data ) =>
		recordFilterEvent( {
			report: trackEventId,
			filter: data.filter || 'all',
			// wc-admin does not send it
			// https://github.com/woocommerce/woocommerce-admin/issues/6221
			filterVariations: data[ 'filter-variations' ],
		} );

	/**
	 * Fetch given product, for the single product view, to get `is-variable` property, to show vartiation filter if needed.
	 * Logic from https://github.com/woocommerce/woocommerce-admin/blob/087b9fad197c561119960def835650671e6f6e04/client/analytics/report/products/index.js#L144
	 * TODO: Check why it's so disjoint with fetching product data (label) in filter config.
	 * https://github.com/woocommerce/woocommerce-admin/commit/c7de3559d9180cf6ecf9a20b72fc20f0a6658518#diff-28cbd3395bb82ac11cadfbf5c5432ef382c9759b297ddd64ef1b93313bef9e52R129-R156
	 */
	const isVariable = useSelect(
		( select ) => {
			const isSingleProductView =
				! query.search &&
				query.products &&
				query.products.split( ',' ).length === 1;
			if ( ! isSingleProductView ) {
				return false;
			}
			const productId = parseInt( query.products, 10 );
			const includeArgs = { include: productId };

			const { getItems } = select( ITEMS_STORE_NAME );
			// TODO Look at similar usage to populate tags in the Search component.
			const products = getItems( 'products', includeArgs );
			return (
				products &&
				products.get( productId ) &&
				products.get( productId ).type === 'variable'
			);
		},
		[ query.search, query.products ]
	);

	const updatedQuery = {
		...query,
		'is-variable': isVariable,
		hasPaidSource,
	};

	return (
		<ReportFilters
			query={ updatedQuery }
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

export default ProductsReportFilters;
