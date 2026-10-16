/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import DataFreshnessNotice from './data-freshness-notice';

describe( 'DataFreshnessNotice', () => {
	it( 'should show the update cadence and the last updated date', () => {
		// 2026-06-24 12:00:00 UTC.
		render( <DataFreshnessNotice lastUpdated={ 1782302400 } /> );

		expect(
			screen.getByText( 'Search Console data updates every 2 to 3 days.' )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Last updated June 24, 2026' )
		).toBeInTheDocument();
	} );

	it( 'should leave out the last updated date when there is none yet', () => {
		render( <DataFreshnessNotice lastUpdated={ null } /> );

		expect(
			screen.getByText( 'Search Console data updates every 2 to 3 days.' )
		).toBeInTheDocument();
		expect( screen.queryByText( /Last updated/ ) ).not.toBeInTheDocument();
	} );
} );
