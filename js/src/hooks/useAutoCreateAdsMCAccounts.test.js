/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useAutoCreateAdsMCAccounts from './useAutoCreateAdsMCAccounts';
import useCreateMCAccount from './useCreateMCAccount';
import useUpsertAdsAccount from './useUpsertAdsAccount';
import useExistingGoogleAdsAccounts from './useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from './useExistingGoogleMCAccounts';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useGoogleMCAccount from './useGoogleMCAccount';
import useShouldCreateAdsAccount from './useShouldCreateAdsAccount';
import useShouldCreateMCAccount from './useShouldCreateMCAccount';

jest.mock( './useCreateMCAccount' );
jest.mock( './useUpsertAdsAccount' );
jest.mock( './useShouldCreateAdsAccount' );
jest.mock( './useShouldCreateMCAccount' );

describe( 'useAutoCreateAdsMCAccounts hook', () => {
	let handleCreateAccount;
	let upsertAdsAccount;

	beforeEach( () => {
		handleCreateAccount = jest.fn( () => Promise.resolve() );
		upsertAdsAccount = jest.fn( () => Promise.resolve() );

		useShouldCreateAdsAccount.mockReturnValue( true );
		useShouldCreateMCAccount.mockReturnValue( true );
	} );

	describe( 'Automatic account creation', () => {
		beforeEach( () => {
			useCreateMCAccount.mockReturnValue( [
				handleCreateAccount,
				{ response: undefined },
			] );
			useUpsertAdsAccount.mockReturnValue( [
				upsertAdsAccount,
				{ loading: true },
			] );
		} );

		it( 'should create both accounts', () => {
			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );

			// It should create both accounts.
			expect( result.current.creatingWhich ).toBe( 'both' );
		} );

		it( 'should create only the Merchant Center account', () => {
			useShouldCreateAdsAccount.mockReturnValue( false );
			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );
			// It should create only the Merchant Center account.
			expect( result.current.creatingWhich ).toBe( 'mc' );
		} );

		it( 'should create only the Google Ads account', () => {
			useShouldCreateMCAccount.mockReturnValue( false );
			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );
			// It should create only the Google Ads account.
			expect( result.current.creatingWhich ).toBe( 'ads' );
		} );
	} );

	describe( 'Existing accounts', () => {
		beforeEach( () => {
			useShouldCreateAdsAccount.mockReturnValue( false );
			useShouldCreateMCAccount.mockReturnValue( false );
		} );

		it( 'should not create accounts if they already exist', () => {
			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );

			// It should not create any accounts.
			expect( result.current.creatingWhich ).toBe( null );

			// make sure functions are not called.
			expect( handleCreateAccount ).not.toHaveBeenCalled();
			expect( upsertAdsAccount ).not.toHaveBeenCalled();
		} );
	} );
} );
