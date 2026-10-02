/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import MarketNotice from './index';

jest.mock( '~/components/app-notice', () => ( { children } ) => (
	<div>{ children }</div>
) );

describe( 'MarketNotice', () => {
	it( 'renders the shippingManaged variant', () => {
		render( <MarketNotice variant="shippingManaged" /> );
		expect(
			screen.getByText( 'Shipping managed by Google' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Your shipping settings are managed through Google Merchant Center.'
			)
		).toBeInTheDocument();
	} );

	it( 'renders the automaticShipping variant', () => {
		render( <MarketNotice variant="automaticShipping" /> );
		expect(
			screen.getByText( 'Automatic shipping' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Shipping rates and times are automatically synced from your store settings.'
			)
		).toBeInTheDocument();
	} );

	it( 'renders nothing for an unrecognised variant', () => {
		const { container } = render( <MarketNotice variant="unknown" /> );
		expect( container.firstChild ).toBeNull();
	} );

	it( 'renders nothing when variant is omitted', () => {
		const { container } = render( <MarketNotice /> );
		expect( container.firstChild ).toBeNull();
	} );
} );
