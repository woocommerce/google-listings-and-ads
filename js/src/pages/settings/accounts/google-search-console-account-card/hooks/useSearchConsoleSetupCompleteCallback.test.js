/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useSearchConsoleSetupCompleteCallback from './useSearchConsoleSetupCompleteCallback';

jest.mock( '~/data' );
jest.mock( '~/hooks/useApiFetchCallback' );

describe( 'useSearchConsoleSetupCompleteCallback', () => {
	let fetchGoogleSearchConsoleAccount;
	let fetchCompleteSetup;
	let fetchResult;

	beforeEach( () => {
		fetchGoogleSearchConsoleAccount = jest
			.fn()
			.mockResolvedValue( undefined );
		fetchCompleteSetup = jest.fn().mockResolvedValue( undefined );
		fetchResult = {
			loading: false,
			error: undefined,
		};

		useAppDispatch.mockReturnValue( { fetchGoogleSearchConsoleAccount } );
		useApiFetchCallback.mockReturnValue( [
			fetchCompleteSetup,
			fetchResult,
		] );
	} );

	it( 'completes setup and refetches the Search Console account', async () => {
		const { result } = renderHook( () =>
			useSearchConsoleSetupCompleteCallback()
		);

		await act( async () => {
			await result.current[ 0 ]();
		} );

		expect( fetchCompleteSetup ).toHaveBeenCalledTimes( 1 );
		expect( fetchGoogleSearchConsoleAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not refetch the account when the request fails', async () => {
		fetchCompleteSetup.mockRejectedValue( new Error( 'network error' ) );

		const { result } = renderHook( () =>
			useSearchConsoleSetupCompleteCallback()
		);

		await act( async () => {
			await result.current[ 0 ]();
		} );

		expect( fetchGoogleSearchConsoleAccount ).not.toHaveBeenCalled();
	} );
} );
