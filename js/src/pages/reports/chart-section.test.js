/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { Chart } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import ChartSection from './chart-section';

// Chart is mocked -- these only verify the props built for it, not its real rendering.
jest.mock( '@woocommerce/components', () => ( {
	Chart: jest.fn( () => null ).mockName( 'Chart' ),
} ) );

jest.mock( '@woocommerce/date', () => ( {
	getChartTypeForQuery: () => 'line',
} ) );

jest.mock( '~/hooks/useUrlQuery', () => ( {
	__esModule: true,
	default: () => ( { selectedMetric: 'clicks' } ),
} ) );

jest.mock( '~/hooks/useStoreCurrency', () => ( {
	__esModule: true,
	default: () => ( { code: 'USD', symbol: '$' } ),
} ) );

const metrics = [
	{
		key: 'clicks',
		label: 'Clicks',
		formatFn: ( value ) => {
			return `${ value } clicks`;
		},
	},
];

// A CTR-shaped metric: its real values are always a 0-1 fraction.
const ctrMetrics = [
	{
		key: 'ctr',
		label: 'CTR',
		formatFn: ( value ) => `${ value * 100 } raw%`,
	},
];

const getChartProps = () => {
	return Chart.mock.calls[ Chart.mock.calls.length - 1 ][ 0 ];
};

