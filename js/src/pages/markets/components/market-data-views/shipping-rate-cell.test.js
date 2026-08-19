/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ShippingRateCell from './shipping-rate-cell';

jest.mock( './formatted-amount', () =>
	jest.fn( ( { amount, currencyCode } ) => (
		<span data-testid="formatted-amount">
			{ currencyCode } { amount }
		</span>
	) )
);

describe( 'ShippingRateCell', () => {
	test( 'renders "-" when the market has no shipping configured', () => {
		render( <ShippingRateCell market={ {} } /> );

		expect( screen.getByText( '-' ) ).toBeInTheDocument();
	} );

	test( 'renders "-" when flat_rate is null', () => {
		render(
			<ShippingRateCell market={ { shipping: { flat_rate: null } } } />
		);

		expect( screen.getByText( '-' ) ).toBeInTheDocument();
	} );

	test( 'formats a zero flat_rate as an amount rather than "-"', () => {
		render(
			<ShippingRateCell
				market={ {
					shipping: { flat_rate: 0, currency: 'USD' },
				} }
			/>
		);

		expect( screen.getByTestId( 'formatted-amount' ) ).toHaveTextContent(
			'USD 0'
		);
	} );

	test( 'formats the flat rate using the currency the rate is stored in, not the market currency', () => {
		render(
			<ShippingRateCell
				market={ {
					shipping: { flat_rate: 8, currency: 'USD' },
					currency: [ 'EUR' ],
				} }
			/>
		);

		expect( screen.getByTestId( 'formatted-amount' ) ).toHaveTextContent(
			'USD 8'
		);
	} );
} );
