/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useConnectGoogleBusinessProfileLocation, {
	CONNECTION_ERROR_SLOTS,
} from './useConnectGoogleBusinessProfileLocation';
import { useAppDispatch } from '~/data';
import { ERROR_SLOTS, STORE_KEY } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback' );

describe( 'useConnectGoogleBusinessProfileLocation', () => {
	let fetchConnect;
	let fetchGoogleBusinessProfileAccount;
	let receiveDetailedError;
	let clearDetailedErrorBySlots;

	beforeEach( () => {
		jest.clearAllMocks();

		// The connection error is recorded in the real store — start each test from a clean slate.
		dispatch( STORE_KEY ).clearDetailedErrorBySlots(
			CONNECTION_ERROR_SLOTS
		);

		fetchConnect = jest.fn().mockName( 'fetchConnect' ).mockResolvedValue();
		useApiFetchCallback.mockReturnValue( [
			fetchConnect,
			{ loading: false },
		] );

		// Mirrors the real action: it never rejects, only resolves to the dispatched action
		// object on success, or to `undefined` on a failure it already reported itself.
		fetchGoogleBusinessProfileAccount = jest
			.fn()
			.mockName( 'fetchGoogleBusinessProfileAccount' )
			.mockResolvedValue( {
				type: 'RECEIVE_ACCOUNTS_GOOGLE_BUSINESS_PROFILE',
				account: {},
			} );
		receiveDetailedError = jest
			.fn()
			.mockName( 'receiveDetailedError' )
			.mockImplementation( ( slot, error ) =>
				dispatch( STORE_KEY ).receiveDetailedError( slot, error )
			);
		clearDetailedErrorBySlots = jest
			.fn()
			.mockName( 'clearDetailedErrorBySlots' )
			.mockImplementation( ( slots ) =>
				dispatch( STORE_KEY ).clearDetailedErrorBySlots( slots )
			);
		useAppDispatch.mockReturnValue( {
			fetchGoogleBusinessProfileAccount,
			receiveDetailedError,
			clearDetailedErrorBySlots,
		} );
	} );

	it( 'starts with no connect request in flight', () => {
		const { result } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation()
		);

		expect( result.current.isConnecting ).toBe( false );
	} );

	it( 'connects the given location and refreshes the connection', async () => {
		const { result } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation()
		);

		await act( async () => {
			await result.current.connectLocation( '2222' );
		} );

		expect( useApiFetchCallback ).toHaveBeenLastCalledWith( {
			path: '/wc/gla/business-profile/locations',
			method: 'POST',
		} );
		expect( fetchConnect ).toHaveBeenCalledTimes( 1 );
		expect( fetchConnect ).toHaveBeenCalledWith( {
			data: { id: '2222' },
		} );
		expect( fetchGoogleBusinessProfileAccount ).toHaveBeenCalledTimes( 1 );
		expect( clearDetailedErrorBySlots ).toHaveBeenCalledWith(
			CONNECTION_ERROR_SLOTS
		);
		expect( result.current.isConnecting ).toBe( false );
	} );

	it( 'records a connection error and does not refresh the connection on failure', async () => {
		fetchConnect.mockRejectedValue( new Error( 'Request failed' ) );
		const { result } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation()
		);

		await act( async () => {
			await result.current.connectLocation( '1111' );
		} );

		expect( fetchGoogleBusinessProfileAccount ).not.toHaveBeenCalled();
		expect( receiveDetailedError ).toHaveBeenCalledWith(
			ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT,
			{ refreshFailedAfterConnecting: false }
		);
		expect( result.current.isConnecting ).toBe( false );
	} );

	it( 'marks the error as a refresh-only failure when the connect request already succeeded', async () => {
		// The real action reports its own failure and resolves to `undefined` -- it never
		// rejects (see actions.js's fetchGoogleBusinessProfileAccount).
		fetchGoogleBusinessProfileAccount.mockResolvedValue( undefined );
		const { result } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation()
		);

		await act( async () => {
			await result.current.connectLocation( '2222' );
		} );

		expect( fetchConnect ).toHaveBeenCalledTimes( 1 );
		expect( receiveDetailedError ).toHaveBeenCalledWith(
			ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT,
			{ refreshFailedAfterConnecting: true }
		);
		expect( result.current.isConnecting ).toBe( false );
	} );

	it( 'retryStatusRefresh refreshes without repeating the connect request', async () => {
		const { result } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation()
		);

		await act( async () => {
			await result.current.retryStatusRefresh();
		} );

		expect( fetchConnect ).not.toHaveBeenCalled();
		expect( fetchGoogleBusinessProfileAccount ).toHaveBeenCalledTimes( 1 );
		expect( clearDetailedErrorBySlots ).toHaveBeenCalledWith(
			CONNECTION_ERROR_SLOTS
		);
		expect( result.current.isConnecting ).toBe( false );
	} );

	it( 'retryStatusRefresh records another refresh-only failure when it fails again', async () => {
		fetchGoogleBusinessProfileAccount.mockResolvedValue( undefined );
		const { result } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation()
		);

		await act( async () => {
			await result.current.retryStatusRefresh();
		} );

		expect( fetchConnect ).not.toHaveBeenCalled();
		expect( receiveDetailedError ).toHaveBeenCalledWith(
			ERROR_SLOTS.GOOGLE_BUSINESS_PROFILE_CONNECTION_ERROR_SLOT,
			{ refreshFailedAfterConnecting: true }
		);
	} );
} );
