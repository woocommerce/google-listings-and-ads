/**
 * External dependencies
 */
import { renderHook, act } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useActionedCampaignsCache from './useActionedCampaignsCache';
import localStorage from '~/utils/localStorage';

// Mocks
jest.mock( '~/constants', () => ( {
	LOCAL_STORAGE_KEYS: { RAISE_BUDGET_RECOMMENDATIONS_ACTIONED: 'test-key' },
	DAY_IN_SECONDS: 86400,
} ) );

jest.mock( '~/utils/localStorage', () => {
	return {
		get: jest.fn(),
		set: jest.fn(),
	};
} );

describe( 'useActionedCampaignsCache', () => {
	const storageKey = 'test-key';
	const ttl = 10000;

	beforeEach( () => {
		localStorage.get.mockReset();
		jest.clearAllMocks();
		jest.spyOn( Date, 'now' ).mockImplementation( () => 100000 );
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'should initialize with empty campaigns if localStorage is empty', () => {
		const { result } = renderHook( () =>
			useActionedCampaignsCache( storageKey, ttl )
		);
		expect( result.current.campaigns ).toEqual( [] );
		expect( localStorage.get ).toHaveBeenCalledWith( storageKey );
	} );

	it( 'should filter out expired campaigns and update localStorage', () => {
		const validCampaign = { campaign: '1', expiry: 200000 };
		const expiredCampaign = { campaign: '2', expiry: 50000 };
		localStorage.get.mockReturnValue(
			JSON.stringify( [ validCampaign, expiredCampaign ] )
		);

		const { result } = renderHook( () =>
			useActionedCampaignsCache( storageKey, ttl )
		);
		expect( result.current.campaigns ).toEqual( [
			validCampaign.campaign,
		] );
		expect( localStorage.set ).toHaveBeenCalledWith(
			storageKey,
			JSON.stringify( [ validCampaign ] )
		);
	} );

	it( 'should upsert a new campaign', () => {
		const { result } = renderHook( () =>
			useActionedCampaignsCache( storageKey, ttl )
		);
		act( () => {
			result.current.upsertActionedCampaign( 'new-campaign' );
		} );
		expect( localStorage.set ).toHaveBeenCalledWith(
			storageKey,
			JSON.stringify( [
				{ campaign: 'new-campaign', expiry: Date.now() + ttl },
			] )
		);
	} );

	it( 'should update expiry for existing campaign', () => {
		const oldExpiry = 50000;
		const campaign = { campaign: 'existing', expiry: oldExpiry };
		localStorage.set( storageKey, JSON.stringify( [ campaign ] ) );

		const { result } = renderHook( () =>
			useActionedCampaignsCache( storageKey, ttl )
		);
		act( () => {
			result.current.upsertActionedCampaign( 'existing' );
		} );
		expect( localStorage.set ).toHaveBeenCalledWith(
			storageKey,
			JSON.stringify( [
				{ campaign: 'existing', expiry: Date.now() + ttl },
			] )
		);
	} );

	it( 'should handle invalid JSON in localStorage gracefully', () => {
		localStorage.set( storageKey, 'not-json' );
		const { result } = renderHook( () =>
			useActionedCampaignsCache( storageKey, ttl )
		);
		expect( result.current.campaigns ).toEqual( [] );
	} );
} );
