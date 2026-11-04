/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GoogleAdsTagRow from './google-ads-tag-row';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);

describe( 'GoogleAdsTagRow', () => {
	beforeEach( () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );
		useGoogleTagManagerAccount.mockReturnValue( {
			account: {
				status: 'connected',
				id: '6002847391',
				containerId: '98765432',
			},
		} );
	} );

	it( 'shows the tag as active, linked to the connected container', () => {
		render(
			<GoogleAdsTagRow
				conversionTag={ { id: 'AW-983746521', enabled: true } }
			/>
		);

		expect( screen.getByText( 'Google Ads tag' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Active' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'AW-983746521 (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://tagmanager.google.com/#/container/accounts/6002847391/containers/98765432'
		);
	} );

	it( 'shows the tag ID as plain text without a connected container', () => {
		useGoogleTagManagerAccount.mockReturnValue( {
			account: { status: 'disconnected' },
		} );

		render(
			<GoogleAdsTagRow
				conversionTag={ { id: 'AW-983746521', enabled: true } }
			/>
		);

		expect( screen.getByText( 'AW-983746521' ) ).toBeInTheDocument();
		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();
	} );

	it( 'passes whether the tag is enabled to the indicator', () => {
		render(
			<GoogleAdsTagRow
				conversionTag={ { id: 'AW-983746521', enabled: false } }
			/>
		);

		expect(
			screen.getByText( 'Controlled externally' )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Active' ) ).not.toBeInTheDocument();
	} );
} );
