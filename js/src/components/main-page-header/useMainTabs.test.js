/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { getPath } from '@woocommerce/navigation';

jest.mock( '~/hooks/useGoogleMCAccount' );
jest.mock( '@woocommerce/navigation', () => ( {
	getNewPath: ( _query, path ) => path,
	getPath: jest.fn(),
} ) );

/**
 * Internal dependencies
 */
import useMainTabs, { ALL_TABS } from './useMainTabs';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

const tabKeys = ( result ) => result.current.tabs.map( ( { key } ) => key );

describe( 'useMainTabs', () => {
	beforeEach( () => {
		// `~/constants` keeps a reference to this object, so change it in place.
		Object.assign( window.glaData, {
			mcSetupComplete: true,
			adsSetupComplete: false,
			enableReports: true,
		} );
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: false } );
		getPath.mockReturnValue( '/google/dashboard' );
	} );

	it( 'returns every tab in the default state', () => {
		const { result } = renderHook( () => useMainTabs() );

		expect( tabKeys( result ) ).toEqual( ALL_TABS.map( ( t ) => t.key ) );
	} );

	it( 'returns only dashboard and settings when there is no MC or Ads connection', () => {
		window.glaData.mcSetupComplete = false;

		const { result } = renderHook( () => useMainTabs() );

		expect( tabKeys( result ) ).toEqual( [ 'dashboard', 'settings' ] );
	} );

	it( 'returns dashboard, reports and settings when there is Ads but no MC connection', () => {
		window.glaData.mcSetupComplete = false;
		window.glaData.adsSetupComplete = true;

		const { result } = renderHook( () => useMainTabs() );

		expect( tabKeys( result ) ).toEqual( [
			'dashboard',
			'reports',
			'settings',
		] );
	} );

	it( 'hides the reports tab for Ads-only merchants when enableReports is false', () => {
		window.glaData.mcSetupComplete = false;
		window.glaData.adsSetupComplete = true;
		window.glaData.enableReports = false;

		const { result } = renderHook( () => useMainTabs() );

		expect( tabKeys( result ) ).toEqual( [ 'dashboard', 'settings' ] );
	} );

	it( 'hides exactly the reports tab when enableReports is false', () => {
		window.glaData.enableReports = false;

		const { result } = renderHook( () => useMainTabs() );

		expect( tabKeys( result ) ).toEqual(
			ALL_TABS.map( ( t ) => t.key ).filter(
				( key ) => key !== 'reports'
			)
		);
	} );

	it( 'restores the full tab list when the MC connection loads', () => {
		window.glaData.mcSetupComplete = false;

		const { result, rerender } = renderHook( () => useMainTabs() );
		expect( tabKeys( result ) ).toEqual( [ 'dashboard', 'settings' ] );

		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );
		rerender();

		expect( tabKeys( result ) ).toEqual( ALL_TABS.map( ( t ) => t.key ) );
	} );

	it( 'selects the tab matching the current URL path', () => {
		getPath.mockReturnValue( '/google/product-feed' );

		const { result } = renderHook( () => useMainTabs() );

		expect( result.current.selectedKey ).toBe( 'product-feed' );
	} );

	it( 'selects the reports tab on a reports sub-page', () => {
		getPath.mockReturnValue( '/google/reports' );

		const { result } = renderHook( () => useMainTabs() );

		expect( result.current.selectedKey ).toBe( 'reports' );
	} );
} );
