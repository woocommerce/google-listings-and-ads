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
import { STORE_KEY } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useApiFetchCallback' );

const downtown = { id: '1111', title: "Jane's Bakery" };
const riverside = { id: '2222', title: "Jane's Bakery Riverside" };

describe( 'useConnectGoogleBusinessProfileLocation', () => {
	let fetchConnect;
	let fetchGoogleBusinessProfileAccount;
	let receiveDetailedError;
	let clearDetailedErrorBySlots;

	beforeEach( () => {
		jest.clearAllMocks();

		// `hasConnectionError` derives from this slot in the real store — start each test from a
		// clean slate.
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

	it( 'starts with no location picked and no connection error', () => {
		const { result } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation( [ downtown, riverside ] )
		);

		expect( result.current.locationId ).toBeUndefined();
		expect( result.current.hasConnectionError ).toBe( false );
		expect( result.current.isConnecting ).toBe( false );
	} );

	it( 'auto-selects the only candidate when there is exactly one location', () => {
		const { result } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation( [ downtown ] )
		);

		expect( result.current.locationId ).toBe( '1111' );
	} );

	it( 'does not auto-select when there are multiple locations', () => {
		const { result } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation( [ downtown, riverside ] )
		);

		expect( result.current.locationId ).toBeUndefined();
	} );

	it( 'connects the picked location and refreshes the connection', async () => {
		const { result } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation( [ downtown, riverside ] )
		);

		act( () => {
			result.current.setLocationId( '2222' );
		} );

		await act( async () => {
			await result.current.handleConnectClick();
		} );

		expect( useApiFetchCallback ).toHaveBeenLastCalledWith( {
			path: '/wc/gla/business-profile/locations',
			method: 'POST',
			data: { id: '2222' },
		} );
		expect( fetchConnect ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleBusinessProfileAccount ).toHaveBeenCalledTimes( 1 );
		expect( result.current.isConnecting ).toBe( false );
		expect( result.current.hasConnectionError ).toBe( false );
	} );

	it( 'records a connection error and does not refresh the connection on failure', async () => {
		fetchConnect.mockRejectedValue( new Error( 'Request failed' ) );
		const { result, rerender } = renderHook( () =>
			useConnectGoogleBusinessProfileLocation( [ downtown ] )
		);

		await act( async () => {
			await result.current.handleConnectClick();
		} );
		rerender();

		expect( fetchGoogleBusinessProfileAccount ).not.toHaveBeenCalled();
		expect( result.current.hasConnectionError ).toBe( true );
		expect( result.current.isConnecting ).toBe( false );
	} );
} );
