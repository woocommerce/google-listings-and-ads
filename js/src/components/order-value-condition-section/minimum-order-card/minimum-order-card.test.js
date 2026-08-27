/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import MinimumOrderCard from './minimum-order-card';
import {
	useAdaptiveFormContext,
	useAdaptiveFormInputProps,
} from '~/components/adaptive-form';

jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn(),
	useAdaptiveFormInputProps: jest.fn(),
} ) );

const defaultRates = Object.freeze( [
	{
		id: '1',
		country: 'US',
		currency: 'USD',
		rate: 20,
		options: { free_shipping_threshold: 50 },
	},
	{
		id: '2',
		country: 'ES',
		currency: 'USD',
		rate: 20,
		options: { free_shipping_threshold: 50 },
	},
] );

function mockFormContext( { offerFreeShipping = true } = {} ) {
	useAdaptiveFormContext.mockReturnValue( {
		values: { offer_free_shipping: offerFreeShipping },
	} );
	useAdaptiveFormInputProps.mockReturnValue( {
		checked: offerFreeShipping,
		onChange: jest.fn(),
		onBlur: jest.fn(),
		value: offerFreeShipping,
	} );
}

describe( 'MinimumOrderCard', () => {
	let onChange;

	beforeEach( () => {
		onChange = jest.fn();
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	describe( 'rendering', () => {
		it( 'renders a single threshold input when offer_free_shipping is true', () => {
			mockFormContext( { offerFreeShipping: true } );

			render(
				<MinimumOrderCard
					onChange={ onChange }
					value={ defaultRates }
				/>
			);

			expect( screen.getByRole( 'textbox' ) ).toHaveValue( '50.00' );
		} );

		it( 'does not render the threshold input when offer_free_shipping is false', () => {
			mockFormContext( { offerFreeShipping: false } );

			render(
				<MinimumOrderCard
					onChange={ onChange }
					value={ defaultRates }
				/>
			);

			expect( screen.queryByRole( 'textbox' ) ).toBeNull();
		} );
	} );

	describe( 'handleBlur', () => {
		it( 'calls onChange with all rates updated when threshold changes', async () => {
			mockFormContext( { offerFreeShipping: true } );
			const user = userEvent.setup();

			const rates = [
				{
					id: '1',
					country: 'US',
					currency: 'USD',
					rate: 20,
					options: { free_shipping_threshold: undefined },
				},
				{
					id: '2',
					country: 'ES',
					currency: 'USD',
					rate: 15,
					options: { free_shipping_threshold: undefined },
				},
			];

			render(
				<MinimumOrderCard onChange={ onChange } value={ rates } />
			);

			const input = screen.getByRole( 'textbox' );
			await user.type( input, '30' );
			await user.tab();

			expect( onChange ).toHaveBeenCalledTimes( 1 );
			const nextValue = onChange.mock.calls[ 0 ][ 0 ];
			expect(
				nextValue.every(
					( r ) => r.options.free_shipping_threshold === 30
				)
			).toBe( true );
		} );

		it( 'does not call onChange when blur with the same value', async () => {
			mockFormContext( { offerFreeShipping: true } );
			const user = userEvent.setup();

			render(
				<MinimumOrderCard
					onChange={ onChange }
					value={ defaultRates }
				/>
			);

			const input = screen.getByRole( 'textbox' );
			// Clear then retype the same value.
			await user.clear( input );
			await user.type( input, '50' );
			await user.tab();

			expect( onChange ).not.toHaveBeenCalled();
		} );

		it( 'sets threshold to undefined when value is 0', async () => {
			mockFormContext( { offerFreeShipping: true } );
			const user = userEvent.setup();

			render(
				<MinimumOrderCard
					onChange={ onChange }
					value={ defaultRates }
				/>
			);

			const input = screen.getByRole( 'textbox' );
			await user.clear( input );
			await user.tab();

			expect( onChange ).toHaveBeenCalledTimes( 1 );
			const nextValue = onChange.mock.calls[ 0 ][ 0 ];
			expect(
				nextValue.every(
					( r ) => r.options.free_shipping_threshold === undefined
				)
			).toBe( true );
		} );
	} );
} );
