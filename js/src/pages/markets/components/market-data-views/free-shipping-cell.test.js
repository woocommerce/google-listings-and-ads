/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import FreeShippingCell from './free-shipping-cell';

jest.mock( './formatted-amount', () =>
	jest.fn( ( { amount, currencyCode } ) => (
		<span data-testid="formatted-amount">
			{ currencyCode } { amount }
		</span>
	) )
);

describe( 'FreeShippingCell', () => {
	test( 'renders "-" when the market has no shipping configured', () => {
		render( <FreeShippingCell market={ {} } /> );

		expect( screen.getByText( '-' ) ).toBeInTheDocument();
	} );

	test( 'renders "-" when flat_rate is null', () => {
		render(
			<FreeShippingCell market={ { shipping: { flat_rate: null } } } />
		);

		expect( screen.getByText( '-' ) ).toBeInTheDocument();
	} );

	test( 'renders "Free" when flat_rate is 0', () => {
		render(
			<FreeShippingCell market={ { shipping: { flat_rate: 0 } } } />
		);

		expect( screen.getByText( 'Free' ) ).toBeInTheDocument();
	} );

	test( "renders the threshold amount using the market's own currency when set", () => {
		render(
			<FreeShippingCell
				market={ {
					shipping: { flat_rate: 5, free_shipping_threshold: 50 },
					currency: [ 'EUR' ],
				} }
			/>
		);

		expect( screen.getByText( /Over/ ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'formatted-amount' ) ).toHaveTextContent(
			'EUR 50'
		);
	} );

	test( 'renders "-" when a non-free rate has no threshold', () => {
		render(
			<FreeShippingCell market={ { shipping: { flat_rate: 5 } } } />
		);

		expect( screen.getByText( '-' ) ).toBeInTheDocument();
	} );
} );
