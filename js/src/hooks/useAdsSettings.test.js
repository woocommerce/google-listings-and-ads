/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import useAdsSettings from '~/hooks/useAdsSettings';

const mockGetAdsSettings = jest.fn().mockName( 'getAdsSettings' );
const mockHasFinishedResolution = jest.fn().mockName( 'hasFinishedResolution' );

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

jest.mock( '~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' )
);

describe( 'useAdsSettings', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getAdsSettings: mockGetAdsSettings,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) )
		);
	} );

	test( 'reads and returns the ads settings when a Google Ads account is connected', () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: true,
			hasFinishedResolution: false,
		} );
		mockGetAdsSettings.mockReturnValue( {
			ads_has_unclaimed_incentive: true,
		} );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () => useAdsSettings() );

		expect( mockGetAdsSettings ).toHaveBeenCalled();
		expect( result.current ).toEqual( {
			adsSettings: { ads_has_unclaimed_incentive: true },
			hasFinishedResolution: true,
		} );
	} );

	test( 'skips reading ads settings and returns null when there is no connected Google Ads account', () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: false,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useAdsSettings() );

		expect( mockGetAdsSettings ).not.toHaveBeenCalled();
		expect( mockHasFinishedResolution ).not.toHaveBeenCalled();
		expect( result.current ).toEqual( {
			adsSettings: null,
			hasFinishedResolution: true,
		} );
	} );

	test( 'reflects the Google Ads account resolution state while disconnected state is still resolving', () => {
		useGoogleAdsAccount.mockReturnValue( {
			hasGoogleAdsConnection: false,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () => useAdsSettings() );

		expect( mockGetAdsSettings ).not.toHaveBeenCalled();
		expect( result.current ).toEqual( {
			adsSettings: null,
			hasFinishedResolution: false,
		} );
	} );
} );
