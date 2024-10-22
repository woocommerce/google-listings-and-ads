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

jest.mock( './useCreateMCAccount' );
jest.mock( './useUpsertAdsAccount' );
jest.mock( './useExistingGoogleAdsAccounts' );
jest.mock( './useExistingGoogleMCAccounts' );
jest.mock( './useGoogleAdsAccount' );
jest.mock( './useGoogleMCAccount' );

describe( 'useAutoCreateAdsMCAccounts hook', () => {
	let handleCreateAccount;
	let upsertAdsAccount;

	beforeEach( () => {
		handleCreateAccount = jest.fn( () => Promise.resolve() );
		upsertAdsAccount = jest.fn( () => Promise.resolve() );

		useGoogleAdsAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleAdsConnection: false,
		} );

		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleMCConnection: false,
		} );
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
			useExistingGoogleMCAccounts.mockReturnValue( {
				data: [],
				hasFinishedResolution: true,
			} );
			useExistingGoogleAdsAccounts.mockReturnValue( {
				existingAccounts: [],
				hasFinishedResolution: true,
			} );
		} );

		it( 'should create both accounts', () => {
			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );

			// It should create both accounts.
			expect( result.current.creatingWhichAccount ).toBe( 'both' );
		} );

		it( 'should create only the Merchant Center account', () => {
			useExistingGoogleAdsAccounts.mockReturnValue( {
				existingAccounts: [
					{
						id: 1,
						name: 'Test Ads Account',
					},
				],
				hasFinishedResolution: true,
			} );

			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );

			// It should create only the Merchant Center account.
			expect( result.current.creatingWhichAccount ).toBe( 'mc' );
		} );

		it( 'should create only the Google Ads account', () => {
			useExistingGoogleMCAccounts.mockReturnValue( {
				data: [
					{
						id: 1,
						name: 'Test MC Account',
					},
				],
				hasFinishedResolution: true,
			} );

			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );

			// It should create only the Google Ads account.
			expect( result.current.creatingWhichAccount ).toBe( 'ads' );
		} );
	} );

	describe( 'Existing accounts', () => {
		beforeEach( () => {
			useCreateMCAccount.mockReturnValue( [
				handleCreateAccount,
				{ response: { status: 200 } },
			] );
			useUpsertAdsAccount.mockReturnValue( [
				upsertAdsAccount,
				{ loading: false },
			] );
		} );

		it( 'should not create accounts if they already exist', () => {
			useExistingGoogleMCAccounts.mockReturnValue( {
				data: [
					{
						id: 1,
						name: 'Test MC Account',
					},
				],
				hasFinishedResolution: true,
			} );

			useExistingGoogleAdsAccounts.mockReturnValue( {
				existingAccounts: [
					{
						id: 1,
						name: 'Test Ads Account',
					},
				],
				hasFinishedResolution: true,
			} );

			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );

			// It should not create any accounts.
			expect( result.current.creatingWhichAccount ).toBe( null );

			// make sure functions are not called.
			expect( handleCreateAccount ).not.toHaveBeenCalled();
			expect( upsertAdsAccount ).not.toHaveBeenCalled();
		} );
	} );
} );
