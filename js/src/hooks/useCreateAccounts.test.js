/* eslint-disable testing-library/no-unnecessary-act */
/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useCreateAccounts from './useCreateAccounts';
import useCreateMCAccount from '../components/google-mc-account-card/useCreateMCAccount';
import useUpsertAdsAccount from './useUpsertAdsAccount';
import useExistingGoogleAdsAccounts from './useExistingGoogleAdsAccounts';
import useExistingGoogleMCAccounts from './useExistingGoogleMCAccounts';
import useGoogleAdsAccount from './useGoogleAdsAccount';

jest.mock( '../components/google-mc-account-card/useCreateMCAccount' );
jest.mock( './useUpsertAdsAccount' );
jest.mock( './useExistingGoogleAdsAccounts' );
jest.mock( './useExistingGoogleMCAccounts' );
jest.mock( './useGoogleAdsAccount' );

describe( 'useCreateAccounts hook', () => {
	let handleCreateAccount;
	let upsertAdsAccount;

	beforeEach( () => {
		handleCreateAccount = jest.fn( () => Promise.resolve() );
		upsertAdsAccount = jest.fn( () => Promise.resolve() );

		useCreateMCAccount.mockReturnValue( [
			handleCreateAccount,
			{ data: undefined, response: { status: 200 } },
		] );
		useUpsertAdsAccount.mockReturnValue( [
			upsertAdsAccount,
			{ loading: false },
		] );
		useGoogleAdsAccount.mockReturnValue( {
			googleAdsAccount: { id: 0 },
			hasFinishedResolution: true,
		} );
		useExistingGoogleMCAccounts.mockReturnValue( {
			data: [],
			hasFinishedResolution: true,
		} );
		useExistingGoogleAdsAccounts.mockReturnValue( {
			existingAccounts: [],
			isResolving: false,
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
			isResolving: false,
		} );

		const { result } = renderHook( () => useCreateAccounts() );

		expect( result.current.isCreatingAccounts ).toBe( false );
		expect( result.current.accountsCreated ).toBe( false );
		expect( handleCreateAccount ).not.toHaveBeenCalled();
		expect( upsertAdsAccount ).not.toHaveBeenCalled();
	} );

	it( 'should call "handleCreateAccount" and "upsertAdsAccount" when there are no existing accounts', async () => {
		// Simulate the initial state and mock behavior for account creation
		const { result, rerender } = renderHook( () => useCreateAccounts() );

		expect( result.current.isCreatingAccounts ).toBe( false );

		useCreateMCAccount.mockReturnValueOnce( [
			handleCreateAccount,
			{ data: {}, response: { status: 200 } },
		] );
		useUpsertAdsAccount.mockReturnValueOnce( [
			upsertAdsAccount,
			{ loading: false },
		] );

		await act( async () => {
			rerender(); // Trigger the effect that begins account creation
		} );

		// At this point, isCreatingAccounts should be true
		expect( result.current.isCreatingAccounts ).toBe( true );

		await act( async () => {
			rerender(); // Trigger the effect that begins account creation
		} );

		expect( result.current.isCreatingAccounts ).toBe( false );
		expect( result.current.accountsCreated ).toBe( true );

		// Finally, check that the functions were called correctly
		expect( handleCreateAccount ).toHaveBeenCalledTimes( 1 );
		expect( upsertAdsAccount ).toHaveBeenCalledTimes( 1 );
	} );
} );
