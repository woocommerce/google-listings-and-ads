/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

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

	it( 'shows the tag as controlled externally, explaining why in a popover', async () => {
		render(
			<GoogleAdsTagRow
				conversionTag={ { id: 'AW-983746521', enabled: false } }
			/>
		);

		expect(
			screen.getByText( 'Controlled externally' )
		).toBeInTheDocument();
		expect( screen.queryByText( 'Active' ) ).not.toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'button', { name: 'Open popover' } )
		);

		expect(
			await screen.findByText(
				"Your Google Tag Manager container already has a tag that tracks Google Ads events. To avoid tracking the same events twice, Google for WooCommerce doesn't add its own Google Ads tag."
			)
		).toBeInTheDocument();
	} );

	it( 'shows the tag as active when the connection leaves out whether it is enabled', () => {
		render( <GoogleAdsTagRow conversionTag={ { id: 'AW-983746521' } } /> );

		expect( screen.getByText( 'Active' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Controlled externally' )
		).not.toBeInTheDocument();
	} );
} );
