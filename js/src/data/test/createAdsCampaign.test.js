/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';

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
				targeted_locations: [ 'ES' ],
				label: 'wc-android',
			},
		} );
	} );
} );
