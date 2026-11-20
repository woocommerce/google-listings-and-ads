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
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import { GOOGLE_ADS_ACCOUNT_STATUS } from '~/constants';

jest.mock( '~/hooks/useGoogleAdsAccount' );
jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);

const connectedTagManagerAccount = {
	status: 'connected',
	id: '6002847391',
	containerId: '98765432',
};

/**
 * Mocks a connected Google Ads account reporting the given plugin-owned Google Ads tag.
 *
 * @param {Object} [conversionTag] The `conversion_tag` field of the Ads connection.
 */
function mockAdsAccount( conversionTag ) {
	useGoogleAdsAccount.mockReturnValue( {
		googleAdsAccount: {
			id: 5647863919,
			status: GOOGLE_ADS_ACCOUNT_STATUS.CONNECTED,
			conversion_tag: conversionTag,
		},
	} );
}

describe( 'GoogleAdsAccountCard', () => {
	beforeEach( () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );
		useGoogleTagManagerAccount.mockReturnValue( {
			account: connectedTagManagerAccount,
		} );
	} );

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

	it( 'shows no Google Ads tag row while the connection does not report the tag', () => {
		mockAdsAccount( undefined );

		render( <GoogleAdsAccountCard /> );

		expect(
			screen.queryByText( 'Google Ads tag' )
		).not.toBeInTheDocument();
	} );

	// The row's states are covered in `google-ads-tag-row.test.js`.
	it( 'shows the Google Ads tag row once the connection reports the tag', () => {
		mockAdsAccount( { id: 'AW-983746521', enabled: true } );

		render( <GoogleAdsAccountCard /> );

		expect( screen.getByText( 'Google Ads tag' ) ).toBeVisible();
		expect(
			screen.getByRole( 'link', {
				name: 'AW-983746521 (opens in a new tab)',
			} )
		).toBeVisible();
	} );
} );
