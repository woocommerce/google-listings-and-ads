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

		fetchGoogleBusinessProfileAccount = jest
			.fn()
			.mockName( 'fetchGoogleBusinessProfileAccount' )
			.mockResolvedValue();
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
			{}
		);
		expect( result.current.isConnecting ).toBe( false );
	} );
} );
