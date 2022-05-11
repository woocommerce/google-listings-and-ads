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

		test( 'When the new minimum order value is provided for a remianing country, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold` set to the given value', async () => {
			// Open "Add another…" modal.
			userEvent.click( rendered.getByRole( 'button', { name: /Add/ } ) );
			// Input some value.
			const input = screen.getByRole( 'textbox' );
			// For some reason it's typed backwards.
			await userEvent.type( input, '03' );
			// Confirm.
			await userEvent.click(
				screen.getByRole( 'button', {
					name: /Add minimum order/,
				} )
			);

			// Wait for Form component's onSubmit microtask.
			await waitFor( () => {
				expect( onChange ).toHaveBeenCalledTimes( 1 );
				expect( onChange ).toHaveBeenCalledWith( [
					{
						...value[ 0 ],
						options: {
							free_shipping_threshold: 30,
						},
					},
					value[ 1 ],
					value[ 2 ],
				] );
			} );
		} );
		test( 'When a minimum order value is changed for an existing group, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold`s set to the given value', async () => {
			// Input some value.
			const input = rendered.getByRole( 'textbox' );
			// For some reason it's typed backwards.
			await userEvent.type( input, '7' );
			// Blur away.
			await userEvent.tab();

			// Wait for Form component's onSubmit microtask.
			await waitFor( () => {
				expect( onChange ).toHaveBeenCalledTimes( 1 );
				expect( onChange ).toHaveBeenCalledWith( [
					value[ 0 ],
					{
						...value[ 1 ],
						options: {
							free_shipping_threshold: 507,
						},
					},
					{
						...value[ 2 ],
						options: {
							free_shipping_threshold: 507,
						},
					},
				] );
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
				expect( onChange ).toHaveBeenCalledWith( [
					{
						...value[ 0 ],
						options: {
							free_shipping_threshold: 50,
						},
					},
					{
						...value[ 1 ],
						options: {
							free_shipping_threshold: undefined,
						},
					},
					{
						...value[ 2 ],
						options: {
							free_shipping_threshold: 50,
						},
					},
				] );
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
			// For some reason it's typed backwards.
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
				expect( onChange ).toHaveBeenCalledWith( [
					{
						...value[ 0 ],
						options: {
							free_shipping_threshold: 507,
						},
					},
					{
						...value[ 1 ],
						options: {
							free_shipping_threshold: undefined,
						},
					},
					{
						...value[ 2 ],
						options: {
							free_shipping_threshold: 507,
						},
					},
				] );
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
				expect( onChange ).toHaveBeenCalledWith( [
					value[ 0 ],
					{
						...value[ 1 ],
						options: {
							free_shipping_threshold: undefined,
						},
					},
					{
						...value[ 2 ],
						options: {
							free_shipping_threshold: undefined,
						},
					},
				] );
			} );
		} );
	} );
} );
