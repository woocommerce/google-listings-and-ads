/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useAutoCreateAdsMCAccounts from './useAutoCreateAdsMCAccounts';
import useGoogleAdsAccount from './useGoogleAdsAccount';
import useExistingGoogleAdsAccounts from './useExistingGoogleAdsAccounts';
import useGoogleMCAccount from './useGoogleMCAccount';
import useExistingGoogleMCAccounts from './useExistingGoogleMCAccounts';
import useCreateMCAccount from './useCreateMCAccount';
import useUpsertAdsAccount from './useUpsertAdsAccount';

jest.mock( './useCreateMCAccount' );
jest.mock( './useUpsertAdsAccount' );
jest.mock( './useGoogleAdsAccount' );
jest.mock( './useExistingGoogleAdsAccounts' );
jest.mock( './useGoogleMCAccount' );
jest.mock( './useExistingGoogleMCAccounts' );

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

		useExistingGoogleAdsAccounts.mockReturnValue( {
			hasFinishedResolution: true,
			existingAccounts: [],
		} );

		useGoogleMCAccount.mockReturnValue( {
			hasFinishedResolution: true,
			hasGoogleMCConnection: false,
		} );

		useExistingGoogleMCAccounts.mockReturnValue( {
			hasFinishedResolution: true,
			data: [],
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
		} );

		it( 'should create both accounts', async () => {
			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );

			await act( async () => {
				expect( result.current.creatingWhich ).toBe( 'both' );
			} );

			expect( handleCreateAccount ).toHaveBeenCalledTimes( 1 );
			expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'should create only the Merchant Center account', async () => {
			useExistingGoogleAdsAccounts.mockReturnValue( {
				hasFinishedResolution: true,
				existingAccounts: [
					{
						id: '1234',
						name: 'Test Account',
					},
				],
			} );

			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );
			await act( async () => {
				expect( result.current.creatingWhich ).toBe( 'mc' );
			} );

			expect( handleCreateAccount ).toHaveBeenCalledTimes( 1 );
			expect( upsertAdsAccount ).toHaveBeenCalledTimes( 0 );
		} );

		it( 'should create only the Google Ads account', async () => {
			useExistingGoogleMCAccounts.mockReturnValue( {
				hasFinishedResolution: true,
				data: [
					{
						id: '12345',
						name: 'Test Account',
					},
				],
			} );

			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );
			await act( async () => {
				expect( result.current.creatingWhich ).toBe( 'ads' );
			} );

			expect( handleCreateAccount ).toHaveBeenCalledTimes( 0 );
			expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );
		} );
	} );

	describe( 'Existing accounts', () => {
		beforeEach( () => {
			useExistingGoogleAdsAccounts.mockReturnValue( {
				hasFinishedResolution: true,
				existingAccounts: [
					{
						id: '1234',
						name: 'Test Account',
					},
				],
			} );

			useExistingGoogleMCAccounts.mockReturnValue( {
				hasFinishedResolution: true,
				data: [
					{
						id: '12345',
						name: 'Test Account',
					},
				],
			} );
		} );

		it( 'should not create accounts if they already exist', () => {
			const { result } = renderHook( () => useAutoCreateAdsMCAccounts() );

			expect( result.current.creatingWhich ).toBe( null );
			expect( handleCreateAccount ).not.toHaveBeenCalled();
			expect( upsertAdsAccount ).not.toHaveBeenCalled();
		} );
	} );
} );
