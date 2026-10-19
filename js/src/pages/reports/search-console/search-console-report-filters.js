/**
 * External dependencies
 */
import { omitBy, isUndefined } from 'lodash';
import {
	getCurrentDates,
	getDateParamsFromQuery,
	isoDateFormat,
} from '@woocommerce/date';
import { getSetting } from '@woocommerce/settings'; // eslint-disable-line import/no-unresolved, @woocommerce/dependency-group
// The above is an unpublished package, delivered with WC, we use Dependency Extraction Webpack Plugin to import it.
// See https://github.com/woocommerce/woocommerce-admin/issues/7781

/**
 * Internal dependencies
 */
import useStoreCurrency from '~/hooks/useStoreCurrency';
import ReportFilters from '~/components/external/woocommerce/filters';
import { recordGlaEvent } from '~/utils/tracks';

const siteLocale = getSetting( 'locale' ).siteLocale;

/**
 * Date range picker, with period comparison, for the Organic search report.
 *
 * @param {Object} props
 * @param {Object} props.query Search query object, to fetch filter values from.
 * @param {string} props.trackEventId Report ID used in tracking events.
 *
 * @fires gla_datepicker_update
 */
const SearchConsoleReportFilters = ( props ) => {
	const { query, trackEventId } = props;

	const { period, compare, before, after } = getDateParamsFromQuery( query );
	const { primary: primaryDate, secondary: secondaryDate } =
		getCurrentDates( query );
	const dateQuery = {
		period,
		compare,
		before,
		after,
		primaryDate,
		secondaryDate,
	};

	const currency = useStoreCurrency();

	/**
	 * Record datepicker update tracking event.
	 * Forward all defined data.
	 *
	 * @param {Object} data Data to be forwarded from ReportFilters' date picker.
	 */
	const handleDateSelect = ( data ) => {
		recordGlaEvent( 'gla_datepicker_update', {
			report: trackEventId,
			...omitBy( data, isUndefined ),
		} );
	};

	return (
		<ReportFilters
			query={ query }
			siteLocale={ siteLocale }
			currency={ currency }
			onDateSelect={ handleDateSelect }
			path={ query.path }
			dateQuery={ dateQuery }
			isoDateFormat={ isoDateFormat }
		/>
	);
};

export default SearchConsoleReportFilters;
