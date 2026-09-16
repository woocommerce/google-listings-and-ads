/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GoogleAdsAccountCard from './google-ads-account-card';
import useGoogleAdsAccount from '~/hooks/useGoogleAdsAccount';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '~/constants';

jest.mock( '~/hooks/useGoogleAdsAccount' );

describe( 'GoogleAdsAccountCard', () => {
	it( 'renders without crashing when the Ads account is not connected', () => {
		// `googleAdsAccount` is `undefined` whenever the Google account isn't
		// connected yet — the default state for a partially-onboarded store.
		useGoogleAdsAccount.mockReturnValue( { googleAdsAccount: undefined } );

		expect( () => render( <GoogleAdsAccountCard /> ) ).not.toThrow();
		expect( screen.queryByText( 'Connected' ) ).not.toBeInTheDocument();
	} );

	it( 'shows the formatted account ID and badge when connected', () => {
		useGoogleAdsAccount.mockReturnValue( {
			googleAdsAccount: {
				id: 5647863919,
				status: GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED,
			},
		} );

		render( <GoogleAdsAccountCard /> );

		expect( screen.getByText( '564-786-3919' ) ).toBeVisible();
		expect( screen.getByText( 'Connected' ) ).toBeVisible();
	} );
} );
