/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import MinimumOrderCard from './minimum-order-card';
import * as adaptiveForm from '~/components/adaptive-form';

jest.mock( '~/hooks/useAppSelectDispatch' );
jest.mock( '~/hooks/useCountryKeyNameMap' );
jest.mock( '~/hooks/useStoreCurrency' );
jest.mock( '~/components/adaptive-form', () => {
	return {
		__esModule: true,
		...jest.requireActual( '~/components/adaptive-form' ),
	};
} );

const mockGetInputProps = jest.fn().mockImplementation( () => {
	return {
		checked: true,
		className: '',
		help: null,
		onBlur: () => {},
		onChange: () => {},
		selected: true,
		value: true,
	};
} );

const adaptiveFormContextDefaultValues = {
	countries: [ 'ES' ],
	language: 'English',
	locale: 'en_US',
	location: 'selected',
	offer_free_shipping: true,
	shipping_country_rates: [],
	shipping_country_times: [],
	shipping_rate: true,
	shipping_time: true,
};

const adaptiveFormInputPropsDefaultValues = {
	checked: true,
	className: '',
	help: null,
	onBlur: () => {},
	onChange: () => {},
	selected: true,
	value: true,
};

const getMinimumOrderCardDefaultValue = () => {
	return Object.freeze( [
		{
			id: '1',
			country: 'US',
			currency: 'USD',
			rate: 20,
			options: {},
		},
		{
			id: '2',
			country: 'ES',
			currency: 'USD',
			rate: 20,
			options: {
				free_shipping_threshold: 50,
			},
		},
		{
			id: '3',
			country: 'CN',
			currency: 'USD',
			rate: 25,
			options: {
				free_shipping_threshold: 50,
			},
		},
	] );
};

const spyAdaptiveFormFunction = ( functionName ) => {
	return jest.spyOn( adaptiveForm, functionName );
};

const mockAdaptiveFormContextImplementation = ( spy, values = {} ) => {
	spy.mockImplementation( () => ( {
		getInputProps: mockGetInputProps(),
		values: {
			...adaptiveFormContextDefaultValues,
			...values,
		},
	} ) );
};

const mockAdaptiveFormInputPropsImplementation = ( spy, values = {} ) => {
	spy.mockImplementation( () => ( {
		...adaptiveFormInputPropsDefaultValues,
		...values,
	} ) );
};

