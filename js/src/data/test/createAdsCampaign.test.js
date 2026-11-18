/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

describe( 'createAdsCampaign', () => {
	let navigatorGetter;

	const mockFetch = jest
		.fn()
		.mockResolvedValue( { targeted_locations: [ 'ES' ] } );

	beforeEach( () => {
		jest.clearAllMocks();
		apiFetch.mockImplementation( ( args ) => {
			return mockFetch( args );
		} );

		navigatorGetter = jest.spyOn( window.navigator, 'userAgent', 'get' );
	} );

	it( 'If the user agent is not set to wc-ios or wc-android, the label should default to wc-web', async () => {
		const { result } = renderHook( () => useAppDispatch() );

		await result.current.createAdsCampaign( 100, [ 'ES' ] );

		expect( mockFetch ).toHaveBeenCalledTimes( 1 );
		expect( mockFetch ).toHaveBeenCalledWith( {
			path: '/wc/gla/ads/campaigns',
			method: 'POST',
			data: {
				amount: 100,
				eu_political_advertising_confirmation: false,
				targeted_locations: [ 'ES' ],
				label: 'wc-web',
			},
		} );
	} );

	it( 'If the user agent is set to wc-ios the label should be wc-ios', async () => {
		navigatorGetter.mockReturnValue(
			'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 wc-ios/19.7.1'
		);

		const { result } = renderHook( () => useAppDispatch() );

		await result.current.createAdsCampaign( 100, [ 'ES' ] );

		expect( mockFetch ).toHaveBeenCalledTimes( 1 );
		expect( mockFetch ).toHaveBeenCalledWith( {
			path: '/wc/gla/ads/campaigns',
			method: 'POST',
			data: {
				amount: 100,
				eu_political_advertising_confirmation: false,
				targeted_locations: [ 'ES' ],
				label: 'wc-ios',
			},
		} );
	} );

	it( 'If the user agent is set to wc-android the label should be wc-android', async () => {
		navigatorGetter.mockReturnValue(
			'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 wc-android/19.7.1'
		);

		const { result } = renderHook( () => useAppDispatch() );

		await result.current.createAdsCampaign( 100, [ 'ES' ] );

		expect( mockFetch ).toHaveBeenCalledTimes( 1 );
		expect( mockFetch ).toHaveBeenCalledWith( {
			path: '/wc/gla/ads/campaigns',
			method: 'POST',
			data: {
				amount: 100,
				eu_political_advertising_confirmation: false,
				targeted_locations: [ 'ES' ],
				label: 'wc-android',
			},
		} );
	} );

	it( 'Should send the campaign name and advertising source when they are given', async () => {
		const { result } = renderHook( () => useAppDispatch() );

		await result.current.createAdsCampaign( 100, [ 'ES' ], false, {
			name: 'Summer sale',
			advertisingSource: 'product',
		} );

		expect( mockFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				data: expect.objectContaining( {
					name: 'Summer sale',
					advertising_source: 'product',
				} ),
			} )
		);
	} );

	it.each( [ '', '   ' ] )(
		'Should leave the name out when it is blank (%j)',
		async ( name ) => {
			const { result } = renderHook( () => useAppDispatch() );

			await result.current.createAdsCampaign( 100, [ 'ES' ], false, {
				name,
				advertisingSource: 'page',
			} );

			const { data } = mockFetch.mock.calls[ 0 ][ 0 ];

			expect( data ).not.toHaveProperty( 'name' );
			expect( data.advertising_source ).toBe( 'page' );
		}
	);
} );

describe( 'createAdsWithAssetsCampaign', () => {
	const assets = {
		final_url: 'https://example.com/services',
		assets: [],
		path1: 'book',
		path2: 'now',
	};

	beforeEach( () => {
		jest.clearAllMocks();
		apiFetch.mockResolvedValue( { targeted_locations: [ 'ES' ] } );
	} );

	it( 'Should send the campaign name and advertising source with the assets when they are given', async () => {
		const { result } = renderHook( () => useAppDispatch() );

		await result.current.createAdsWithAssetsCampaign(
			100,
			[ 'ES' ],
			assets,
			false,
			{ name: 'Services', advertisingSource: 'page' }
		);

		expect( apiFetch ).toHaveBeenCalledWith(
			expect.objectContaining( {
				data: expect.objectContaining( {
					final_url: 'https://example.com/services',
					name: 'Services',
					advertising_source: 'page',
				} ),
			} )
		);
	} );

	it( 'Should not add the campaign name or advertising source when they are not given', async () => {
		const { result } = renderHook( () => useAppDispatch() );

		await result.current.createAdsWithAssetsCampaign(
			100,
			[ 'ES' ],
			assets
		);

		const { data } = apiFetch.mock.calls[ 0 ][ 0 ];

		expect( data ).not.toHaveProperty( 'name' );
		expect( data ).not.toHaveProperty( 'advertising_source' );
	} );

	it( 'Should leave a blank name out', async () => {
		const { result } = renderHook( () => useAppDispatch() );

		await result.current.createAdsWithAssetsCampaign(
			100,
			[ 'ES' ],
			assets,
			false,
			{ name: '  ', advertisingSource: 'page' }
		);

		expect( apiFetch.mock.calls[ 0 ][ 0 ].data ).not.toHaveProperty(
			'name'
		);
	} );
} );
