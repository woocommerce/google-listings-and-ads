/**
 * External dependencies
 */
import { resolveSelect } from '@wordpress/data';
import { renderHook } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch, STORE_KEY } from '~/data';

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

const seedCampaign = {
	id: 12345,
	name: 'Test',
	status: 'enabled',
	targeted_locations: [],
	country: 'US',
};

describe( 'updateAdsCampaign', () => {
	const mockFetch = jest.fn();

	beforeEach( () => {
		jest.clearAllMocks();
		mockFetch.mockImplementation( ( opts ) => {
			if (
				opts.path === '/wc/gla/ads/campaigns' &&
				opts.method !== 'PATCH'
			) {
				return Promise.resolve( [ seedCampaign ] );
			}
			return Promise.resolve( {} );
		} );
		apiFetch.mockImplementation( ( args ) => mockFetch( args ) );
	} );

	it( 'When data includes brand_guidelines_enabled: true, the PATCH request body should include it', async () => {
		await resolveSelect( STORE_KEY ).getAdsCampaigns( {} );

		const { result } = renderHook( () => useAppDispatch() );

		await result.current.updateAdsCampaign( 12345, {
			amount: 100,
			brand_guidelines_enabled: true,
		} );

		expect( mockFetch ).toHaveBeenCalledWith( {
			path: '/wc/gla/ads/campaigns/12345',
			method: 'PATCH',
			data: {
				amount: 100,
				brand_guidelines_enabled: true,
			},
		} );
	} );

	it( 'When data includes brand_guidelines_enabled: false, the PATCH request body should include it', async () => {
		await resolveSelect( STORE_KEY ).getAdsCampaigns( {} );

		const { result } = renderHook( () => useAppDispatch() );

		await result.current.updateAdsCampaign( 12345, {
			brand_guidelines_enabled: false,
		} );

		expect( mockFetch ).toHaveBeenCalledWith( {
			path: '/wc/gla/ads/campaigns/12345',
			method: 'PATCH',
			data: {
				brand_guidelines_enabled: false,
			},
		} );
	} );
} );
