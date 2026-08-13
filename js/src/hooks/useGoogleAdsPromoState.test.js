/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleAdsPromoState from '~/hooks/useGoogleAdsPromoState';
import useGoogleAdsAccountReady from '~/hooks/useGoogleAdsAccountReady';
import useHasRecentAdSpend from '~/hooks/useHasRecentAdSpend';
import { getCreateCampaignUrl, getOnboardingUrl } from '~/utils/urls';

jest.mock( '~/hooks/useGoogleAdsAccountReady', () =>
	jest.fn().mockName( 'useGoogleAdsAccountReady' )
);

jest.mock( '~/hooks/useHasRecentAdSpend', () =>
	jest.fn().mockName( 'useHasRecentAdSpend' )
);

jest.mock( '~/utils/urls', () => ( {
	getCreateCampaignUrl: jest.fn( () => 'CREATE_CAMPAIGN_URL' ),
	getOnboardingUrl: jest.fn( () => 'ONBOARDING_URL' ),
} ) );

describe( 'useGoogleAdsPromoState', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'is not eligible and stays resolving until both signals settle', () => {
		useGoogleAdsAccountReady.mockReturnValue( { isGoogleAdsReady: null } );
		useHasRecentAdSpend.mockReturnValue( {
			hasAdSpend: false,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () => useGoogleAdsPromoState() );

		expect( result.current.isResolving ).toBe( true );
		expect( result.current.isEligible ).toBe( false );
	} );

	test( 'not-onboarded → eligible, not ready, "Get started" onboarding URL', () => {
		useGoogleAdsAccountReady.mockReturnValue( {
			isGoogleAdsReady: false,
		} );
		useHasRecentAdSpend.mockReturnValue( {
			hasAdSpend: false,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useGoogleAdsPromoState() );

		expect( result.current ).toEqual( {
			isResolving: false,
			isEligible: true,
			isReady: false,
			ctaUrl: 'ONBOARDING_URL',
		} );
		expect( getOnboardingUrl ).toHaveBeenCalled();
		expect( getCreateCampaignUrl ).not.toHaveBeenCalled();
	} );

	test( 'onboarded → eligible, ready, "Launch a campaign" campaign URL', () => {
		useGoogleAdsAccountReady.mockReturnValue( {
			isGoogleAdsReady: true,
		} );
		useHasRecentAdSpend.mockReturnValue( {
			hasAdSpend: false,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useGoogleAdsPromoState() );

		expect( result.current ).toEqual( {
			isResolving: false,
			isEligible: true,
			isReady: true,
			ctaUrl: 'CREATE_CAMPAIGN_URL',
		} );
		expect( getCreateCampaignUrl ).toHaveBeenCalled();
	} );

	// `INCOMPLETE` Ads accounts are folded into `isGoogleAdsReady === true` by
	// `useGoogleAdsAccountReady`, so at the gating layer they behave as ready
	// (→ "Launch a campaign"); billing is set during campaign creation.
	test( 'INCOMPLETE account is treated as ready', () => {
		useGoogleAdsAccountReady.mockReturnValue( {
			isGoogleAdsReady: true,
		} );
		useHasRecentAdSpend.mockReturnValue( {
			hasAdSpend: false,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useGoogleAdsPromoState() );

		expect( result.current.isReady ).toBe( true );
		expect( result.current.isEligible ).toBe( true );
		expect( result.current.ctaUrl ).toBe( 'CREATE_CAMPAIGN_URL' );
	} );

	test( 'suppressed when the merchant has recent Ads spend', () => {
		useGoogleAdsAccountReady.mockReturnValue( {
			isGoogleAdsReady: true,
		} );
		useHasRecentAdSpend.mockReturnValue( {
			hasAdSpend: true,
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useGoogleAdsPromoState() );

		expect( result.current.isResolving ).toBe( false );
		expect( result.current.isEligible ).toBe( false );
	} );
} );
