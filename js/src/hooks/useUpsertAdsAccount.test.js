/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import useUpsertAdsAccount from './useUpsertAdsAccount';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import { useAppDispatch } from '~/data';
import { ERROR_SLOTS } from '~/data/constants';
import extractDetailedApiError from '~/utils/extractDetailedApiError';

jest.mock( '@wordpress/api-fetch' );
jest.mock( './useGoogleAdsAccount' );
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn(),
} ) );
jest.mock( '~/utils/extractDetailedApiError' );

/**
 * Creates a minimal `Response`-like object, as returned or thrown by `apiFetch` with `parse: false`.
 *
 * @param {Object} body The JSON body of the response.
 * @param {number} status The HTTP status code.
 * @return {Object} The mocked response.
 */
const jsonResponse = ( body, status ) => {
	const response = {
		status,
		json: () => Promise.resolve( body ),
	};
	response.clone = () => response;
	return response;
};

describe( 'useUpsertAdsAccount', () => {
	let fetchGoogleAdsAccount;
	let fetchGoogleAdsAccountStatus;
	let receiveDetailedError;

	beforeEach( () => {
		jest.clearAllMocks();

		fetchGoogleAdsAccount = jest.fn( () => Promise.resolve() );
		fetchGoogleAdsAccountStatus = jest.fn( () => Promise.resolve() );
		receiveDetailedError = jest.fn();

		useAppDispatch.mockReturnValue( {
			fetchGoogleAdsAccount,
			fetchGoogleAdsAccountStatus,
			receiveDetailedError,
		} );
		useGoogleAdsAccount.mockReturnValue( { googleAdsAccount: null } );
		apiFetch.mockResolvedValue( jsonResponse( { id: 123 }, 200 ) );
	} );

	it( 'creates an account when there is no connected one and refreshes the account data', async () => {
		const { result } = renderHook( () => useUpsertAdsAccount() );

		await act( async () => {
			await result.current[ 0 ]();
		} );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/wc/gla/ads/accounts',
			method: 'POST',
			data: { id: undefined },
			parse: false,
		} );
		expect( fetchGoogleAdsAccount ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleAdsAccountStatus ).toHaveBeenCalledTimes( 1 );
		expect( receiveDetailedError ).not.toHaveBeenCalled();
		expect( result.current[ 1 ] ).toEqual( {
			loading: false,
			action: null,
		} );
	} );

	it( 'updates the connected account', async () => {
		useGoogleAdsAccount.mockReturnValue( {
			googleAdsAccount: { id: 456 },
		} );
		const { result } = renderHook( () => useUpsertAdsAccount() );

		await act( async () => {
			await result.current[ 0 ]();
		} );

		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( { data: { id: 456 } } )
		);
	} );

	it( 'passes the raw error response to `extractDetailedApiError`, ignoring 428', async () => {
		const errorResponse = jsonResponse(
			{ message: 'Billing setup required' },
			428
		);
		apiFetch.mockRejectedValue( errorResponse );
		extractDetailedApiError.mockResolvedValue( null );
		const { result } = renderHook( () => useUpsertAdsAccount() );

		await act( async () => {
			await result.current[ 0 ]();
		} );

		// The response itself, not its parsed body, so the status code can be checked.
		expect( extractDetailedApiError ).toHaveBeenCalledWith( errorResponse, {
			ignoredStatusCodes: [ 428 ],
		} );
		expect( receiveDetailedError ).not.toHaveBeenCalled();
		expect( fetchGoogleAdsAccount ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleAdsAccountStatus ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'reports other errors to the Google Ads connection error slot', async () => {
		apiFetch.mockRejectedValue(
			jsonResponse( { message: 'Invalid account' }, 400 )
		);
		extractDetailedApiError.mockResolvedValue( {
			data: { statusCode: 400, message: 'Invalid account' },
		} );
		const { result } = renderHook( () => useUpsertAdsAccount() );

		await act( async () => {
			await result.current[ 0 ]();
		} );

		expect( receiveDetailedError ).toHaveBeenCalledTimes( 1 );
		expect( receiveDetailedError ).toHaveBeenCalledWith(
			ERROR_SLOTS.GOOGLE_ADS_CONNECTION_ERROR_SLOT,
			{
				statusCode: 400,
				message: 'Invalid account',
				title: 'Google Ads Creation Failed',
			}
		);
		expect( fetchGoogleAdsAccount ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleAdsAccountStatus ).toHaveBeenCalledTimes( 1 );
	} );
} );
