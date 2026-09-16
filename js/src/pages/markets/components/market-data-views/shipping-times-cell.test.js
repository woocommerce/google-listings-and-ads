/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ShippingTimesCell from './shipping-times-cell';

describe( 'ShippingTimesCell', () => {
	test( 'renders "-" when the market has no shipping configured', () => {
		render( <ShippingTimesCell market={ {} } /> );

		expect( screen.getByText( '-' ) ).toBeInTheDocument();
	} );

	test( 'renders "-" when only one of flat_time/flat_max_time is set', () => {
		render(
			<ShippingTimesCell
				market={ { shipping: { flat_time: 2, flat_max_time: null } } }
			/>
		);

		expect( screen.getByText( '-' ) ).toBeInTheDocument();
	} );

	test( 'renders "Same day" when both flat_time and flat_max_time are 0', () => {
		render(
			<ShippingTimesCell
				market={ { shipping: { flat_time: 0, flat_max_time: 0 } } }
			/>
		);

		expect( screen.getByText( 'Same day' ) ).toBeInTheDocument();
	} );

	test( 'renders singular "1 day" when min equals max and is 1', () => {
		render(
			<ShippingTimesCell
				market={ { shipping: { flat_time: 1, flat_max_time: 1 } } }
			/>
		);

		expect( screen.getByText( '1 day' ) ).toBeInTheDocument();
	} );

	test( 'renders plural "N days" when min equals max and is greater than 1', () => {
		render(
			<ShippingTimesCell
				market={ { shipping: { flat_time: 3, flat_max_time: 3 } } }
			/>
		);

		expect( screen.getByText( '3 days' ) ).toBeInTheDocument();
	} );

	test( 'renders a range like "3 - 5 days" when min and max differ', () => {
		render(
			<ShippingTimesCell
				market={ { shipping: { flat_time: 3, flat_max_time: 5 } } }
			/>
		);

		expect( screen.getByText( '3 - 5 days' ) ).toBeInTheDocument();
	} );
} );
