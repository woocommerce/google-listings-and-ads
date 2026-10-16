/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useRefreshGoogleTagManagerConnection from './useRefreshGoogleTagManagerConnection';
import { useAppDispatch } from '~/data';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { recordGlaEvent } from '~/utils/tracks';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);
jest.mock( '~/utils/tracks', () => ( {
	...jest.requireActual( '~/utils/tracks' ),
	recordGlaEvent: jest.fn().mockName( 'recordGlaEvent' ),
} ) );
jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	getHistory: jest.fn().mockName( 'getHistory' ),
} ) );

describe( 'useRefreshGoogleTagManagerConnection', () => {
	let fetchGoogleTagManagerAccount;
	let fetchGoogleTagManagerSettings;
	let createNotice;
	let dispatchers;

	beforeEach( () => {
		jest.clearAllMocks();

		fetchGoogleTagManagerAccount = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerAccount' )
			.mockResolvedValue( {
				account: { status: 'connected', id: '6002847391' },
			} );
		fetchGoogleTagManagerSettings = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerSettings' )
			.mockResolvedValue( {
				settings: { snippetInjectionEnabled: true },
			} );
		createNotice = jest.fn().mockName( 'createNotice' );

		dispatchers = {
			fetchGoogleTagManagerAccount,
			fetchGoogleTagManagerSettings,
		};
		useAppDispatch.mockReturnValue( dispatchers );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );
	} );

	it( 'returns a stable refreshConnection function', () => {
		const { result, rerender } = renderHook( () =>
			useRefreshGoogleTagManagerConnection()
		);
		const { refreshConnection } = result.current;

		rerender();

		expect( result.current.refreshConnection ).toBe( refreshConnection );
	} );

	it( 'refreshes the connection and the settings, and resolves with the refreshed connection', async () => {
		const { result } = renderHook( () =>
			useRefreshGoogleTagManagerConnection()
		);

		const refreshed = await result.current.refreshConnection();

		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleTagManagerSettings ).toHaveBeenCalledTimes( 1 );
		expect( refreshed ).toEqual( {
			status: 'connected',
			id: '6002847391',
		} );
	} );

	it( 'records the connection, and an injection failure when the container has no public ID', async () => {
		fetchGoogleTagManagerAccount.mockResolvedValue( {
			account: {
				status: 'connected',
				id: '6002847391',
				injectionFailed: true,
			},
		} );
		const { result } = renderHook( () =>
			useRefreshGoogleTagManagerConnection()
		);

		await result.current.refreshConnection();

		expect( recordGlaEvent.mock.calls.map( ( [ name ] ) => name ) ).toEqual(
			[
				'gla_google_tag_manager_container_connected',
				'gla_google_tag_manager_injection_failure',
			]
		);
	} );

	it( 'records nothing when the refreshed connection is not connected', async () => {
		fetchGoogleTagManagerAccount.mockResolvedValue( {
			account: { status: 'incomplete', id: '6002847391' },
		} );
		const { result } = renderHook( () =>
			useRefreshGoogleTagManagerConnection()
		);

		await result.current.refreshConnection();

		expect( recordGlaEvent ).not.toHaveBeenCalled();
	} );

	it( 'resolves with undefined and records nothing when the connection could not be loaded', async () => {
		fetchGoogleTagManagerAccount.mockResolvedValue( undefined );
		const { result } = renderHook( () =>
			useRefreshGoogleTagManagerConnection()
		);

		expect( await result.current.refreshConnection() ).toBeUndefined();
		expect( recordGlaEvent ).not.toHaveBeenCalled();
		expect( createNotice ).not.toHaveBeenCalled();
	} );

	it( 'shows a snackbar when an Ads conflict turned the snippet off', async () => {
		fetchGoogleTagManagerAccount.mockResolvedValue( {
			account: {
				status: 'connected',
				id: '6002847391',
				adsConversionConflict: true,
			},
		} );
		fetchGoogleTagManagerSettings.mockResolvedValue( {
			settings: { snippetInjectionEnabled: false },
		} );
		const { result } = renderHook( () =>
			useRefreshGoogleTagManagerConnection()
		);

		await result.current.refreshConnection();

		expect( createNotice ).toHaveBeenCalledWith(
			'warning',
			"The Google Tag Manager snippet wasn't added to your site due to a conflict with existing Google Ads tracking.",
			expect.objectContaining( { type: 'snackbar' } )
		);
	} );

	it( 'shows no snackbar when the snippet is still on despite a conflict', async () => {
		fetchGoogleTagManagerAccount.mockResolvedValue( {
			account: {
				status: 'connected',
				id: '6002847391',
				adsConversionConflict: true,
			},
		} );
		const { result } = renderHook( () =>
			useRefreshGoogleTagManagerConnection()
		);

		await result.current.refreshConnection();

		expect( createNotice ).not.toHaveBeenCalled();
	} );
} );
