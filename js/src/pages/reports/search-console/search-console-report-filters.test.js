/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SearchConsoleReportFilters from './search-console-report-filters';
import ReportFilters from '~/components/external/woocommerce/filters';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/components/external/woocommerce/filters', () =>
	jest.fn( () => null ).mockName( 'ReportFilters' )
);

jest.mock( '~/utils/tracks', () => ( {
	__esModule: true,
	recordGlaEvent: jest.fn(),
} ) );

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: () => ( { siteLocale: 'en_US' } ),
} ) );

jest.mock( '~/hooks/useStoreCurrency', () => ( {
	__esModule: true,
	default: () => ( {} ),
} ) );

describe( 'SearchConsoleReportFilters', () => {
	const query = {
		path: '/google/reports',
		reportKey: 'search-console',
		period: 'custom',
		compare: 'previous_period',
		after: '2026-09-01',
		before: '2026-09-14',
	};

	beforeEach( () => {
		ReportFilters.mockClear();
		recordGlaEvent.mockClear();
	} );

	it( 'should render only the date picker, using the date range from the query', () => {
		render(
			<SearchConsoleReportFilters
				query={ query }
				trackEventId="reports-search-console"
			/>
		);

		const props = ReportFilters.mock.calls[ 0 ][ 0 ];

		expect( props.filters ).toBeUndefined();
		expect( props.path ).toBe( '/google/reports' );
		expect( props.dateQuery ).toMatchObject( {
			period: 'custom',
			compare: 'previous_period',
			after: expect.anything(),
			before: expect.anything(),
		} );
		expect( props.dateQuery.primaryDate.after.format( 'YYYY-MM-DD' ) ).toBe(
			'2026-09-01'
		);
		expect(
			props.dateQuery.primaryDate.before.format( 'YYYY-MM-DD' )
		).toBe( '2026-09-14' );
	} );

	it( 'should record the date picker update with the report ID', () => {
		render(
			<SearchConsoleReportFilters
				query={ query }
				trackEventId="reports-search-console"
			/>
		);

		ReportFilters.mock.calls[ 0 ][ 0 ].onDateSelect( {
			period: 'last_month',
			compare: 'previous_year',
			before: undefined,
		} );

		expect( recordGlaEvent ).toHaveBeenCalledWith(
			'gla_datepicker_update',
			{
				report: 'reports-search-console',
				period: 'last_month',
				compare: 'previous_year',
			}
		);
	} );
} );
