/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GoogleAdsTagIndicator from './google-ads-tag-indicator';

describe( 'GoogleAdsTagIndicator', () => {
	it( 'shows the tag as active when the plugin adds it', () => {
		render( <GoogleAdsTagIndicator enabled={ true } /> );

		expect( screen.getByText( 'Active' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Controlled externally' )
		).not.toBeInTheDocument();
	} );

	it( 'shows the tag as active when whether it is enabled is left out', () => {
		render( <GoogleAdsTagIndicator /> );

		expect( screen.getByText( 'Active' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Controlled externally' )
		).not.toBeInTheDocument();
	} );

	it( 'shows the tag as controlled externally, explaining why in a popover', async () => {
		render( <GoogleAdsTagIndicator enabled={ false } /> );

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
} );
