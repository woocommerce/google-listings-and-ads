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

jest.mock( '.~/hooks/useTargetAudienceFinalCountryCodes', () => () => ( {
	data: { selectedCountryCodes: [ 'ES' ] },
} ) );

const mockupsertShippingRates = jest.fn();

jest.mock( '.~/data', () => ( {
	useAppDispatch: () => ( {
		upsertShippingRates: mockupsertShippingRates,
	} ),
} ) );

describe( 'CountriesPriceInput', () => {
	const defaultProps = {
		savedValue: {
			countries: [ 'ES' ],
			price: '1',
		},
	};

	afterEach( () => {
		jest.clearAllMocks();
	} );

	test( 'Check if the saved price value is set in the input', () => {
		render( <CountriesPriceInputForm { ...defaultProps } /> );

		const input = screen.getByRole( 'textbox' );
		expect( input ).toHaveValue( defaultProps.savedValue.price );
	} );

	test( 'Check if the new value is updated without using the saved value & upsertShippingRates is called', () => {
		render( <CountriesPriceInputForm { ...defaultProps } /> );

		const input = screen.getByRole( 'textbox' );
		expect( input ).toHaveValue( defaultProps.savedValue.price );

		userEvent.clear( input );
		userEvent.type( input, '2' );

		fireEvent.blur( input );

		expect( input ).toHaveValue( '2' );
		expect( mockupsertShippingRates ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'Check when the saved price value is null and it has not been edited & upsertShippingRates is called', () => {
		render(
			<CountriesPriceInputForm
				savedValue={ { ...defaultProps.savedValue, price: null } }
			/>
		);

		const input = screen.getByRole( 'textbox' );
		fireEvent.blur( input );
		expect( input ).toHaveValue( '0' );
		expect( mockupsertShippingRates ).toHaveBeenCalledTimes( 1 );
	} );
} );
