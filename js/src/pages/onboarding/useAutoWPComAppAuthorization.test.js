/**
 * External dependencies
 */
import { renderHook, waitFor } from '@testing-library/react';
import { getQuery } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import useAutoWPComAppAuthorization from './useAutoWPComAppAuthorization';
import useJetpackAccount from '~/hooks/useJetpackAccount';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleMCAccount from '~/hooks/useGoogleMCAccount';
import { useAppDispatch } from '~/data';

jest.mock( '@woocommerce/navigation' );
jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn().mockName( 'recordEvent' ),
} ) );
jest.mock( '~/hooks/useJetpackAccount' );
jest.mock( '~/hooks/useGoogleAccount' );
jest.mock( '~/hooks/useGoogleMCAccount' );
jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

const googleAccount = {
	google: { active: 'yes' },
	scope: { gmcRequired: true, onboardingRequired: true },
};

const googleMCAccount = { googleMCAccount: {}, isWPComAppGranted: true };

describe( 'useAutoWPComAppAuthorization', () => {
	let fetchWPComAppAuthorizationUrl;

	beforeEach( () => {
		getQuery.mockClear().mockReturnValue( { 'google-mc': 'connected' } );
		recordEvent.mockClear();
		useJetpackAccount.mockReturnValue( { jetpack: { active: 'yes' } } );
		useGoogleAccount.mockReturnValue( googleAccount );
		useGoogleMCAccount.mockReturnValue( googleMCAccount );

		fetchWPComAppAuthorizationUrl = jest
			.fn()
			.mockName( 'fetchWPComAppAuthorizationUrl' )
			.mockResolvedValue( 'https://jest/wpcom-auth' );
		useAppDispatch.mockReturnValue( { fetchWPComAppAuthorizationUrl } );
	} );

	describe( 'Should return `null`', () => {
		it( 'When Jetpack account is loading', () => {
			useJetpackAccount.mockReturnValue( { jetpack: null } );
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBeNull();
			expect( fetchWPComAppAuthorizationUrl ).not.toHaveBeenCalled();
		} );

		it( 'When Google account is loading', () => {
			useGoogleAccount.mockReturnValue( { google: null } );
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBeNull();
			expect( fetchWPComAppAuthorizationUrl ).not.toHaveBeenCalled();
		} );

		it( 'When Google Merchant Center account is loading', () => {
			useGoogleMCAccount.mockReturnValue( { googleMCAccount: null } );
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBeNull();
			expect( fetchWPComAppAuthorizationUrl ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'Should return `true`', () => {
		it( 'When not being redirected back from Google authorization', () => {
			getQuery.mockReturnValue( {} );
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBe( true );
			expect( fetchWPComAppAuthorizationUrl ).not.toHaveBeenCalled();
		} );

		it( 'When Jetpack account is not connected', () => {
			useJetpackAccount.mockReturnValue( { jetpack: { active: 'no' } } );
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBe( true );
			expect( fetchWPComAppAuthorizationUrl ).not.toHaveBeenCalled();
		} );

		it( 'When Google account is not connected', () => {
			useGoogleAccount.mockReturnValue( {
				...googleAccount,
				google: { active: 'no' },
			} );
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBe( true );
			expect( fetchWPComAppAuthorizationUrl ).not.toHaveBeenCalled();
		} );

		it( 'When access to Google Merchant Center is insufficient', () => {
			useGoogleAccount.mockReturnValue( {
				...googleAccount,
				scope: { gmcRequired: false },
			} );
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBe( true );
			expect( fetchWPComAppAuthorizationUrl ).not.toHaveBeenCalled();
		} );

		it( 'When all required access to Google account is insufficient', () => {
			useGoogleAccount.mockReturnValue( {
				...googleAccount,
				scope: { gmcRequired: true, onboardingRequired: false },
			} );
			useGoogleMCAccount.mockReturnValue( {
				...googleMCAccount,
				isWPComAppGranted: false,
			} );
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBe( true );
			expect( fetchWPComAppAuthorizationUrl ).not.toHaveBeenCalled();
		} );

		it( 'When WPCom app authorization is already granted', () => {
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBe( true );
			expect( fetchWPComAppAuthorizationUrl ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'WPCom app authorization is not granted and being redirected back from Google authorization', () => {
		let originalLocation;
		let spyHref;

		beforeEach( () => {
			useGoogleMCAccount.mockReturnValue( {
				...googleMCAccount,
				isWPComAppGranted: false,
			} );

			originalLocation = global.location;
			Object.defineProperty( global, 'location', {
				value: new URL( 'http://localhost' ),
				writable: true,
			} );
			spyHref = jest.spyOn( global.location, 'href', 'set' );
		} );

		afterEach( () => {
			global.location = originalLocation;
		} );

		it( 'Should return `false`', () => {
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBe( false );
		} );

		it( 'Should redirect to WPCom app authorization URL', async () => {
			renderHook( () => useAutoWPComAppAuthorization() );

			await waitFor( () => expect( spyHref ).toHaveBeenCalledTimes( 1 ) );
			expect( spyHref ).toHaveBeenCalledWith( 'https://jest/wpcom-auth' );
		} );

		it( 'Should record an event when starting the redirection to WPCom app authorization URL', () => {
			expect( recordEvent ).not.toHaveBeenCalled();

			renderHook( () => useAutoWPComAppAuthorization( 'jest-testing' ) );

			expect( recordEvent ).toHaveBeenCalledTimes( 1 );
			expect( recordEvent ).toHaveBeenCalledWith(
				'gla_enable_product_sync',
				{
					page: 'jest-testing',
					context: 'auto-redirection',
				}
			);
		} );

		it( 'Should only fetch and redirect to WPCom app authorization URL one time', async () => {
			expect( recordEvent ).not.toHaveBeenCalled();
			expect( fetchWPComAppAuthorizationUrl ).not.toHaveBeenCalled();
			expect( spyHref ).not.toHaveBeenCalled();

			const hook = renderHook( ( page ) =>
				useAutoWPComAppAuthorization( page )
			);

			expect( recordEvent ).toHaveBeenCalledTimes( 1 );
			expect( fetchWPComAppAuthorizationUrl ).toHaveBeenCalledTimes( 1 );
			await waitFor( () => expect( spyHref ).toHaveBeenCalledTimes( 1 ) );

			hook.rerender();
			hook.rerender( 'jest-testing' );
			hook.rerender();

			expect( getQuery ).toHaveBeenCalledTimes( 4 );
			expect( recordEvent ).toHaveBeenCalledTimes( 1 );
			expect( fetchWPComAppAuthorizationUrl ).toHaveBeenCalledTimes( 1 );
			await waitFor( () => expect( spyHref ).toHaveBeenCalledTimes( 1 ) );
		} );

		it( 'When an error occurs while fetching the URL, it should trigger a hook update and change the return value from `false` to `true`', async () => {
			fetchWPComAppAuthorizationUrl.mockRejectedValue( new Error() );
			const hook = renderHook( () => useAutoWPComAppAuthorization() );

			expect( hook.result.current ).toBe( false );
			await waitFor( () => expect( hook.result.current ).toBe( true ) );
		} );
	} );
} );
