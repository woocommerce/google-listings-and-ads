/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import useAdsCurrency from '.~/hooks/useAdsCurrency';

const unavailable = __( 'Unavailable', 'google-listings-and-ads' );

/**
 * A hook to attach format function to each basic metric in given array.
 *
 * This is a dedicated hook for report pages.
 * It loads both currency configs from WooCommerce store and Google ads account.
 * And the attached `formatFn` function returns formatted string according to `metric.isCurrency`.
 *
 * @param {Array<import('./index.js').MetricSchema>} metrics Metric array to be attached format function.
 * @return {Array<import('./index.js').Metric>} Metric array with attached format function.
 */
export default function useMetricsWithFormatter( metrics ) {
	const { formatNumber } = useStoreCurrency();
	const { formatAmount } = useAdsCurrency();

	return useMemo( () => {
		function formatFn( value ) {
			if ( value === undefined ) {
				return unavailable;
			}
			if ( this.isCurrency ) {
				// Use currency code to make sure it's nonambiguous.
				return formatAmount( value, true );
			}
			// All non-currency values on report pages are semantically integer.
			// Avoid confusing information in the presence ('.00') of decimal.
			return formatNumber( value, 0 );
		}

		return metrics.map( ( metric ) => {
			return {
				...metric,
				formatFn,
			};
		} );
	}, [ metrics, formatNumber, formatAmount ] );
}