describe( 'ChartSection', () => {
	beforeEach( () => {
		Chart.mockClear();
	} );

	describe( 'without a comparison period', () => {
		it( 'plots one series, filling in missing days with 0, with the legend hidden', () => {
			render(
				<ChartSection
					metrics={ metrics }
					loaded
					intervals={ [
						{ interval: '2026-10-01', subtotals: { clicks: 3 } },
						{ interval: '2026-10-03', subtotals: { clicks: 5 } },
					] }
				/>
			);

			const props = getChartProps();
			expect( props.data ).toEqual( [
				{ date: '2026-10-01', Clicks: { value: 3, label: 'Clicks' } },
				{ date: '2026-10-02', Clicks: { value: 0, label: 'Clicks' } },
				{ date: '2026-10-03', Clicks: { value: 5, label: 'Clicks' } },
			] );
			expect( props.legendPosition ).toBe( 'hidden' );
			expect( props.allowedIntervals ).toBeUndefined();
			expect( props.legendTotals ).toBeUndefined();
		} );

		it( 'plots nothing until the data is loaded', () => {
			render(
				<ChartSection
					metrics={ metrics }
					loaded={ false }
					intervals={ [] }
				/>
			);

			expect( getChartProps().data ).toEqual( [] );
			expect( getChartProps().isRequesting ).toBe( true );
		} );

		it( 'scales an all-sub-1 metric up by a power of 10 before plotting, and formats back down', () => {
			render(
				<ChartSection
					metrics={ ctrMetrics }
					loaded
					intervals={ [
						{ interval: '2026-10-01', subtotals: { ctr: 0.05 } },
					] }
				/>
			);

			// 0.05 × 1000 = 50 clears the ≥10 floor; formatFn divides back down to format it.
			const props = getChartProps();
			expect( props.data ).toEqual( [
				{ date: '2026-10-01', CTR: { value: 50, label: 'CTR' } },
			] );
			expect( props.tooltipValueFormat( 50 ) ).toBe( '5 raw%' );
		} );

		it( 'leaves an already-safe metric unscaled', () => {
			render(
				<ChartSection
					metrics={ metrics }
					loaded
					intervals={ [
						{ interval: '2026-10-01', subtotals: { clicks: 30 } },
					] }
				/>
			);

			expect( getChartProps().data ).toEqual( [
				{ date: '2026-10-01', Clicks: { value: 30, label: 'Clicks' } },
			] );
		} );
	} );

	describe( 'with a comparison period', () => {
		const comparison = {
			intervals: [
				{ interval: '2025-10-01', subtotals: { clicks: 2 } },
				{ interval: '2025-10-02', subtotals: { clicks: 4 } },
			],
			totals: { clicks: { value: 8, prevValue: 6, delta: 33.33 } },
			labels: { primary: 'Month to date', secondary: 'Previous year' },
		};

		it( 'plots both periods aligned by position, with their totals in the legend', () => {
			render(
				<ChartSection
					metrics={ metrics }
					loaded
					intervals={ [
						{ interval: '2026-10-01', subtotals: { clicks: 3 } },
						{ interval: '2026-10-02', subtotals: { clicks: 5 } },
					] }
					comparison={ comparison }
					intervalUnit="day"
					allowedIntervals={ [ 'day', 'week' ] }
				/>
			);

			const props = getChartProps();
			expect( props.data ).toEqual( [
				{
					date: '2026-10-01T00:00:00',
					primary: {
						label: 'Month to date',
						value: 3,
						labelDate: '2026-10-01T00:00:00',
					},
					secondary: {
						label: 'Previous year',
						value: 2,
						labelDate: '2025-10-01T00:00:00',
					},
				},
				{
					date: '2026-10-02T00:00:00',
					primary: {
						label: 'Month to date',
						value: 5,
						labelDate: '2026-10-02T00:00:00',
					},
					secondary: {
						label: 'Previous year',
						value: 4,
						labelDate: '2025-10-02T00:00:00',
					},
				},
			] );
			expect( props.legendPosition ).toBeUndefined();
			expect( props.legendTotals ).toEqual( {
				primary: 8,
				secondary: 6,
			} );
			expect( props.interval ).toBe( 'day' );
			expect( props.allowedIntervals ).toEqual( [ 'day', 'week' ] );
			expect( props.yFormat( 8 ) ).toBe( '8 clicks' );
		} );

		it( 'plots both periods as given, without filling in missing days', () => {
			render(
				<ChartSection
					metrics={ metrics }
					loaded
					intervals={ [
						{ interval: '2026-10-01', subtotals: { clicks: 3 } },
						{ interval: '2026-10-03', subtotals: { clicks: 5 } },
					] }
					comparison={ {
						...comparison,
						intervals: [
							{
								interval: '2025-10-01',
								subtotals: { clicks: 1 },
							},
						],
					} }
					intervalUnit="day"
					allowedIntervals={ [ 'day', 'week' ] }
				/>
			);

			const { data } = getChartProps();
			expect( data.map( ( { date } ) => date ) ).toEqual( [
				'2026-10-01T00:00:00',
				'2026-10-03T00:00:00',
			] );
			expect( data[ 1 ].secondary ).toEqual( {
				label: 'Previous year',
				value: 0,
				labelDate: undefined,
			} );
		} );

		it( "scales an all-sub-1 metric by the smaller period's max, with headroom for either alone", () => {
			render(
				<ChartSection
					metrics={ ctrMetrics }
					loaded
					intervals={ [
						{ interval: '2026-10-01', subtotals: { ctr: 0.05 } },
					] }
					comparison={ {
						intervals: [
							{
								interval: '2025-10-01',
								subtotals: { ctr: 0.02 },
							},
						],
						totals: {
							ctr: { value: 0.05, prevValue: 0.02 },
						},
						labels: {
							primary: 'Month to date',
							secondary: 'Previous year',
						},
					} }
				/>
			);

			// The smaller max (0.02) sets the scale here, not the larger one (0.05).
			const props = getChartProps();
			expect( props.data ).toEqual( [
				{
					date: '2026-10-01T00:00:00',
					primary: {
						label: 'Month to date',
						value: 50,
						labelDate: '2026-10-01T00:00:00',
					},
					secondary: {
						label: 'Previous year',
						value: 20,
						labelDate: '2025-10-01T00:00:00',
					},
				},
			] );
			expect( props.legendTotals ).toEqual( {
				primary: 50,
				secondary: 20,
			} );
			expect( props.yFormat( 50 ) ).toBe( '5 raw%' );
			expect( props.yBelow1Format( 20 ) ).toBe( '2 raw%' );
		} );

		it( 'scales from the smaller period alone when the two periods are wildly lopsided', () => {
			render(
				<ChartSection
					metrics={ ctrMetrics }
					loaded
					intervals={ [
						{ interval: '2026-10-01', subtotals: { ctr: 0.05 } },
					] }
					comparison={ {
						intervals: [
							{
								interval: '2025-10-01',
								subtotals: { ctr: 0.0001 },
							},
						],
						totals: {
							ctr: { value: 0.05, prevValue: 0.0001 },
						},
						labels: {
							primary: 'Month to date',
							secondary: 'Previous year',
						},
					} }
				/>
			);

			// 0.0001 × 100000 = 10 clears the floor; scaling from 0.05 instead would not have.
			const props = getChartProps();
			expect( props.data[ 0 ].secondary.value ).toBe( 10 );
			expect( props.data[ 0 ].primary.value ).toBe( 5000 );
		} );

		it( 'still clears the floor for a far more extreme gap between periods', () => {
			render(
				<ChartSection
					metrics={ ctrMetrics }
					loaded
					intervals={ [
						{ interval: '2026-10-01', subtotals: { ctr: 0.05 } },
					] }
					comparison={ {
						intervals: [
							{
								interval: '2025-10-01',
								subtotals: { ctr: 0.0000001 },
							},
						],
						totals: {
							ctr: { value: 0.05, prevValue: 0.0000001 },
						},
						labels: {
							primary: 'Month to date',
							secondary: 'Previous year',
						},
					} }
				/>
			);

			// A 500,000x gap, not just the 500x one above -- still clears the floor.
			const props = getChartProps();
			expect( props.data[ 0 ].secondary.value ).toBe( 10 );
			expect( props.data[ 0 ].primary.value ).toBe( 5000000 );
		} );
	} );
} );
