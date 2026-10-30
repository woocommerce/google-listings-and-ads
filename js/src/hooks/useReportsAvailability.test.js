/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useReportsAvailability from './useReportsAvailability';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';

jest.mock( '~/hooks/useGoogleMCAccount' );

describe( 'useReportsAvailability', () => {
	let originalGlaData;

	beforeEach( () => {
		originalGlaData = { ...window.glaData };
		Object.assign( window.glaData, {
			mcSetupComplete: false,
			adsSetupComplete: false,
			enableReports: true,
		} );
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: false } );
	} );

	afterEach( () => {
		Object.assign( window.glaData, originalGlaData );
	} );

	it( 'allows all reports when Merchant Center setup is complete', () => {
		window.glaData.mcSetupComplete = true;

		const { result } = renderHook( () => useReportsAvailability() );

		expect( result.current ).toEqual( {
			hasMC: true,
			canViewReports: true,
		} );
	} );

	it( 'allows all reports when Merchant Center is connected', () => {
		useGoogleMCAccount.mockReturnValue( { hasGoogleMCConnection: true } );

		const { result } = renderHook( () => useReportsAvailability() );

		expect( result.current ).toEqual( {
			hasMC: true,
			canViewReports: true,
		} );
	} );

	it( 'allows reports without Merchant Center when Ads setup is complete', () => {
		window.glaData.adsSetupComplete = true;

		const { result } = renderHook( () => useReportsAvailability() );

		expect( result.current ).toEqual( {
			hasMC: false,
			canViewReports: true,
		} );
	} );

	it( 'does not allow reports without Merchant Center or Ads', () => {
		const { result } = renderHook( () => useReportsAvailability() );

		expect( result.current ).toEqual( {
			hasMC: false,
			canViewReports: false,
		} );
	} );

	it( 'does not allow reports when reports are disabled', () => {
		Object.assign( window.glaData, {
			mcSetupComplete: true,
			adsSetupComplete: true,
			enableReports: false,
		} );

		const { result } = renderHook( () => useReportsAvailability() );

		expect( result.current ).toEqual( {
			hasMC: true,
			canViewReports: false,
		} );
	} );
} );
