/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useStoreCurrency from '~/hooks/useStoreCurrency';

const unavailable = __( 'Unavailable', 'google-listings-and-ads' );

/**
 * The organic overview metrics, with a format function for each.
 *
 * Clicks and impressions are whole numbers, CTR is a percentage of the 0–1 fraction Search
 * Console returns, and average position has one decimal. Average position is better when it
 * decreases, so its trend colours are reversed.
 *
 * @return {Array<import('../../index.js').Metric & { reverseTrend?: boolean, labelTooltipText?: string }>}
 *   The metrics, in display order.
 */
const useOrganicOverviewMetrics = () => {
	const { formatNumber } = useStoreCurrency();

	return useMemo( () => {
		const withFormat = ( formatValue ) => {
			return ( value ) => {
				if ( value === undefined || value === null ) {
					return unavailable;
				}

				return formatValue( value );
			};
		};

		return [
			{
				key: 'clicks',
				label: __( 'Clicks', 'google-listings-and-ads' ),
				formatFn: withFormat( ( value ) => formatNumber( value, 0 ) ),
			},
			{
				key: 'impressions',
				label: __( 'Impressions', 'google-listings-and-ads' ),
				formatFn: withFormat( ( value ) => formatNumber( value, 0 ) ),
			},
			{
				key: 'ctr',
				label: __( 'CTR', 'google-listings-and-ads' ),
				formatFn: withFormat(
					( value ) => `${ formatNumber( value * 100, 1 ) }%`
				),
				// `@woocommerce/components`'s Chart computes a Y-axis step that rounds down to
				// exactly 0 whenever every plotted value is below 1 (its quarter-rounding branch
				// in d3chart/utils/scales.js), which then divides into the axis limits and yields
				// a NaN domain -- the chart silently renders nothing. CTR is the only metric here
				// whose values are always a 0-1 fraction, so it's charted as a 0-100 percentage
				// instead to stay out of that broken range; `chartFormatFn` formats that
				// already-scaled value without multiplying by 100 again.
				chartValueScale: 100,
				chartFormatFn: withFormat(
					( value ) => `${ formatNumber( value, 1 ) }%`
				),
			},
			{
				key: 'position',
				label: __( 'Average position', 'google-listings-and-ads' ),
				formatFn: withFormat( ( value ) => formatNumber( value, 1 ) ),
				reverseTrend: true,
				labelTooltipText: __(
					"Your store's average position in Google Search results, where 1 is the topmost position.",
					'google-listings-and-ads'
				),
			},
		];
	}, [ formatNumber ] );
};

export default useOrganicOverviewMetrics;
