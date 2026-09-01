/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';
import { getCurrentDates } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import useProductRevenueMetricsDown from '~/hooks/useProductRevenueMetricsDown';

const PRIMARY_AFTER = '2025-02-01';
const SECONDARY_AFTER = '2025-01-01';

const mockGetWCReportStats = jest.fn();
const mockHasFinishedResolution = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/date', () => ( {
	__esModule: true,
	getCurrentDates: jest.fn(),
	// Identity so range markers pass through unchanged into the stats query.
	appendTimestamp: jest.fn( ( date ) => date ),
} ) );

/**
 * Configure the mocked store selectors.
 *
 * @param {Object} [options]
 * @param {Object} [options.resolved] Map of reportType → whether its totals have resolved.
 * @param {Object} [options.totals] Map of reportType → `{ primary, secondary }` totals objects.
 */
function setupStore( { resolved = {}, totals = {} } = {} ) {
	mockHasFinishedResolution.mockImplementation(
		( selectorName, [ reportType ] ) => Boolean( resolved[ reportType ] )
	);
	mockGetWCReportStats.mockImplementation( ( reportType, query ) => {
		const range = query.after === PRIMARY_AFTER ? 'primary' : 'secondary';
		return totals[ reportType ]?.[ range ] ?? null;
	} );
}

describe( 'useProductRevenueMetricsDown', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		getCurrentDates.mockReturnValue( {
			primary: { after: PRIMARY_AFTER, before: '2025-02-28' },
			secondary: { after: SECONDARY_AFTER, before: '2025-01-31' },
		} );
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getWCReportStats: mockGetWCReportStats,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) )
		);
	} );

	const render = () =>
		renderHook( () =>
			useProductRevenueMetricsDown(
				{},
				'period=month&compare=previous_period'
			)
		);

	test( 'reports pending and hidden while Case 1 (revenue) totals are still resolving', () => {
		setupStore( { resolved: { revenue: false } } );

		const { result } = render();

		expect( result.current ).toEqual( {
			hasFinishedResolution: false,
			isDown: false,
			metricsCase: null,
		} );
	} );

	test( 'fetches revenue stats for both the primary and comparison ranges', () => {
		setupStore( { resolved: { revenue: false } } );

		render();

		expect( mockGetWCReportStats ).toHaveBeenCalledWith( 'revenue', {
			after: PRIMARY_AFTER,
			before: '2025-02-28',
			interval: 'day',
		} );
		expect( mockGetWCReportStats ).toHaveBeenCalledWith( 'revenue', {
			after: SECONDARY_AFTER,
			before: '2025-01-31',
			interval: 'day',
		} );
	} );

	test( 'matches Case 1 when any revenue metric is down, and short-circuits Case 2', () => {
		setupStore( {
			resolved: { revenue: true, products: true },
			totals: {
				revenue: {
					// net_revenue is down (50 < 100); the other two are up.
					primary: {
						total_sales: 200,
						net_revenue: 50,
						orders_count: 20,
					},
					secondary: {
						total_sales: 100,
						net_revenue: 100,
						orders_count: 10,
					},
				},
			},
		} );

		const { result } = render();

		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			isDown: true,
			metricsCase: 'revenue',
		} );
		// Short-circuit: products stats are never requested.
		expect( mockGetWCReportStats ).not.toHaveBeenCalledWith(
			'products',
			expect.anything()
		);
	} );

	test( 'falls through to Case 2 (products) when revenue is not down', () => {
		setupStore( {
			resolved: { revenue: true, products: true },
			totals: {
				revenue: {
					primary: {
						total_sales: 200,
						net_revenue: 200,
						orders_count: 20,
					},
					secondary: {
						total_sales: 100,
						net_revenue: 100,
						orders_count: 10,
					},
				},
				products: {
					primary: { items_sold: 5 },
					secondary: { items_sold: 10 },
				},
			},
		} );

		const { result } = render();

		expect( mockGetWCReportStats ).toHaveBeenCalledWith(
			'products',
			expect.objectContaining( { after: PRIMARY_AFTER } )
		);
		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			isDown: true,
			metricsCase: 'products',
		} );
	} );

	test( 'reports pending while Case 2 (products) totals are still resolving', () => {
		setupStore( {
			resolved: { revenue: true, products: false },
			totals: {
				revenue: {
					primary: {
						total_sales: 200,
						net_revenue: 200,
						orders_count: 20,
					},
					secondary: {
						total_sales: 100,
						net_revenue: 100,
						orders_count: 10,
					},
				},
			},
		} );

		const { result } = render();

		expect( result.current ).toEqual( {
			hasFinishedResolution: false,
			isDown: false,
			metricsCase: null,
		} );
	} );

	test( 'hides the placement when the selected period is up vs. the comparison range', () => {
		setupStore( {
			resolved: { revenue: true, products: true },
			totals: {
				revenue: {
					primary: {
						total_sales: 200,
						net_revenue: 200,
						orders_count: 20,
					},
					secondary: {
						total_sales: 100,
						net_revenue: 100,
						orders_count: 10,
					},
				},
				products: {
					primary: { items_sold: 20 },
					secondary: { items_sold: 10 },
				},
			},
		} );

		const { result } = render();

		expect( result.current ).toEqual( {
			hasFinishedResolution: true,
			isDown: false,
			metricsCase: null,
		} );
	} );
} );
