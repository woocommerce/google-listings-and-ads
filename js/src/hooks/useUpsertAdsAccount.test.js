/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useUpsertAdsAccount from './useUpsertAdsAccount';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useApiFetchCallback from './useApiFetchCallback';
import extractDetailedApiError from '~/utils/extractDetailedApiError';
import { useAppDispatch } from '~/data';
import { ERROR_SLOTS } from '~/data/constants';

jest.mock( './useGoogleAdsAccount' );
jest.mock( './useApiFetchCallback' );
jest.mock( '~/utils/extractDetailedApiError' );
jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

describe( 'useUpsertAdsAccount', () => {
	let fetchCreateAccount;
	let fetchGoogleAdsAccount;
	let fetchGoogleAdsAccountStatus;
	let receiveDetailedError;

	beforeEach( () => {
		fetchCreateAccount = jest.fn();
		fetchGoogleAdsAccount = jest.fn().mockResolvedValue( {} );
		fetchGoogleAdsAccountStatus = jest.fn().mockResolvedValue( {} );
		receiveDetailedError = jest.fn();

		useGoogleAdsAccount.mockReturnValue( { googleAdsAccount: {} } );
		useApiFetchCallback.mockReturnValue( [ fetchCreateAccount, {} ] );
		useAppDispatch.mockReturnValue( {
			fetchGoogleAdsAccount,
			fetchGoogleAdsAccountStatus,
			receiveDetailedError,
		} );
	} );

	it( 'surfaces a 406 error and does not fetch account data', async () => {
		const mockResponse = {
			ok: false,
			status: 406,
			clone: jest.fn().mockReturnThis(),
		};
		fetchCreateAccount.mockRejectedValue( mockResponse );
		extractDetailedApiError.mockResolvedValue( {
			data: {
				statusCode: 406,
				message:
					'Account creation limit reached. Contact support for help.',
			},
		} );

		const { result } = renderHook( () => useUpsertAdsAccount() );
		const [ upsertAdsAccount ] = result.current;

		await act( async () => {
			await upsertAdsAccount();
		} );

		expect( receiveDetailedError ).toHaveBeenCalledWith(
			ERROR_SLOTS.GOOGLE_ADS_CONNECTION_ERROR_SLOT,
			expect.objectContaining( {
				statusCode: 406,
				message:
					'Account creation limit reached. Contact support for help.',
				title: 'Google Ads Creation Failed',
			} )
		);
		expect( fetchGoogleAdsAccount ).not.toHaveBeenCalled();
		expect( fetchGoogleAdsAccountStatus ).not.toHaveBeenCalled();
	} );

	it( 'continues without error for 428 response', async () => {
		const mockResponse = {
			ok: false,
			status: 428,
			clone: jest.fn().mockReturnThis(),
		};
		fetchCreateAccount.mockRejectedValue( mockResponse );
		// extractDetailedApiError returns null for 428 (it is in ignoredStatusCodes)
		extractDetailedApiError.mockResolvedValue( null );

		const { result } = renderHook( () => useUpsertAdsAccount() );
		const [ upsertAdsAccount ] = result.current;

		await act( async () => {
			await upsertAdsAccount();
		} );

		expect( receiveDetailedError ).not.toHaveBeenCalled();
		expect( fetchGoogleAdsAccount ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleAdsAccountStatus ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'fetches account data after a successful creation', async () => {
		fetchCreateAccount.mockResolvedValue( {} );

		const { result } = renderHook( () => useUpsertAdsAccount() );
		const [ upsertAdsAccount ] = result.current;

		await act( async () => {
			await upsertAdsAccount();
		} );

		expect( receiveDetailedError ).not.toHaveBeenCalled();
		expect( fetchGoogleAdsAccount ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleAdsAccountStatus ).toHaveBeenCalledTimes( 1 );
	} );
} );
