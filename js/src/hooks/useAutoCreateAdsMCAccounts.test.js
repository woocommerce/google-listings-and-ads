/* eslint-disable testing-library/no-unnecessary-act */
/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useCreateAccounts from './useAutoCreateAdsMCAccounts';
import useCreateMCAccount from '../components/google-mc-account-card/useCreateMCAccount';
import useUpsertAdsAccount from './useUpsertAdsAccount';
import useExistingGoogleAdsAccounts from './useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from './useExistingGoogleMCAccounts';

jest.mock( '../components/google-mc-account-card/useCreateMCAccount' );
jest.mock( './useUpsertAdsAccount' );
jest.mock( './useExistingGoogleAdsAccounts' );
jest.mock( './useExistingGoogleMCAccounts' );
jest.mock( './useGoogleAdsAccount' );

describe( 'useAutoCreateAdsMCAccounts hook', () => {
	let handleCreateAccount;
	let upsertAdsAccount;

	beforeEach( () => {
		handleCreateAccount = jest.fn( () => Promise.resolve() );
		upsertAdsAccount = jest.fn( () => Promise.resolve() );

		useCreateMCAccount.mockReturnValue( [
			handleCreateAccount,
			{ response: { status: 200 } },
		] );
		useUpsertAdsAccount.mockReturnValue( [
			upsertAdsAccount,
			{ loading: false },
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

	it( 'should not call "handleCreateAccount" and "upsertAdsAccount" when there are existing accounts', () => {
		// Simulate existing accounts
		useExistingGoogleMCAccounts.mockReturnValue( {
			data: [ { id: 1 } ],
			hasFinishedResolution: true,
		} );
		useExistingGoogleAdsAccounts.mockReturnValue( {
			existingAccounts: [ { id: 1 } ],
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () => useCreateAccounts() );

		expect( result.current.isCreatingAdsAccount ).toBe( false );
		expect( result.current.isCreatingMCAccount ).toBe( false );
		expect( result.current.accountsCreated ).toBe( false );
		expect( handleCreateAccount ).not.toHaveBeenCalled();
		expect( upsertAdsAccount ).not.toHaveBeenCalled();
	} );

	it( 'should call "handleCreateAccount" and "upsertAdsAccount" when there are no existing accounts', async () => {
		// Simulate the initial state and mock behavior for account creation
		const { result, rerender } = renderHook( () => useCreateAccounts() );

		expect( result.current.isCreatingAdsAccount ).toBe( false );
		expect( result.current.isCreatingMCAccount ).toBe( false );

		useCreateMCAccount.mockReturnValueOnce( [
			handleCreateAccount,
			{ response: { status: 200 } },
		] );
		useUpsertAdsAccount.mockReturnValueOnce( [
			upsertAdsAccount,
			{ loading: false },
		] );

		await act( async () => {
			rerender(); // Trigger the effect that begins account creation
		} );

		expect( result.current.isCreatingAdsAccount ).toBe( false );
		expect( result.current.isCreatingMCAccount ).toBe( false );

		await act( async () => {
			rerender();
		} );

		expect( result.current.isCreatingAdsAccount ).toBe( false );
		expect( result.current.isCreatingMCAccount ).toBe( false );
		expect( result.current.accountsCreated ).toBe( true );

		// Finally, check that the functions were called correctly
		expect( handleCreateAccount ).toHaveBeenCalledTimes( 1 );
		expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should create Merchant Center account only when there is no existing MC account, but there is an existing Ads account', async () => {
		useExistingGoogleAdsAccounts.mockReturnValue( {
			existingAccounts: [ { id: 1 } ], // Existing Ads account
			hasFinishedResolution: true,
		} );

		// Step 1: Initial render - No response, loading is true
		useCreateMCAccount.mockReturnValueOnce( [
			handleCreateAccount,
			{ response: undefined, loading: true }, // Initially no response, loading is true
		] );

		const { result, rerender } = renderHook( () => useCreateAccounts() );

		// Initially, it should not be creating accounts
		expect( result.current.isCreatingAdsAccount ).toBe( false );
		expect( result.current.isCreatingMCAccount ).toBe( false );

		// Trigger the effect that starts account creation
		await act( async () => {
			rerender(); // Simulate the effect firing
		} );

		// Step 2: At this point, MC account creation should have started
		expect( result.current.isCreatingMCAccount ).toBe( true );
		expect( result.current.isCreatingAdsAccount ).toBe( false ); // Since Ads account exists
		expect( handleCreateAccount ).toHaveBeenCalledTimes( 1 );
		expect( upsertAdsAccount ).not.toHaveBeenCalled();

		// Step 3: Simulate the response for MC account creation
		useCreateMCAccount.mockReturnValueOnce( [
			handleCreateAccount,
			{ response: { status: 200 }, loading: false }, // Now account creation is complete
		] );

		// Trigger a rerender to simulate the async function resolving
		await act( async () => {
			rerender(); // Re-render after response has been updated
		} );

		// Step 4: Final assertions after account creation has completed
		expect( result.current.isCreatingAdsAccount ).toBe( false );
		expect( result.current.isCreatingMCAccount ).toBe( false );
		expect( result.current.accountsCreated ).toBe( true ); // The account has been created

		// Finally, verify that only handleCreateAccount was called, not upsertAdsAccount
		expect( handleCreateAccount ).toHaveBeenCalledTimes( 1 );
		expect( upsertAdsAccount ).not.toHaveBeenCalled();
	} );

	it( 'should create Ads account only when there is no existing Ads account, but there is an existing Merchant Center account', async () => {
		// Simulate existing MC account but no existing Ads account
		useExistingGoogleMCAccounts.mockReturnValue( {
			data: [ { id: 1 } ], // Existing MC account
			hasFinishedResolution: true,
		} );

		// Step 1: Initial render - No response, loading is true for Ads account creation
		useUpsertAdsAccount.mockReturnValueOnce( [
			upsertAdsAccount,
			{ response: undefined, loading: true }, // Initially no response, loading is true for Ads account creation
		] );

		const { result, rerender } = renderHook( () => useCreateAccounts() );

		// Initially, it should not be creating accounts
		expect( result.current.isCreatingAdsAccount ).toBe( false );
		expect( result.current.isCreatingMCAccount ).toBe( false );

		// Trigger the effect that starts account creation
		await act( async () => {
			rerender(); // Simulate the effect firing
		} );

		// Step 2: At this point, Ads account creation should have started
		expect( result.current.isCreatingAdsAccount ).toBe( true );
		expect( result.current.isCreatingMCAccount ).toBe( false ); // Since MC account exists
		expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );
		expect( handleCreateAccount ).not.toHaveBeenCalled(); // MC account creation shouldn't be triggered

		// Step 3: Simulate the response for Ads account creation
		useUpsertAdsAccount.mockReturnValueOnce( [
			upsertAdsAccount,
			{ response: { status: 200 }, loading: false }, // Now Ads account creation is complete
		] );

		// Trigger a rerender to simulate the async function resolving
		await act( async () => {
			rerender(); // Re-render after response has been updated
		} );

		// Step 4: Final assertions after Ads account creation has completed
		expect( result.current.accountsCreated ).toBe( true ); // The account has been created
		expect( result.current.isCreatingAdsAccount ).toBe( false ); // Ads account creation finished

		// Finally, verify that only upsertAdsAccount was called, not handleCreateAccount
		expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );
		expect( handleCreateAccount ).not.toHaveBeenCalled();
	} );
} );
