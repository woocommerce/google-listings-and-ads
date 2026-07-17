/**
 * External dependencies
 */
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import getGoogleAdsOverviewUrl from './getGoogleAdsOverviewUrl';

describe( 'getGoogleAdsOverviewUrl', () => {
	it( 'returns the Google Ads overview URL', () => {
		expect( getGoogleAdsOverviewUrl() ).toBe(
			'https://ads.google.com/aw/overview'
		);
	} );
} );
