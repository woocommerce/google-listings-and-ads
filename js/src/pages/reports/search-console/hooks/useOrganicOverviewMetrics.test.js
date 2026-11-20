/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useOrganicOverviewMetrics from './useOrganicOverviewMetrics';

jest.mock( '~/hooks/useStoreCurrency', () => ( {
	__esModule: true,
	default: () => ( {
		formatNumber: ( value, precision ) => {
			return Number( value ).toFixed( precision );
		},
	} ),
} ) );

const getMetric = ( metrics, key ) => {
	return metrics.find( ( metric ) => metric.key === key );
};

describe( 'useOrganicOverviewMetrics', () => {
	it( 'lists clicks, impressions, CTR and average position, in that order', () => {
		const { result } = renderHook( () => useOrganicOverviewMetrics() );

		expect( result.current.map( ( { label } ) => label ) ).toEqual( [
			'Clicks',
			'Impressions',
			'CTR',
			'Average position',
		] );
	} );

	it( 'formats each metric', () => {
		const { result } = renderHook( () => useOrganicOverviewMetrics() );

		expect( getMetric( result.current, 'clicks' ).formatFn( 1234 ) ).toBe(
			'1234'
		);
		expect(
			getMetric( result.current, 'impressions' ).formatFn( 48213 )
		).toBe( '48213' );
		expect( getMetric( result.current, 'ctr' ).formatFn( 0.038 ) ).toBe(
			'3.8%'
		);
		expect(
			getMetric( result.current, 'position' ).formatFn( 14.73 )
		).toBe( '14.7' );
	} );

	it( 'shows the value as unavailable when there is none', () => {
		const { result } = renderHook( () => useOrganicOverviewMetrics() );

		result.current.forEach( ( { formatFn } ) => {
			expect( formatFn( undefined ) ).toBe( 'Unavailable' );
			expect( formatFn( null ) ).toBe( 'Unavailable' );
		} );
	} );

	it( 'scales CTR for charting, and formats the already-scaled value without multiplying again', () => {
		const { result } = renderHook( () => useOrganicOverviewMetrics() );
		const ctr = getMetric( result.current, 'ctr' );

		// The raw value (as `formatFn` expects) is a 0-1 fraction; the charted value is that
		// fraction already multiplied by `chartValueScale`, so the same real CTR is formatted
		// consistently by both: formatFn(0.038) and chartFormatFn(0.038 * chartValueScale).
		expect( ctr.chartValueScale ).toBe( 100 );
		expect( ctr.chartFormatFn( 0.038 * ctr.chartValueScale ) ).toBe(
			'3.8%'
		);
	} );

	it( 'leaves clicks, impressions and position unscaled for charting', () => {
		const { result } = renderHook( () => useOrganicOverviewMetrics() );

		result.current
			.filter( ( { key } ) => key !== 'ctr' )
			.forEach( ( metric ) => {
				expect( metric.chartValueScale ).toBeUndefined();
				expect( metric.chartFormatFn ).toBeUndefined();
			} );
	} );

	it( 'reverses the trend colours and explains average position only', () => {
		const { result } = renderHook( () => useOrganicOverviewMetrics() );
		const position = getMetric( result.current, 'position' );

		expect( position.reverseTrend ).toBe( true );
		expect( position.labelTooltipText ).toBe(
			"Your store's average position in Google Search results, where 1 is the topmost position."
		);
		result.current
			.filter( ( { key } ) => key !== 'position' )
			.forEach( ( metric ) => {
				expect( metric.reverseTrend ).toBeUndefined();
				expect( metric.labelTooltipText ).toBeUndefined();
			} );
	} );
} );
