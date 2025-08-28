/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { Chart } from '@woocommerce/components';
import { getChartTypeForQuery } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import useUrlQuery from '~/hooks/useUrlQuery';
import useStoreCurrency from '~/hooks/useStoreCurrency';

const emptyMessage = __(
	'No data for the selected date range',
	'google-listings-and-ads'
);

/**
 * Utility to format Date as YYYY-MM-DD
 */
function formatDate( date ) {
	const year = date.getFullYear();
	const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
	const day = String( date.getDate() ).padStart( 2, '0' );
	return `${ year }-${ month }-${ day }`;
}

/**
 * Utility to generate all dates between start and end (inclusive)
 */
function generateDateRange( start, end ) {
	const dates = [];
	const current = new Date( start );
	const endDate = new Date( end );

	while ( current <= endDate ) {
		dates.push( formatDate( current ) );
		current.setDate( current.getDate() + 1 );
	}
	return dates;
}

export default function ChartSection( { metrics, loaded, intervals } ) {
	const query = useUrlQuery();
	const storeCurrencyConfig = useStoreCurrency();

	const { selectedMetric } = query;
	let visibleMetric = {};
	if ( metrics.length ) {
		visibleMetric =
			( selectedMetric &&
				metrics.find( ( metric ) => metric.key === selectedMetric ) ) ||
			metrics[ 0 ];
	}

	const { key, label, isCurrency = false, formatFn } = visibleMetric;

	const visibleCurrency = { ...storeCurrencyConfig, symbol: '' };
	const chartType = getChartTypeForQuery( query );
	const valueType = isCurrency ? 'currency' : 'number';
	const localizedFormatFn = formatFn.bind( visibleMetric );

	const chartData = useMemo( () => {
		if ( ! loaded || ! intervals.length ) {
			return [];
		}

		// Map existing intervals for quick lookup
		const intervalMap = intervals.reduce(
			( acc, { interval, subtotals } ) => {
				acc[ interval ] = subtotals;
				return acc;
			},
			{}
		);

		// Generate full date range
		const startDate = intervals[ 0 ].interval;
		const endDate = intervals[ intervals.length - 1 ].interval;
		const allDates = generateDateRange( startDate, endDate );

		// Build chart data ensuring all dates are included
		return allDates.map( ( date ) => {
			const subtotals = intervalMap[ date ] || { [ key ]: 0 };
			return {
				date,
				[ label ]: {
					value: subtotals[ key ] ?? 0,
					label,
				},
			};
		} );
	}, [ key, label, loaded, intervals ] );

	return (
		<Chart
			data={ chartData }
			title={ label }
			query={ query }
			currency={ visibleCurrency }
			chartType={ chartType }
			valueType={ valueType }
			tooltipValueFormat={ localizedFormatFn }
			isRequesting={ ! loaded }
			emptyMessage={ emptyMessage }
			layout="time-comparison"
			legendPosition="hidden"
		/>
	);
}

/**
 * @typedef {import("./index.js").Metric} Metric
 * @typedef {import("./index.js").IntervalsData} IntervalsData
 */
