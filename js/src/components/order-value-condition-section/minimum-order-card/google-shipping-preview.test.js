/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import GoogleShippingPreview from './google-shipping-preview';

jest.mock( '~/hooks/useAdsCurrency', () =>
	jest.fn().mockReturnValue( {
		formatAmount: jest.fn( ( amount ) => `$${ amount.toFixed( 2 ) }` ),
	} )
);

describe( 'GoogleShippingPreview', () => {
	it( 'renders null when threshold is not provided', () => {
		const { container } = render( <GoogleShippingPreview /> );
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders null when threshold is 0', () => {
		const { container } = render(
			<GoogleShippingPreview threshold={ 0 } />
		);
		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'renders the preview when a threshold is provided', () => {
		render( <GoogleShippingPreview threshold={ 50 } /> );

		expect(
			screen.getByText(
				/Example of what your customers will see on Google:/
			)
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Ships free over $50.00' )
		).toBeInTheDocument();
	} );

	it( 'formats the threshold amount using useAdsCurrency', () => {
		render( <GoogleShippingPreview threshold={ 123.45 } /> );

		expect(
			screen.getByText( 'Ships free over $123.45' )
		).toBeInTheDocument();
	} );

	it( 'renders with the correct CSS class', () => {
		const { container } = render(
			<GoogleShippingPreview threshold={ 50 } />
		);

		expect(
			container.querySelector( '.gla-google-shipping-preview' )
		).toBeInTheDocument();
	} );
} );
