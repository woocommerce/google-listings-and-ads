/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { act, render, screen } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import SearchConsoleReport from './index';
import { MIN_RECHECK_INTERVAL } from './hooks/useRefreshGoogleSearchConsoleAccount';
import '~/data';

jest.mock( '@wordpress/api-fetch' );
jest.mock( '~/utils/tracks', () => ( { recordGlaEvent: jest.fn() } ) );
jest.mock( '~/components/main-tab-nav', () => () => <div /> );
jest.mock( '../reports-navigation', () => () => <div /> );
jest.mock( './search-console-report-filters', () => () => (
	<div>Date filters</div>
) );
jest.mock( './organic-overview', () => () => <div>Organic overview</div> );
jest.mock( './top-search-queries', () => () => <div>Top search queries</div> );
jest.mock( './search-console-top-pages', () => () => (
	<div>Top pages tables</div>
) );

const NEEDS_ATTENTION_HEADING = 'Your Search Console profile needs attention';

describe( 'Organic search report connection re-check', () => {
	let now;

	const returnToBrowserTab = async () => {
		now += MIN_RECHECK_INTERVAL;

		await act( async () => {
			document.dispatchEvent( new Event( 'visibilitychange' ) );
		} );
	};

	beforeEach( () => {
		now = 1000000;
		jest.spyOn( Date, 'now' ).mockImplementation( () => now );
		apiFetch.mockResolvedValue( { status: 'connected' } );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'should show the needs-attention prompt when the status changed since an earlier visit', async () => {
		const { unmount } = render( <SearchConsoleReport /> );
		await screen.findByText( 'Organic overview' );
		unmount();

		apiFetch.mockResolvedValue( { status: 'action-needed' } );
		render( <SearchConsoleReport /> );

		expect(
			await screen.findByText( NEEDS_ATTENTION_HEADING )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Organic overview' ) ).toBeNull();
	} );

	it( 'should keep the reports when the check on open fails', async () => {
		const { unmount } = render( <SearchConsoleReport /> );
		await screen.findByText( 'Organic overview' );
		unmount();

		apiFetch.mockRejectedValue( new Error( 'offline' ) );
		render( <SearchConsoleReport /> );

		expect(
			await screen.findByText( 'Organic overview' )
		).toBeInTheDocument();
		expect( screen.queryByText( NEEDS_ATTENTION_HEADING ) ).toBeNull();
		expect(
			screen.queryByText( 'Connect your Search Console profile' )
		).toBeNull();
	} );

	it( 'should swap the reports for the prompt when the status changes while the report stays open', async () => {
		render( <SearchConsoleReport /> );
		await screen.findByText( 'Organic overview' );

		apiFetch.mockResolvedValue( { status: 'action-needed' } );
		await returnToBrowserTab();

		expect(
			await screen.findByText( NEEDS_ATTENTION_HEADING )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Organic overview' ) ).toBeNull();
	} );

	it( 'should keep the reports when the re-check request fails', async () => {
		render( <SearchConsoleReport /> );
		await screen.findByText( 'Organic overview' );

		apiFetch.mockRejectedValue( new Error( 'offline' ) );
		await returnToBrowserTab();

		expect( screen.getByText( 'Organic overview' ) ).toBeInTheDocument();
		expect( screen.queryByText( NEEDS_ATTENTION_HEADING ) ).toBeNull();
		expect(
			screen.queryByText( 'Connect your Search Console profile' )
		).toBeNull();
	} );
} );
