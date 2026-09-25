/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useAutoCreateAdsAccount from './useAutoCreateAdsAccount';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useExistingGoogleAdsAccounts from './useExistingGoogleAdsAccounts';
import useUpsertAdsAccount from './useUpsertAdsAccount';

jest.mock( './useUpsertAdsAccount' );
jest.mock( './useGoogleAdsAccount' );
jest.mock( './useExistingGoogleAdsAccounts' );

describe( 'useAutoCreateAdsAccount hook', () => {
	let upsertAdsAccount;

	beforeEach( () => {
		upsertAdsAccount = jest.fn( () => Promise.resolve() );

		useGoogleAdsAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleAdsConnection: false,
		} );

		useExistingGoogleAdsAccounts.mockReturnValue( {
			hasFinishedResolution: true,
			existingAccounts: [],
		} );
	} );

	describe( 'Automatic account creation', () => {
		it( 'should create a Google Ads account if there is none', async () => {
			useUpsertAdsAccount.mockReturnValue( [
				upsertAdsAccount,
				{ loading: true },
			] );

			const { result } = renderHook( () => useAutoCreateAdsAccount() );
			await act( async () => {
				expect( result.current.creatingWhich ).toBe( 'ads' );
			} );

			expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'Existing accounts', () => {
		it( 'should not create any Ads accounts if they already exist', () => {
			useExistingGoogleAdsAccounts.mockReturnValue( {
				hasFinishedResolution: true,
				existingAccounts: [
					{
						id: '1234',
						name: 'Test Account',
					},
				],
			} );

			const { result } = renderHook( () => useAutoCreateAdsAccount() );

			expect( result.current.creatingWhich ).toBe( null );
			expect( upsertAdsAccount ).not.toHaveBeenCalled();
		} );
	} );
} );
