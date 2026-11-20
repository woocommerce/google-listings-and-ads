/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { Chart } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import ChartSection from './chart-section';

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
	} );
} );
