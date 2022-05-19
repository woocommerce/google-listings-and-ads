/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import MinimumOrderCard from './minimum-order-card.js';
import { structuredClone } from '.~/utils/structuredClone.js';

jest.mock( '.~/hooks/useAppSelectDispatch' );
jest.mock( '.~/hooks/useCountryKeyNameMap' );
jest.mock( '.~/hooks/useStoreCurrency' );

describe( 'MinimumOrderCard', () => {
	describe( 'onChange callback property', () => {
		let value, onChange, rendered;
		beforeEach( () => {
			value = Object.freeze( [
				{
					id: '1',
					country: 'US',
					method: 'flat_rate',
					currency: 'USD',
					rate: 20,
					options: {},
				},
				{
					id: '2',
					country: 'ES',
					method: 'flat_rate',
					currency: 'USD',
					rate: 20,
					options: {
						free_shipping_threshold: 50,
					},
				},
				{
					id: '3',
					country: 'CN',
					method: 'flat_rate',
					currency: 'USD',
					rate: 25,
					options: {
						free_shipping_threshold: 50,
					},
				},
			] );
			onChange = jest.fn().mockName( 'onChange callback' );

			rendered = render(
				<MinimumOrderCard value={ value } onChange={ onChange } />
			);
		} );

		afterEach( () => {
			jest.clearAllMocks();
		} );

		test( 'When the new minimum order value is provided for a remaining country, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold` set to the given value', async () => {
			// Open "Add another…" modal.
			userEvent.click( rendered.getByRole( 'button', { name: /Add/ } ) );
			// Input some value.
			const input = screen.getByRole( 'textbox' );
			await userEvent.type( input, '30' );
			// Confirm.
			await userEvent.click(
				screen.getByRole( 'button', {
					name: /Add minimum order/,
				} )
			);

			// Wait for Form component's onSubmit microtask.
			await waitFor( () => {
				expect( onChange ).toHaveBeenCalledTimes( 1 );
				// Expect US threshold to be set to 30.
				const expectedValue = structuredClone( value );
				expectedValue[ 0 ].options.free_shipping_threshold = 30;
				expect( onChange ).toHaveBeenCalledWith( expectedValue );
			} );
		} );
		test( 'When a minimum order value is changed for an existing group, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold`s set to the given value', async () => {
			// Input some value.
			const input = rendered.getByRole( 'textbox' );
			await userEvent.type( input, '7' );
			// Blur away.
			await userEvent.tab();

			// Wait for Form component's onSubmit microtask.
			await waitFor( () => {
				expect( onChange ).toHaveBeenCalledTimes( 1 );

				// Expect ES, CN threshold to be updated to 507.
				const expectedValue = structuredClone( value );
				expectedValue[ 1 ].options.free_shipping_threshold = 507;
				expectedValue[ 2 ].options.free_shipping_threshold = 507;
				expect( onChange ).toHaveBeenCalledWith( expectedValue );
			} );
		} );
		test( 'When a set of countries is changed for a minimum order value in an existing group, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold`s set to the given value for new countries, and `undefied` for old ones', async () => {
			// Open group/"Edit" modal.
			userEvent.click( rendered.getByRole( 'button', { name: /Edit/ } ) );
			// Input some value.
			const countriesSelect = rendered.getByRole( 'combobox' );
			await fireEvent.click( countriesSelect );
			// Find and de-select Spain.
			fireEvent.change( countriesSelect, { target: { value: 'Spain' } } );
			fireEvent.click( screen.queryByLabelText( 'Spain' ) );
			// Find and select States.
			fireEvent.change( countriesSelect, { target: { value: 'State' } } );
			fireEvent.click( screen.queryByLabelText( 'United States' ) );
			// Confirm.
			await userEvent.click(
				screen.getByRole( 'button', {
					name: /Update/,
				} )
			);

			// Wait for Form component's onSubmit microtask.
			await waitFor( () => {
				expect( onChange ).toHaveBeenCalledTimes( 1 );

				// Expect US threshold to be set to 50,
				// and ES threshold to be set to `undefined.
				const expectedValue = structuredClone( value );
				expectedValue[ 0 ].options.free_shipping_threshold = 50;
				expectedValue[ 1 ].options.free_shipping_threshold = undefined;
				expect( onChange ).toHaveBeenCalledWith( expectedValue );
			} );
		} );
		test( 'When a set of countries and threshold are changed for an existing group, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold`s set to the given value for new countries, and `undefied` for old ones', async () => {
			// Open group/"Edit" modal.
			userEvent.click( rendered.getByRole( 'button', { name: /Edit/ } ) );
			// Input some value.
			const countriesSelect = rendered.getByRole( 'combobox' );
			await fireEvent.click( countriesSelect );
			// Find and de-select Spain.
			fireEvent.change( countriesSelect, { target: { value: 'Spain' } } );
			fireEvent.click( screen.queryByLabelText( 'Spain' ) );
			// Find and select States.
			fireEvent.change( countriesSelect, { target: { value: 'State' } } );
			fireEvent.click( screen.queryByLabelText( 'United States' ) );
			// Input some value.
			const input = rendered.getByRole( 'textbox' );
			await userEvent.type( input, '7' );
			// Confirm.
			await userEvent.click(
				screen.getByRole( 'button', {
					name: /Update/,
				} )
			);

			// Wait for Form component's onSubmit microtask.
			await waitFor( () => {
				expect( onChange ).toHaveBeenCalledTimes( 1 );

				// Expect US threshold to be set to 507,
				// ES threshold to be set to `undefined`,
				// and CN to be changed to 507.
				const expectedValue = structuredClone( value );
				expectedValue[ 0 ].options.free_shipping_threshold = 507;
				expectedValue[ 1 ].options.free_shipping_threshold = undefined;
				expectedValue[ 2 ].options.free_shipping_threshold = 507;
				expect( onChange ).toHaveBeenCalledWith( expectedValue );
			} );
		} );

		test( 'When a minimum order value is removed for a group of countries, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold`s set to `undefined`', async () => {
			// Open group/"Edit" modal.
			userEvent.click( rendered.getByRole( 'button', { name: /Edit/ } ) );
			// Click delete.
			userEvent.click( screen.getByRole( 'button', { name: /Delete/ } ) );
			await userEvent.tab();

			// Wait for Form component's onSubmit microtask.
			await waitFor( () => {
				expect( onChange ).toHaveBeenCalledTimes( 1 );

				// Expect ES & CN threshold to be set to `undefined`.
				const expectedValue = structuredClone( value );
				expectedValue[ 1 ].options.free_shipping_threshold = undefined;
				expectedValue[ 2 ].options.free_shipping_threshold = undefined;
				expect( onChange ).toHaveBeenCalledWith( expectedValue );
			} );
		} );
	} );
} );
