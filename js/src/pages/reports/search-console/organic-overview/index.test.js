/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import OrganicOverview from './index';
import SummarySection from '../../summary-section';
import ChartSection from '../../chart-section';
import useOrganicOverviewReport from '../hooks/useOrganicOverviewReport';

jest.mock( '../../summary-section', () =>
	jest.fn( () => null ).mockName( 'SummarySection' )
);
jest.mock( '../../chart-section', () =>
	jest.fn( () => null ).mockName( 'ChartSection' )
);
jest.mock( '../hooks/useOrganicOverviewReport', () =>
	jest.fn().mockName( 'useOrganicOverviewReport' )
);

const mockMetrics = [ { key: 'clicks', label: 'Clicks' } ];
jest.mock( '../hooks/useOrganicOverviewMetrics', () => () => mockMetrics );

const report = {
	loaded: true,
	totals: { clicks: { value: 8, prevValue: 6, delta: 33.33 } },
	intervals: [ { interval: '2026-10-01', subtotals: { clicks: 8 } } ],
	comparisonIntervals: [
		{ interval: '2025-10-01', subtotals: { clicks: 6 } },
	],
	interval: 'day',
	allowedIntervals: [ 'day', 'week' ],
	labels: { primary: 'Month to date', secondary: 'Previous year' },
};

describe( 'OrganicOverview', () => {
	beforeEach( () => {
		SummarySection.mockClear();
		ChartSection.mockClear();
		useOrganicOverviewReport.mockReturnValue( report );
	} );

	it( 'shows the totals of the metrics, tracked under the Organic search report', () => {
		render( <OrganicOverview /> );

		expect( SummarySection.mock.calls[ 0 ][ 0 ] ).toEqual( {
			loaded: true,
			metrics: mockMetrics,
			totals: report.totals,
			trackEventId: 'reports-search-console',
		} );
	} );

	it( 'charts the selected metric over both periods, with the interval selector', () => {
		render( <OrganicOverview /> );

		expect( ChartSection.mock.calls[ 0 ][ 0 ] ).toEqual( {
			metrics: mockMetrics,
			loaded: true,
			intervals: report.intervals,
			comparison: {
				intervals: report.comparisonIntervals,
				totals: report.totals,
				labels: report.labels,
			},
			interval: 'day',
			allowedIntervals: [ 'day', 'week' ],
		} );
	} );
} );
