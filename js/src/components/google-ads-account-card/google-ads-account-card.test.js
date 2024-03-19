/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import GoogleAdsAccountCard from './google-ads-account-card';
import useGoogleAdsAccountStatus from '.~/hooks/useGoogleAdsAccountStatus';
import useGoogleAdsAccount from '.~/hooks/useGoogleAdsAccount';
import useGoogleAccount from '.~/hooks/useGoogleAccount';
import useExistingGoogleAdsAccounts from '.~/hooks/useExistingGoogleAdsAccounts';

jest.mock( '.~/hooks/useGoogleAdsAccountStatus', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useGoogleAdsAccountStatus' ),
} ) );

jest.mock( '@woocommerce/components', () => ( {
	...jest.requireActual( '@woocommerce/components' ),
	Spinner: jest
		.fn( () => <div role="status" aria-label="spinner" /> )
		.mockName( 'Spinner' ),
} ) );

jest.mock( '.~/hooks/useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' ).mockReturnValue( {} )
);

jest.mock( '.~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' ).mockReturnValue( {} )
);

jest.mock( '.~/hooks/useExistingGoogleAdsAccounts', () =>
	jest.fn().mockName( 'useExistingGoogleAdsAccounts' ).mockReturnValue( {} )
);

jest.mock( '.~/hooks/useDispatchCoreNotices', () => ( {
	__esModule: true,
	default: jest
		.fn()
		.mockName( 'useDispatchCoreNotices' )
		.mockImplementation( () => {
			return {
				createNotice: jest.fn(),
			};
		} ),
} ) );

const CONNECTED_GOOGLE_ADS_ACCOUNT = {
	id: 777777,
	currency: 'PLN',
	symbol: 'zł',
	status: 'connected',
};

describe( 'GoogleAdsAccountCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'Should show a spinner when the Google Ads status is being resolved', async () => {
		useGoogleAdsAccountStatus.mockReturnValue( {
			isResolving: true,
			hasAccess: false,
			inviteLink: null,
		} );

		render( <GoogleAdsAccountCard /> );
		const spinner = screen.getByRole( 'status', { name: 'spinner' } );
		expect( spinner ).toBeInTheDocument();
	} );

	it( 'Should show a claim account component when hasAccess is false and there is a valid Google Ads ID', async () => {
		useGoogleAdsAccountStatus.mockReturnValue( {
			hasFinishedResolution: true,
			hasAccess: false,
			isResolving: false,
			inviteLink: 'http://ads.google.com/invite',
		} );

		useGoogleAdsAccount.mockReturnValue( {
			googleAdsAccount: CONNECTED_GOOGLE_ADS_ACCOUNT,
			hasFinishedResolution: true,
		} );

		useGoogleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			isResolving: false,
			scope: {
				adsRequired: true,
			},
			google: true,
		} );

		useExistingGoogleAdsAccounts.mockReturnValue( {
			existingAccounts: [],
		} );

		render( <GoogleAdsAccountCard /> );

		expect(
			screen.getAllByText(
				'Claim your new Google Ads account to complete this setup.'
			)
		).toBeTruthy();
	} );
} );
