/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, fireEvent } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import CountriesPriceInputForm from './';
import { useAppDispatch } from '.~/data';

jest.mock( '.~/hooks/useStoreCurrency', () =>
	jest.fn( () => ( {
		code: 'GBP',
	} ) )
);

jest.mock( '.~/hooks/useCountryKeyNameMap', () =>
	jest.fn( () => ( {
		GB: 'United Kingdom',
		US: 'United States',
		ES: 'Spain',
	} ) )
);

jest.mock( '.~/hooks/useTargetAudienceFinalCountryCodes', () =>
	jest.fn( () => ( {
		data: {
			selectedCountryCodes: [ 'GB', 'US', 'ES' ],
		},
	} ) )
);

jest.mock( '.~/data', () => ( {
	useAppDispatch: jest.fn( () => {
		return {
			upsertShippingRates: jest.fn(),
		};
	} ),
} ) );

describe( 'CountriesPriceInput', () => {
	const defaultProps = {
		savedValue: {
			countries: [ 'ES' ],
			price: '1',
		},
	};

	test( 'Check if the saved price value is set in the input', () => {
		render( <CountriesPriceInputForm { ...defaultProps } /> );

		const input = screen.getByRole( 'textbox' );
		fireEvent.blur( input );
		expect( input ).toHaveValue( defaultProps.savedValue.price );
	} );

	test( 'Check if the new value is updated', () => {
		render( <CountriesPriceInputForm { ...defaultProps } /> );

		const input = screen.getByRole( 'textbox' );
		expect( input ).toHaveValue( defaultProps.savedValue.price );

		userEvent.clear( input );
		userEvent.type( input, '2' );

		fireEvent.blur( input );

		expect( input ).toHaveValue( '2' );
	} );

	test( 'Check when the saved price value is null and it has not been edited', () => {
		render(
			<CountriesPriceInputForm
				savedValue={ { ...defaultProps.savedValue, price: null } }
			/>
		);

		const input = screen.getByRole( 'textbox' );
		fireEvent.blur( input );
		expect( input ).toHaveValue( '0' );
	} );
} );