describe( 'MinimumOrderCard', () => {
	let value,
		onChange,
		rendered,
		spyAdaptiveFormContext,
		spyAdaptiveFormInputProps;

	describe( 'onChange callback property', () => {
		beforeEach( () => {
			spyAdaptiveFormContext = spyAdaptiveFormFunction(
				'useAdaptiveFormContext'
			);
			mockAdaptiveFormContextImplementation( spyAdaptiveFormContext );

			spyAdaptiveFormInputProps = spyAdaptiveFormFunction(
				'useAdaptiveFormInputProps'
			);
			mockAdaptiveFormInputPropsImplementation(
				spyAdaptiveFormInputProps
			);

			value = getMinimumOrderCardDefaultValue();
			onChange = jest.fn().mockName( 'onChange callback' );

			rendered = render(
				<MinimumOrderCard value={ value } onChange={ onChange } />
			);
		} );

		afterEach( () => {
			jest.clearAllMocks();
		} );

		test( 'When the new minimum order value is provided for a remaining country, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold` set to the given value', async () => {
			const user = userEvent.setup();

			// Open "Add another…" modal.
			await user.click( rendered.getByRole( 'button', { name: /Add/ } ) );
			// Input some value.
			const input = screen.getByRole( 'textbox' );
			await user.type( input, '30' );
			// Confirm.
			await user.click(
				screen.getByRole( 'button', {
					name: /Add minimum order/,
				} )
			);

			expect( onChange ).toHaveBeenCalledTimes( 1 );

			// Expect US threshold to be set to 30.
			const expectedValue = structuredClone( value );
			expectedValue[ 0 ].options.free_shipping_threshold = 30;
			expect( onChange ).toHaveBeenCalledWith( expectedValue );
		} );

		test( 'When a minimum order value is changed for an existing group, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold`s set to the given value', async () => {
			const user = userEvent.setup();

			// Input some value.
			const input = rendered.getByRole( 'textbox' );
			await user.type( input, '7' );
			// Blur away.
			await user.tab();

			expect( onChange ).toHaveBeenCalledTimes( 1 );

			// Expect ES, CN threshold to be updated to 507.
			const expectedValue = structuredClone( value );
			expectedValue[ 1 ].options.free_shipping_threshold = 507;
			expectedValue[ 2 ].options.free_shipping_threshold = 507;
			expect( onChange ).toHaveBeenCalledWith( expectedValue );
		} );
		test( 'When a set of countries is changed for a minimum order value in an existing group, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold`s set to the given value for new countries, and `undefined` for old ones', async () => {
			const user = userEvent.setup();

			// Open group/"Edit" modal.
			await user.click(
				rendered.getByRole( 'button', { name: /Edit/ } )
			);
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
			await user.click(
				screen.getByRole( 'button', {
					name: /Update/,
				} )
			);

			expect( onChange ).toHaveBeenCalledTimes( 1 );

			// Expect US threshold to be set to 50,
			// and ES threshold to be set to `undefined.
			const expectedValue = structuredClone( value );
			expectedValue[ 0 ].options.free_shipping_threshold = 50;
			expectedValue[ 1 ].options.free_shipping_threshold = undefined;
			expect( onChange ).toHaveBeenCalledWith( expectedValue );
		} );
		test( 'When a set of countries and threshold are changed for an existing group, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold`s set to the given value for new countries, and `undefined` for old ones', async () => {
			const user = userEvent.setup();

			// Open group/"Edit" modal.
			await user.click(
				rendered.getByRole( 'button', { name: /Edit/ } )
			);
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
			await user.type( input, '7' );
			// Confirm.
			await user.click(
				screen.getByRole( 'button', {
					name: /Update/,
				} )
			);

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

		test( 'When a minimum order value is removed for a group of countries, calls the `onChange` callback with the new value containing `shippingRate.options.free_shipping_threshold`s set to `undefined`', async () => {
			const user = userEvent.setup();

			// Open group/"Edit" modal.
			await user.click(
				rendered.getByRole( 'button', { name: /Edit/ } )
			);
			// Click delete.
			await user.click(
				screen.getByRole( 'button', { name: /Delete/ } )
			);
			await user.tab();

			expect( onChange ).toHaveBeenCalledTimes( 1 );

			// Expect ES & CN threshold to be set to `undefined`.
			const expectedValue = structuredClone( value );
			expectedValue[ 1 ].options.free_shipping_threshold = undefined;
			expectedValue[ 2 ].options.free_shipping_threshold = undefined;
			expect( onChange ).toHaveBeenCalledWith( expectedValue );
		} );
	} );

	describe( 'Free shipping order value condition', () => {
		beforeEach( () => {
			value = getMinimumOrderCardDefaultValue();
			onChange = jest.fn().mockName( 'onChange callback' );
		} );

		afterEach( () => {
			jest.clearAllMocks();
		} );

		test( 'When no free shipping is offered, hides the input field', async () => {
			spyAdaptiveFormContext = spyAdaptiveFormFunction(
				'useAdaptiveFormContext'
			);
			mockAdaptiveFormContextImplementation( spyAdaptiveFormContext, {
				offer_free_shipping: false,
			} );

			spyAdaptiveFormInputProps = spyAdaptiveFormFunction(
				'useAdaptiveFormInputProps'
			);
			mockAdaptiveFormInputPropsImplementation(
				spyAdaptiveFormInputProps
			);

			rendered = render(
				<MinimumOrderCard value={ value } onChange={ onChange } />
			);

			const { container } = rendered;

			const inputControl = container.querySelector(
				'.gla-minimum-order-input-control'
			);

			expect( inputControl ).toBeNull();
		} );

		test( 'When free shipping is offered, do not hide the input field', async () => {
			spyAdaptiveFormContext = spyAdaptiveFormFunction(
				'useAdaptiveFormContext'
			);
			mockAdaptiveFormContextImplementation( spyAdaptiveFormContext, {
				offer_free_shipping: true,
			} );

			spyAdaptiveFormInputProps = spyAdaptiveFormFunction(
				'useAdaptiveFormInputProps'
			);
			mockAdaptiveFormInputPropsImplementation(
				spyAdaptiveFormInputProps
			);

			rendered = render(
				<MinimumOrderCard value={ value } onChange={ onChange } />
			);

			const { container } = rendered;

			const inputControl = container.querySelector(
				'.gla-minimum-order-input-control'
			);

			expect( inputControl ).not.toBeNull();
		} );
	} );
} );
