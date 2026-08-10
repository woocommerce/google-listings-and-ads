/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

jest.mock( '~/hooks/useGoogleMCAccount' );
jest.mock( '~/hooks/useMenuEffect' );
jest.mock( '~/components/gtin-migration-banner', () => () => null );
jest.mock( '@woocommerce/navigation', () => ( {
	getNewPath: ( _query, path ) => path,
	getPath: jest.fn().mockReturnValue( '/google/dashboard' ),
} ) );

const renderedTabKeys = ( container ) =>
	Array.from( container.querySelectorAll( '[role="tab"]' ) ).map(
		( el ) => el.id
	);

describe( 'MainTabNav', () => {
	let MainTabNav;
	let ALL_TABS;
	let useGoogleMCAccount;
	let getPath;

	beforeEach( () => {
		jest.resetModules();

		window.glaData = {
			...window.glaData,
			mcSetupComplete: true,
			enableReports: true,
		};

		useGoogleMCAccount = require( '~/hooks/useGoogleMCAccount' ).default;
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: false } );

		( { getPath } = require( '@woocommerce/navigation' ) );
		getPath.mockReturnValue( '/google/dashboard' );

		const mod = require( './main-tab-nav' );
		MainTabNav = mod.default;
		ALL_TABS = mod.ALL_TABS;
	} );

	it( 'restores the full tab list when hasGoogleMCConnection flips false → true', () => {
		window.glaData.mcSetupComplete = false;
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: false } );

		const { container, rerender } = render( <MainTabNav /> );
		const beforeFlip = new Set( renderedTabKeys( container ) );

		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );
		rerender( <MainTabNav /> );
		const afterFlip = new Set( renderedTabKeys( container ) );

		// Every tab visible before the flip must still be visible after,
		// and at least one previously-hidden tab must reappear.
		for ( const key of beforeFlip ) {
			expect( afterFlip.has( key ) ).toBe( true );
		}
		expect( afterFlip.size ).toBeGreaterThan( beforeFlip.size );
	} );

	it( 'a fresh instance reflects its own inputs, not the previous instance', () => {
		window.glaData.mcSetupComplete = false;
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: false } );

		const first = render( <MainTabNav /> );
		const firstKeys = new Set( renderedTabKeys( first.container ) );
		first.unmount();

		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );
		const second = render( <MainTabNav /> );
		const secondKeys = new Set( renderedTabKeys( second.container ) );

		expect( secondKeys.size ).toBeGreaterThan( firstKeys.size );
	} );

	it( 'renders only dashboard and settings when there is no MC connection', () => {
		window.glaData.mcSetupComplete = false;
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: false } );

		const { container } = render( <MainTabNav /> );

		expect( renderedTabKeys( container ) ).toEqual( [
			'dashboard',
			'settings',
		] );
	} );

	it( 'hides exactly the reports tab when enableReports is false', () => {
		const withReports = render( <MainTabNav /> );
		const withReportsKeys = new Set(
			renderedTabKeys( withReports.container )
		);
		withReports.unmount();

		window.glaData.enableReports = false;

		const withoutReports = render( <MainTabNav /> );
		const withoutReportsKeys = new Set(
			renderedTabKeys( withoutReports.container )
		);

		const removed = [ ...withReportsKeys ].filter(
			( key ) => ! withoutReportsKeys.has( key )
		);
		const added = [ ...withoutReportsKeys ].filter(
			( key ) => ! withReportsKeys.has( key )
		);

		expect( removed ).toEqual( [ 'reports' ] );
		expect( added ).toEqual( [] );
	} );

	it( 'renders every tab from ALL_TABS in the default state', () => {
		const { container } = render( <MainTabNav /> );

		expect( renderedTabKeys( container ) ).toEqual(
			ALL_TABS.map( ( t ) => t.key )
		);
	} );

	it( 'marks the tab matching the current URL path as selected', () => {
		getPath.mockReturnValue( '/google/product-feed' );

		const { container } = render( <MainTabNav /> );
		const selected = container.querySelector( '[aria-selected="true"]' );

		expect( selected ).not.toBeNull();
		expect( selected.id ).toBe( 'product-feed' );
	} );
} );
